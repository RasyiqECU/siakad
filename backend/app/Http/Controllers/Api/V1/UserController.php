<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\RespondsWithEnvelope;
use App\Http\Requests\Api\V1\ResetUserPasswordRequest;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manajemen pengguna (API_SPECIFICATION §3.2).
 * Proteksi:
 * - tidak bisa menghapus/menonaktifkan diri sendiri;
 * - sistem wajib menyisakan minimal satu super admin;
 * - aksi sensitif tercatat di audit log via SimpleAuditLogger (dengan IP).
 */
class UserController extends Controller
{
    use RespondsWithEnvelope;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);

        $query = User::query()->with('roles')->orderBy('id');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.name', $role));
        }

        if ($request->query('is_active') !== null) {
            $query->where('is_active', (bool) $request->query('is_active', '0'));
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage)->appends($request->query());

        return $this->respondPaginated($paginator, UserResource::class, 'Daftar pengguna berhasil dimuat.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $roles = $validated['roles'] ?? [];
            if ($roles !== []) {
                $user->syncRoles($roles);
            }

            return $user;
        });

        SimpleAuditLogger::log('user_created', $user, [
            'attributes' => [$user->name, $user->email],
            'roles' => $user->getRoleNames()->all(),
        ]);

        return $this->respondSingle(
            UserResource::make($user->load('roles'))->toArray($request),
            'Pengguna berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, User $user): JsonResponse
    {
        return $this->respondSingle(
            UserResource::make($user->load('roles'))->toArray($request),
            'Detail pengguna berhasil dimuat.'
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        // Proteksi: tidak bisa menonaktifkan diri sendiri.
        if (array_key_exists('is_active', $validated) && ! $validated['is_active'] && $request->user()->is($user)) {
            return $this->respondError('Tidak dapat menonaktifkan akun sendiri.', 422);
        }

        $remaining = $this->remainingSuperAdmins($user);

        if (array_key_exists('roles', $validated)) {
            // Proteksi: super admin terakhir tidak bisa dilepas rolenya, kecuali
            // target tetap diberikan kembali role super_admin.
            if ($user->hasRole('super_admin')
                && ! in_array('super_admin', $validated['roles'], true)
                && $remaining === 0) {
                return $this->respondError('Sistem harus memiliki minimal satu super admin.', 422);
            }
        }

        if (array_key_exists('is_active', $validated) && ! $validated['is_active']
            && $user->hasRole('super_admin') && $user->is_active && $remaining === 0) {
            return $this->respondError('Sistem harus memiliki minimal satu super admin aktif.', 422);
        }

        $user = DB::transaction(function () use ($user, $validated) {
            if (array_key_exists('roles', $validated)) {
                $user->syncRoles($validated['roles']);
            }

            $user->update($validated);

            return $user;
        });

        SimpleAuditLogger::log('user_updated', $user, [
            'changes' => $user->getChanges(),
            'roles' => $user->getRoleNames()->all(),
        ]);

        return $this->respondSingle(
            UserResource::make($user->load('roles'))->toArray($request),
            'Pengguna berhasil diperbarui.'
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return $this->respondError('Tidak dapat menghapus akun sendiri.', 422);
        }

        if ($user->hasRole('super_admin') && $this->remainingSuperAdmins($user) === 0) {
            return $this->respondError('Sistem harus memiliki minimal satu super admin.', 422);
        }

        $user->tokens()->delete();

        $user->delete();

        SimpleAuditLogger::log('user_deleted', $user, [
            'attributes' => [$user->name, $user->email],
        ]);

        return $this->respondDeleted('Pengguna berhasil dihapus.');
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        $user->update(['is_active' => true]);

        SimpleAuditLogger::log('user_activated', $user, ['is_active' => true]);

        return $this->respondSingle(
            UserResource::make($user->load('roles'))->toArray($request),
            'Pengguna berhasil diaktifkan.'
        );
    }

    public function deactivate(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return $this->respondError('Tidak dapat menonaktifkan akun sendiri.', 422);
        }

        if ($user->hasRole('super_admin') && $user->is_active
            && $this->remainingSuperAdmins($user) === 0) {
            return $this->respondError('Sistem harus memiliki minimal satu super admin aktif.', 422);
        }

        $user->update(['is_active' => false]);
        $user->tokens()->delete(); // paksa logout semua device

        SimpleAuditLogger::log('user_deactivated', $user, ['is_active' => false]);

        return $this->respondSingle(
            UserResource::make($user->load('roles'))->toArray($request),
            'Pengguna berhasil dinonaktifkan.'
        );
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): JsonResponse
    {
        $user->update(['password' => $request->validated('password')]);

        // Token lama tidak berlaku.
        $user->tokens()->delete();

        SimpleAuditLogger::log('user_password_reset', $user, ['reset_at' => now()->toDateTimeString()]);

        return $this->respondSingle(
            UserResource::make($user->load('roles'))->toArray($request),
            'Password pengguna berhasil direset. Token lama dicabut.'
        );
    }

    /** Jumlah super admin lain (tidak termasuk target). */
    private function remainingSuperAdmins(User $target): int
    {
        return User::whereKeyNot($target->getKey())
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('roles.name', 'super_admin'))
            ->count();
    }

    private function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return max(1, min((int) ($request->query('per_page', $default)), $max));
    }
}
