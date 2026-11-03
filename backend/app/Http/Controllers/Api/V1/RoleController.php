<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\RespondsWithEnvelope;
use App\Http\Requests\Api\V1\SyncRolePermissionsRequest;
use App\Http\Resources\Api\V1\RoleResource;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Daftar role + sinkronisasi permission role (API_SPECIFICATION §3.2).
 */
class RoleController extends Controller
{
    use RespondsWithEnvelope;

    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->with('permissions')
            ->orderBy('id')
            ->get();

        return $this->respondSingle(
            RoleResource::collection($roles)->toArray(request()),
            'Daftar role berhasil dimuat.'
        );
    }

    public function updatePermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $permissionNames = $request->validated('permissions');

        $role->syncPermissions($permissionNames);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        SimpleAuditLogger::log('role_permissions_updated', $role, [
            'role' => $role->name,
            'permissions' => $role->permissions()->pluck('name')->all(),
        ]);

        return $this->respondSingle(
            RoleResource::make($role->load('permissions'))->toArray($request),
            'Permission untuk role "'.($role->display_name ?: $role->name).'" berhasil diperbarui.'
        );
    }
}
