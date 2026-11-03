<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreStudyProgramRequest;
use App\Http\Requests\Api\V1\UpdateStudyProgramRequest;
use App\Http\Resources\Api\V1\StudyProgramResource;
use App\Models\StudyProgram;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD data master program studi (API_SPECIFICATION §3.3). Soft delete.
 * Filter: ?search (code/name), ?faculty_id, ?department_id, ?degree, ?is_active.
 * Resource selalu memuat department beserta faculty.
 */
class StudyProgramController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = StudyProgram::query()->with('department.faculty:id,code,name');

        $this->applySearch($query, $request->query('search'), ['code', 'name']);
        if ($departmentId = $request->query('department_id')) {
            $query->where('department_id', (int) $departmentId);
        }
        if ($facultyId = $request->query('faculty_id')) {
            $query->whereHas('department', fn ($q) => $q->where('faculty_id', (int) $facultyId));
        }
        if ($degree = $request->query('degree')) {
            $query->where('degree', $degree);
        }
        if ($request->query('is_active') !== null) {
            $query->where('is_active', (bool) $request->query('is_active', '0'));
        }
        $this->applySorting($query, $request, ['code', 'name', 'degree', 'department_id', 'created_at'], 'code');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, StudyProgramResource::class, 'Daftar program studi berhasil dimuat.');
    }

    public function store(StoreStudyProgramRequest $request): JsonResponse
    {
        $studyProgram = StudyProgram::create($request->validated());
        $studyProgram->load('department.faculty:id,code,name');

        SimpleAuditLogger::log('study_program_created', $studyProgram, [
            'attributes' => ['code' => $studyProgram->code, 'name' => $studyProgram->name],
            'degree' => $studyProgram->degree->value,
        ]);

        return $this->respondSingle(
            StudyProgramResource::make($studyProgram)->toArray($request),
            'Program studi berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, StudyProgram $studyProgram): JsonResponse
    {
        $studyProgram->load('department.faculty:id,code,name');

        return $this->respondSingle(
            StudyProgramResource::make($studyProgram)->toArray($request),
            'Detail program studi berhasil dimuat.'
        );
    }

    public function update(UpdateStudyProgramRequest $request, StudyProgram $studyProgram): JsonResponse
    {
        $studyProgram->update($request->validated());
        $studyProgram->load('department.faculty:id,code,name');

        SimpleAuditLogger::log('study_program_updated', $studyProgram, [
            'changes' => $studyProgram->getChanges(),
        ]);

        return $this->respondSingle(
            StudyProgramResource::make($studyProgram)->toArray($request),
            'Program studi berhasil diperbarui.'
        );
    }

    public function destroy(StudyProgram $studyProgram): JsonResponse
    {
        $studyProgram->delete();

        SimpleAuditLogger::log('study_program_deleted', $studyProgram, [
            'attributes' => ['code' => $studyProgram->code, 'name' => $studyProgram->name],
        ]);

        return $this->respondDeleted('Program studi berhasil dihapus.');
    }
}
