<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreCourseRequest;
use App\Http\Requests\Api\V1\UpdateCourseRequest;
use App\Http\Resources\Api\V1\CourseResource;
use App\Models\Course;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD mata kuliah (API_SPECIFICATION §3.3). Soft delete.
 * Filter: ?search (code/name), ?study_program_id, ?course_type, ?semester.
 */
class CourseController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = Course::query()->with('studyProgram:id,code,name,degree');

        $this->applySearch($query, $request->query('search'), ['code', 'name']);
        if ($studyProgramId = $request->query('study_program_id')) {
            $query->where('study_program_id', (int) $studyProgramId);
        }
        if ($courseType = $request->query('course_type')) {
            $query->where('course_type', $courseType);
        }
        if ($semester = $request->query('semester')) {
            $query->where('semester', (int) $semester);
        }
        $this->applySorting($query, $request, ['code', 'name', 'sks', 'semester', 'study_program_id', 'created_at'], 'code');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, CourseResource::class, 'Daftar mata kuliah berhasil dimuat.');
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = Course::create($request->validated());
        $course->load('studyProgram:id,code,name,degree');

        SimpleAuditLogger::log('course_created', $course, [
            'attributes' => ['code' => $course->code, 'name' => $course->name, 'sks' => $course->sks],
        ]);

        return $this->respondSingle(
            CourseResource::make($course)->toArray($request),
            'Mata kuliah berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        $course->load('studyProgram:id,code,name,degree');

        return $this->respondSingle(
            CourseResource::make($course)->toArray($request),
            'Detail mata kuliah berhasil dimuat.'
        );
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course->update($request->validated());
        $course->load('studyProgram:id,code,name,degree');

        SimpleAuditLogger::log('course_updated', $course, [
            'changes' => $course->getChanges(),
        ]);

        return $this->respondSingle(
            CourseResource::make($course)->toArray($request),
            'Mata kuliah berhasil diperbarui.'
        );
    }

    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        SimpleAuditLogger::log('course_deleted', $course, [
            'attributes' => ['code' => $course->code, 'name' => $course->name],
        ]);

        return $this->respondDeleted('Mata kuliah berhasil dihapus.');
    }
}
