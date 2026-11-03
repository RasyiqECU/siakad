<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\RespondsWithEnvelope;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Daftar audit log (API_SPECIFICATION §3.2).
 * Filter: ?user_id, ?action. Guard permission: users.read.
 */
class AuditLogController extends Controller
{
    use RespondsWithEnvelope;

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', '15'), 100));

        $query = AuditLog::query()->with('user')->orderByDesc('id');

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage)->appends($request->query());

        return $this->respondPaginated($paginator, AuditLogResource::class, 'Daftar audit log berhasil dimuat.');
    }
}
