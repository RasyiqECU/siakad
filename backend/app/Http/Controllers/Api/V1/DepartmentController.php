<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreDepartmentRequest;
use App\Http\Requests\Api\V1\UpdateDepartmentRequest;
use App\Http\Resources\Api\V1\DepartmentResource;
use App\Models\Department;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD data master jurusan (API_SPECIFICATION §3.3). Soft delete.
 * Filter: ?search (code/name), ?faculty_id.
 */
class DepartmentController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = Department::query()->with('faculty:id,code,name');

        $this->applySearch($query, $request->query('search'), ['code', 'name']);
        if ($facultyId = $request->query('faculty_id')) {
            $query->where('faculty_id', (int) $facultyId);
        }
        $this->applySorting($query, $request, ['code', 'name', 'faculty_id', 'created_at'], 'code');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, DepartmentResource::class, 'Daftar jurusan berhasil dimuat.');
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());
        $department->load('faculty:id,code,name');

        SimpleAuditLogger::log('department_created', $department, [
            'attributes' => ['code' => $department->code, 'name' => $department->name],
        ]);

        return $this->respondSingle(
            DepartmentResource::make($department)->toArray($request),
            'Jurusan berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Department $department): JsonResponse
    {
        $department->load('faculty:id,code,name');

        return $this->respondSingle(
            DepartmentResource::make($department)->toArray($request),
            'Detail jurusan berhasil dimuat.'
        );
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());
        $department->load('faculty:id,code,name');

        SimpleAuditLogger::log('department_updated', $department, [
            'changes' => $department->getChanges(),
        ]);

        return $this->respondSingle(
            DepartmentResource::make($department)->toArray($request),
            'Jurusan berhasil diperbarui.'
        );
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        SimpleAuditLogger::log('department_deleted', $department, [
            'attributes' => ['code' => $department->code, 'name' => $department->name],
        ]);

        return $this->respondDeleted('Jurusan berhasil dihapus.');
    }
}
