<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreFacultyRequest;
use App\Http\Requests\Api\V1\UpdateFacultyRequest;
use App\Http\Resources\Api\V1\FacultyResource;
use App\Models\Faculty;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD data master fakultas (API_SPECIFICATION §3.3). Soft delete.
 */
class FacultyController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = Faculty::query()
            ->withCount('departments');

        $this->applySearch($query, $request->query('search'), ['code', 'name']);
        $this->applySorting($query, $request, ['code', 'name', 'created_at'], 'code');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, FacultyResource::class, 'Daftar fakultas berhasil dimuat.');
    }

    public function store(StoreFacultyRequest $request): JsonResponse
    {
        $faculty = Faculty::create($request->validated());

        SimpleAuditLogger::log('faculty_created', $faculty, [
            'attributes' => ['code' => $faculty->code, 'name' => $faculty->name],
        ]);

        return $this->respondSingle(
            FacultyResource::make($faculty)->toArray($request),
            'Fakultas berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Faculty $faculty): JsonResponse
    {
        $faculty->loadCount('departments');

        return $this->respondSingle(
            FacultyResource::make($faculty)->toArray($request),
            'Detail fakultas berhasil dimuat.'
        );
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty): JsonResponse
    {
        $faculty->update($request->validated());

        SimpleAuditLogger::log('faculty_updated', $faculty, [
            'changes' => $faculty->getChanges(),
        ]);

        return $this->respondSingle(
            FacultyResource::make($faculty->loadCount('departments'))->toArray($request),
            'Fakultas berhasil diperbarui.'
        );
    }

    public function destroy(Faculty $faculty): JsonResponse
    {
        $faculty->delete();

        SimpleAuditLogger::log('faculty_deleted', $faculty, [
            'attributes' => ['code' => $faculty->code, 'name' => $faculty->name],
        ]);

        return $this->respondDeleted('Fakultas berhasil dihapus.');
    }
}
