<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature test Phase 2 — Manajemen Pengguna, Profil, Role & Audit Log
 * (API_SPECIFICATION §3.2).
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function seedApp(): void
    {
        $this->seed();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** User demo super_admin dari RolesAndUsersSeeder. */
    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@simora.test')->firstOrFail();
    }

    protected function actingUser(User $user): self
    {
        return $this->actingAs($user, 'sanctum');
    }

    /**
     * Buang cache guard auth — pada harness test satu instance RequestGuard
     * bisa mem-cache user antar-request (tidak terjadi di request HTTP nyata).
     * Dipanggil sebelum menguji pencabutan token.
     */
    protected function freshAuthGuards(): void
    {
        $this->app->make('auth')->forgetGuards();
    }

    // ------------------------------------------------------------------
    // Autentikasi & otorisasi
    // ------------------------------------------------------------------

    public function test_guest_cannot_list_users(): void
    {
        $this->seedApp();

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
        $this->assertNotNull($response->json('message'));
    }

    public function test_non_privileged_role_cannot_list_users(): void
    {
        $this->seedApp();

        $mahasiswa = User::where('email', 'mahasiswa@simora.test')->firstOrFail();

        $response = $this->actingUser($mahasiswa)->getJson('/api/v1/users');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    // ------------------------------------------------------------------
    // CRUD user sebagai super admin
    // ------------------------------------------------------------------

    public function test_superadmin_can_create_update_deactivate_and_delete_user(): void
    {
        $this->seedApp();

        // Login via API supaya semua request memakai token sungguhan
        // (actingAs bertahan lintas-request dan bisa menyamarkan pencabutan token).
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'superadmin@simora.test',
            'password' => 'password',
        ]);
        $login->assertStatus(200);
        $adminToken = $login->json('data.token');
        $adminHeaders = ['Authorization' => "Bearer {$adminToken}"];

        $adminId = $login->json('data.user.id');

        // --- create
        $create = $this->withHeaders($adminHeaders)->postJson('/api/v1/users', [
            'name' => 'Dosen Uji',
            'email' => 'dosen.uji@simora.test',
            'password' => 'password123',
            'phone' => '08123123123',
            'roles' => ['dosen'],
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('success', true);

        $userId = $create->json('data.id');
        $user = User::findOrFail($userId);

        $this->assertTrue($user->is_active);
        $this->assertSame('dosen.uji@simora.test', $user->email);
        $this->assertTrue($user->hasRole('dosen'));
        $this->assertSame('Dosen', $user->roles->first()->display_name);

        // --- update
        $update = $this->withHeaders($adminHeaders)->patchJson("/api/v1/users/{$userId}", [
            'phone' => '08999888777',
        ]);
        $update->assertStatus(200)
            ->assertJsonPath('data.phone', '08999888777');

        // --- deactivate: token lama dicabut + login ditolak 403
        $oldToken = $user->createToken('device')->plainTextToken;

        $deactivate = $this->withHeaders($adminHeaders)->patchJson("/api/v1/users/{$userId}/deactivate");
        $deactivate->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'is_active' => false,
        ]);

        $blocked = $this->postJson('/api/v1/auth/login', [
            'email' => 'dosen.uji@simora.test',
            'password' => 'password123',
        ]);
        $blocked->assertStatus(403)
            ->assertJsonPath('success', false);

        // --- token lama tidak valid setelah deaktivasi
        $this->freshAuthGuards();
        $stale = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/auth/me');
        $stale->assertStatus(401);

        // --- activate kembali lalu delete (soft delete)
        $this->withHeaders($adminHeaders)->patchJson("/api/v1/users/{$userId}/activate")
            ->assertStatus(200);

        // hapus user lain (bukan diri sendiri — diuji di test terpisah)
        $this->withHeaders($adminHeaders)->deleteJson("/api/v1/users/{$userId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('users', ['id' => $userId]);
    }

    public function test_duplicate_email_rejected_with_422(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        $response = $this->actingUser($admin)->postJson('/api/v1/users', [
            'name' => 'Duplikat',
            'email' => 'mahasiswa@simora.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    public function test_short_password_rejected_with_422(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        $response = $this->actingUser($admin)->postJson('/api/v1/users', [
            'name' => 'Pendek',
            'email' => 'pendek@simora.test',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_list_users_returns_envelope_with_meta(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        $response = $this->actingUser($admin)->getJson('/api/v1/users?per_page=2&page=1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(2, 'data');

        $this->assertTrue(array_key_exists('links', $response->json()));

        // Filter role bekerja (dosen demo + akun dosen dari MasterDataDemoSeeder).
        $dosenOnly = $this->actingUser($admin)->getJson('/api/v1/users?role=dosen');
        $dosenOnly->assertStatus(200)
            ->assertJsonPath('data.0.email', 'dosen@simora.test');
        $this->assertGreaterThan(0, $dosenOnly->json('meta.total'));
    }

    // ------------------------------------------------------------------
    // Proteksi self & super admin terakhir
    // ------------------------------------------------------------------

    public function test_cannot_delete_or_deactivate_self(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        $deleteSelf = $this->actingUser($admin)->deleteJson('/api/v1/users/'.$admin->id);
        $deleteSelf->assertStatus(422)
            ->assertJsonPath('success', false);

        $deactivateSelf = $this->actingUser($admin)->patchJson('/api/v1/users/'.$admin->id.'/deactivate');
        $deactivateSelf->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_remove_last_super_admin(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        // role super_admin harus tetap pada satu-satunya pemegangnya.
        $response = $this->actingUser($admin)->patchJson('/api/v1/users/'.$admin->id, [
            'roles' => ['dosen'],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertTrue($admin->fresh()->hasRole('super_admin'));

        // sedangkan user non-super_admin boleh ganti role.
        $pimpinan = User::where('email', 'pimpinan@simora.test')->firstOrFail();
        $this->actingUser($admin)->patchJson("/api/v1/users/{$pimpinan->id}", [
            'roles' => ['keuangan'],
        ])->assertStatus(200)
            ->assertJsonPath('data.roles.0.name', 'keuangan');
    }

    // ------------------------------------------------------------------
    // Profil sendiri
    // ------------------------------------------------------------------

    public function test_profile_show_and_update_flow(): void
    {
        $this->seedApp();
        $mahasiswa = User::where('email', 'mahasiswa@simora.test')->firstOrFail();

        // --- GET /profile
        $show = $this->actingUser($mahasiswa)->getJson('/api/v1/profile');
        $show->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'mahasiswa@simora.test');

        $this->assertSame('mahasiswa', $show->json('data.roles.0.name'));
        // Mahasiswa demo kini ditautkan ke profil student (MasterDataDemoSeeder).
        $this->assertNotNull($show->json('data.student'));
        $this->assertNull($show->json('data.lecturer'));
        $this->assertContains('study_plans.create', $show->json('data.permissions'));

        // --- PATCH: password baru tanpa current_password → 422
        $this->actingUser($mahasiswa)->patchJson('/api/v1/profile', [
            'password' => 'password999',
        ])->assertStatus(422);

        // --- PATCH: current_password salah → 422
        $this->actingUser($mahasiswa)->patchJson('/api/v1/profile', [
            'password' => 'password999',
            'current_password' => ' SalahBin', // salah
        ])->assertStatus(422);

        // --- PATCH: nama + ganti password valid
        $update = $this->actingUser($mahasiswa)->patchJson('/api/v1/profile', [
            'name' => 'Andi Mahasiswa Updated',
            'phone' => '0899000111',
            'password' => 'password999',
            'current_password' => 'password',
        ]);

        $update->assertStatus(200)
            ->assertJsonPath('data.name', 'Andi Mahasiswa Updated')
            ->assertJsonPath('data.phone', '0899000111');

        $fresh = $mahasiswa->fresh();
        $this->assertTrue(Hash::check('password999', $fresh->password));

        // password baru bisa dipakai login.
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'mahasiswa@simora.test',
            'password' => 'password999',
        ]);
        $login->assertStatus(200)
            ->assertJsonPath('success', true);

        // audit entry profile_updated tercatat.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'profile_updated',
            'subject_id' => $mahasiswa->id,
            'user_id' => $mahasiswa->id,
        ]);
    }

    public function test_reset_password_revokes_tokens(): void
    {
        $this->seedApp();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'superadmin@simora.test',
            'password' => 'password',
        ]);
        $login->assertStatus(200);
        $adminHeaders = ['Authorization' => 'Bearer '.$login->json('data.token')];

        $target = User::where('email', 'dosen@simora.test')->firstOrFail();
        $oldToken = $target->createToken('device')->plainTextToken;

        $reset = $this->withHeaders($adminHeaders)->patchJson("/api/v1/users/{$target->id}/reset-password", [
            'password' => 'passwordbaru99',
        ]);

        $reset->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('passwordbaru99', $target->fresh()->password));

        // token lama dicabut → 401 dengan token lama.
        $this->freshAuthGuards();
        $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // Role & permission
    // ------------------------------------------------------------------

    public function test_roles_index_returns_roles_with_permissions(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        $response = $this->actingUser($admin)->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.permissions.0.id', fn ($id) => is_int($id))
            ->assertJsonCount(6, 'data');

        $names = collect($response->json('data'))->pluck('name')->all();
        foreach (['super_admin', 'admin_akademik', 'dosen', 'mahasiswa', 'keuangan', 'pimpinan'] as $expected) {
            $this->assertContains($expected, $names);
        }

        $mahasiswaRole = collect($response->json('data'))->firstWhere('name', 'mahasiswa');
        $this->assertSame('Mahasiswa', $mahasiswaRole['display_name']);
        $this->assertContains('study_plans.create', collect($mahasiswaRole['permissions'])->pluck('name')->all());
    }

    public function test_keuangan_role_permission_sync_works(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        $role = Role::findByName('mahasiswa', 'web');
        $original = $role->permissions->pluck('name')->all();
        $this->assertNotEmpty($original);

        $newList = array_values([
            'study_plans.read', 'study_plans.create', 'study_plans.update',
            'grades.read', 'tuition_bills.read', 'payments.create', 'payments.read',
        ]);

        $update = $this->actingUser($admin)->putJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => $newList,
        ]);

        $update->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertSame(7, Role::findByName('mahasiswa', 'web')->permissions->count());

        // mahasiswa kini punya payments.read.
        $mahasiswa = User::where('email', 'mahasiswa@simora.test')->firstOrFail();
        $this->assertTrue($mahasiswa->hasPermissionTo('payments.read', 'web'));

        // invalid permission name → 422
        $this->actingUser($admin)->putJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => ['permission.ngasal'],
        ])->assertStatus(422);

        // --- revert
        $revert = $this->actingUser($admin)->putJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => $original,
        ]);
        $revert->assertStatus(200);
        $this->assertSame(count($original), Role::findByName('mahasiswa', 'web')->permissions->count());
    }

    // ------------------------------------------------------------------
    // Audit log
    // ------------------------------------------------------------------

    public function test_audit_log_written_on_user_actions(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        // create (HTTP → user_created ber-IP)
        $create = $this->actingUser($admin)->postJson('/api/v1/users', [
            'name' => 'Audit Uji',
            'email' => 'audit.uji@simora.test',
            'password' => 'password123',
        ]);
        $create->assertStatus(201);
        $userId = $create->json('data.id');

        // password reset
        $this->actingUser($admin)->patchJson("/api/v1/users/{$userId}/reset-password", [
            'password' => 'password456',
        ])->assertStatus(200);

        // delete (HTTP → user_deleted; observer → deleted)
        $this->actingUser($admin)->deleteJson("/api/v1/users/{$userId}")->assertStatus(200);

        // Audit endpoint listable.
        $logs = AuditLog::query()
            ->where('subject_id', $userId)
            ->where('subject_type', User::class)
            ->get();

        $actions = $logs->pluck('action')->all();

        $this->assertContains('user_created', $actions);
        $this->assertContains('user_password_reset', $actions);
        $this->assertContains('user_deleted', $actions);
        // Observer juga mencatat event model.
        $observerActions = ['created', 'updated', 'deleted'];
        $this->assertNotEmpty(array_intersect($observerActions, $actions));

        // Vendor aksi HTTP diisi aktor + IP dari request.
        $created = $logs->firstWhere('action', 'user_created');
        $this->assertSame($admin->id, $created->user_id);
        $this->assertNotNull($created->ip_address);
        $this->assertNotNull($created->user_agent);

        // properties tidak pernah menyimpan password.
        foreach ($logs->pluck('properties')->all() as $properties) {
            $this->assertArrayNotHasKey('password', $properties ?? []);
        }
    }

    public function test_audit_logs_endpoint_listable_and_filtered(): void
    {
        $this->seedApp();
        $admin = $this->superadmin();

        $this->actingUser($admin)->patchJson('/api/v1/profile', [
            'name' => 'Super Admin SIMORA',
        ])->assertStatus(200);

        $response = $this->actingUser($admin)
            ->getJson('/api/v1/audit-logs?action=profile_updated&user_id='.$admin->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        foreach ($response->json('data') as $log) {
            $this->assertSame($admin->id, $log['user']['id']);
            $this->assertSame('profile_updated', $log['action']);
        }

        $this->assertGreaterThan(0, $response->json('meta.total'));
    }
}
