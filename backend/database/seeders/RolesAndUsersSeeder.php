<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Seeder awal: 6 role utama SIMORA + akun demo (hanya untuk environment non-production).
 * Permission lengkap diisi pada fase RBAC (Phase 2).
 */
class RolesAndUsersSeeder extends Seeder
{
    public const ROLES = [
        ['name' => 'super_admin', 'display_name' => 'Super Admin'],
        ['name' => 'admin_akademik', 'display_name' => 'Admin Akademik'],
        ['name' => 'dosen', 'display_name' => 'Dosen'],
        ['name' => 'mahasiswa', 'display_name' => 'Mahasiswa'],
        ['name' => 'keuangan', 'display_name' => 'Bagian Keuangan'],
        ['name' => 'pimpinan', 'display_name' => 'Pimpinan'],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            // findOrCreate spatie TIDAK mendukung atribut tambahan —
            // pastikan display_name ter-set/update setelahnya.
            $roleModel = Role::findOrCreate($role['name'], 'web');

            if ($roleModel->display_name !== $role['display_name']) {
                $roleModel->display_name = $role['display_name'];
                $roleModel->save();
            }
        }

        $demoUsers = [
            ['name' => 'Super Admin SIMORA', 'email' => 'superadmin@simora.test', 'role' => 'super_admin'],
            ['name' => 'Admin Akademik SIMORA', 'email' => 'admin@simora.test', 'role' => 'admin_akademik'],
            ['name' => 'Dr. Budi Santoso, M.Kom', 'email' => 'dosen@simora.test', 'role' => 'dosen'],
            ['name' => 'Andi Mahasiswa', 'email' => 'mahasiswa@simora.test', 'role' => 'mahasiswa'],
            ['name' => 'Staf Keuangan SIMORA', 'email' => 'keuangan@simora.test', 'role' => 'keuangan'],
            ['name' => 'Prof. Siti Rahayu, Dr.', 'email' => 'pimpinan@simora.test', 'role' => 'pimpinan'],
        ];

        if (app()->environment('production')) {
            $this->command?->warn('Seeder akun demo dilewati (APP_ENV = production).');

            return;
        }

        foreach ($demoUsers as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );
            $user->syncRoles([$row['role']]);
        }
    }
}
