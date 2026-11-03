<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreAcademicStatusRequest;
use App\Http\Requests\Api\V1\UpdateAcademicStatusRequest;
use App\Http\Resources\Api\V1\AcademicStatusResource;
use App\Models\AcademicStatus;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD data master status akademik mahasiswa (API_SPECIFICATION §3.3).
 */
class AcademicStatusController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = AcademicStatus::query();

        $this->applySearch($query, $request->query('search'), ['code', 'name']);
        $this->applySorting($query, $request, ['code', 'name', 'created_at'], 'code');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, AcademicStatusResource::class, 'Daftar status akademik berhasil dimuat.');
    }

    public function store(StoreAcademicStatusRequest $request): JsonResponse
    {
        $status = AcademicStatus::create($request->validated());

        SimpleAuditLogger::log('academic_status_created', $status, [
            'attributes' => ['code' => $status->code, 'name' => $status->name],
        ]);

        return $this->respondSingle(
            AcademicStatusResource::make($status)->toArray($request),
            'Status akademik berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, AcademicStatus $academicStatus): JsonResponse
    {
        return $this->respondSingle(
            AcademicStatusResource::make($academicStatus)->toArray($request),
            'Detail status akademik berhasil dimuat.'
        );
    }

    public function update(UpdateAcademicStatusRequest $request, AcademicStatus $academicStatus): JsonResponse
    {
        $academicStatus->update($request->validated());

        SimpleAuditLogger::log('academic_status_updated', $academicStatus, [
            'changes' => $academicStatus->getChanges(),
        ]);

        return $this->respondSingle(
            AcademicStatusResource::make($academicStatus)->toArray($request),
            'Status akademik berhasil diperbarui.'
        );
    }

    public function destroy(AcademicStatus $academicStatus): JsonResponse
    {
        $academicStatus->delete();

        SimpleAuditLogger::log('academic_status_deleted', $academicStatus, [
            'attributes' => ['code' => $academicStatus->code, 'name' => $academicStatus->name],
        ]);

        return $this->respondDeleted('Status akademik berhasil dihapus.');
    }
}
