<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreCurriculumCourseRequest;
use App\Http\Requests\Api\V1\StoreCurriculumPrerequisiteRequest;
use App\Http\Requests\Api\V1\StoreCurriculumRequest;
use App\Http\Requests\Api\V1\UpdateCurriculumRequest;
use App\Http\Resources\Api\V1\CurriculumResource;
use App\Models\Course;
use App\Models\CoursePrerequisite;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD kurikulum + endpoint nested (API_SPECIFICATION §3.3):
 * - POST/DELETE /curriculums/{id}/courses          → isi kurikulum (unique + recompute total_sks)
 * - POST/DELETE /curriculums/{id}/prerequisites    → prasyarat mata kuliah
 * Semua aksi nested guard permission curriculums.update. total_sks selalu
 * dihistung ulang di dalam DB::transaction setelah attach/detach.
 */
class CurriculumController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = Curriculum::query()->with('studyProgram:id,code,name,degree', 'academicYear:id,year,is_active');

        $this->applySearch($query, $request->query('search'), ['code', 'name']);
        if ($studyProgramId = $request->query('study_program_id')) {
            $query->where('study_program_id', (int) $studyProgramId);
        }
        if ($academicYearId = $request->query('academic_year_id')) {
            $query->where('academic_year_id', (int) $academicYearId);
        }
        if ($request->query('is_active') !== null) {
            $query->where('is_active', (bool) $request->query('is_active', '0'));
        }
        $this->applySorting($query, $request, ['code', 'name', 'total_sks', 'study_program_id', 'academic_year_id', 'created_at'], 'code');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, CurriculumResource::class, 'Daftar kurikulum berhasil dimuat.');
    }

    public function store(StoreCurriculumRequest $request): JsonResponse
    {
        $curriculum = DB::transaction(function () use ($request) {
            /** @var Curriculum $curriculum */
            $curriculum = Curriculum::create($request->validated());
            $this->recomputeTotalSks($curriculum); // total_sks = 0 saat kurikulum baru

            return $curriculum;
        });

        SimpleAuditLogger::log('curriculum_created', $curriculum, [
            'attributes' => ['code' => $curriculum->code, 'name' => $curriculum->name],
        ]);

        return $this->respondSingle(
            CurriculumResource::make($curriculum->loadMissing('studyProgram', 'academicYear'))->toArray($request),
            'Kurikulum berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Curriculum $curriculum): JsonResponse
    {
        $curriculum->load([
            'studyProgram', 'academicYear',
            'curriculumCourses.course', 'curriculumCourses.prerequisites.prerequisiteCourse',
        ]);

        return $this->respondSingle(
            CurriculumResource::make($curriculum)->toArray($request),
            'Detail kurikulum berhasil dimuat.'
        );
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum): JsonResponse
    {
        $curriculum->update($request->validated());

        SimpleAuditLogger::log('curriculum_updated', $curriculum, [
            'changes' => $curriculum->getChanges(),
        ]);

        return $this->respondSingle(
            CurriculumResource::make($curriculum->loadMissing('studyProgram', 'academicYear'))->toArray($request),
            'Kurikulum berhasil diperbarui.'
        );
    }

    public function destroy(Curriculum $curriculum): JsonResponse
    {
        $curriculum->delete();

        SimpleAuditLogger::log('curriculum_deleted', $curriculum, [
            'attributes' => ['code' => $curriculum->code, 'name' => $curriculum->name],
        ]);

        return $this->respondDeleted('Kurikulum berhasil dihapus.');
    }

    // ------------------------------------------------------------------
    // Nested: isi kurikulum (mata kuliah)
    // ------------------------------------------------------------------

    public function attachCourse(StoreCurriculumCourseRequest $request, Curriculum $curriculum): JsonResponse
    {
        $validated = $request->validated();

        $course = Course::findOrFail($validated['course_id']);

        // Prinsip: mata kuliah wajib satu program studi dengan kurikulumnya.
        if ((int) $course->study_program_id !== (int) $curriculum->study_program_id) {
            return $this->respondError("Mata kuliah '{$course->code}' tidak termasuk program studi kurikulum ini.", 422);
        }

        $duplicate = CurriculumCourse::query()
            ->where('curriculum_id', $curriculum->id)
            ->where('course_id', $course->id)
            ->exists();
        if ($duplicate) {
            return $this->respondError("Mata kuliah '{$course->code}' sudah terdaftar di kurikulum ini.", 422);
        }

        [$item, $totalSks] = DB::transaction(function () use ($curriculum, $validated) {
            $item = $curriculum->curriculumCourses()->create($validated);
            $totalSks = $this->recomputeTotalSks($curriculum);

            return [$item, $totalSks];
        });

        SimpleAuditLogger::log('curriculum_course_attached', $curriculum, [
            'curriculum_course_id' => $item->id,
            'course_id' => $item->course_id,
            'course_code' => $course->code,
            'semester' => $item->semester,
            'is_mandatory' => $item->is_mandatory,
            'total_sks' => $totalSks,
        ]);

        return $this->respondSingle([
            'curriculum_id' => $curriculum->id,
            'curriculum_course_id' => $item->id,
            'course_id' => $item->course_id,
            'semester' => $item->semester,
            'is_mandatory' => $item->is_mandatory,
            'total_sks' => $totalSks,
        ], 'Mata kuliah berhasil ditambahkan ke kurikulum.', 201);
    }

    public function detachCourse(Curriculum $curriculum, int $courseId): JsonResponse
    {
        $item = CurriculumCourse::query()
            ->where('curriculum_id', $curriculum->id)
            ->where('course_id', $courseId)
            ->first();

        if ($item === null) {
            return $this->respondError('Mata kuliah tersebut tidak termuat dalam kurikulum ini.', 404);
        }

        [$courseIdRemoved, $totalSks] = DB::transaction(function () use ($curriculum, $item) {
            $courseIdRemoved = $item->course_id;
            $item->delete(); // prasyarat terkait ikut terhapus (FK cascade)
            $totalSks = $this->recomputeTotalSks($curriculum);

            return [$courseIdRemoved, $totalSks];
        });

        SimpleAuditLogger::log('curriculum_course_detached', $curriculum, [
            'course_id' => $courseIdRemoved,
            'total_sks' => $totalSks,
        ]);

        return $this->respondSingle([
            'curriculum_id' => $curriculum->id,
            'removed_course_id' => $courseIdRemoved,
            'total_sks' => $totalSks,
        ], 'Mata kuliah berhasil dihapus dari kurikulum.');
    }

    // ------------------------------------------------------------------
    // Nested: prasyarat mata kuliah
    // ------------------------------------------------------------------

    public function attachPrerequisite(StoreCurriculumPrerequisiteRequest $request, Curriculum $curriculum): JsonResponse
    {
        $validated = $request->validated();

        /** @var CurriculumCourse|null $item */
        $item = CurriculumCourse::query()
            ->where('id', $validated['curriculum_course_id'])
            ->where('curriculum_id', $curriculum->id)
            ->first();
        if ($item === null) {
            return $this->respondError('Item kurikulum (curriculum_course_id) tidak termuat dalam kurikulum ini.', 422);
        }

        $prerequisiteCourseId = (int) $validated['prerequisite_course_id'];

        // Mata kuliah prasyarat harus juga termuat dalam kurikulum yang sama.
        $prerequisiteInCurriculum = CurriculumCourse::query()
            ->where('curriculum_id', $curriculum->id)
            ->where('course_id', $prerequisiteCourseId)
            ->exists();
        if (! $prerequisiteInCurriculum) {
            return $this->respondError('Mata kuliah prasyarat tidak termuat dalam kurikulum ini.', 422);
        }

        if ((int) $item->course_id === $prerequisiteCourseId) {
            return $this->respondError('Mata kuliah tidak dapat menjadi prasyarat dirinya sendiri.', 422);
        }

        $duplicate = CoursePrerequisite::query()
            ->where('curriculum_course_id', $item->id)
            ->where('prerequisite_course_id', $prerequisiteCourseId)
            ->exists();
        if ($duplicate) {
            return $this->respondError('Pasangan prasyarat tersebut sudah terdaftar.', 422);
        }

        $prerequisite = CoursePrerequisite::create([
            'curriculum_course_id' => $item->id,
            'prerequisite_course_id' => $prerequisiteCourseId,
        ]);

        SimpleAuditLogger::log('curriculum_prerequisite_attached', $curriculum, [
            'curriculum_course_id' => $item->id,
            'prerequisite_course_id' => $prerequisiteCourseId,
        ]);

        return $this->respondSingle([
            'id' => $prerequisite->id,
            'curriculum_course_id' => $prerequisite->curriculum_course_id,
            'prerequisite_course_id' => $prerequisite->prerequisite_course_id,
        ], 'Prasyarat mata kuliah berhasil ditambahkan.', 201);
    }

    public function detachPrerequisite(Curriculum $curriculum, int $prerequisiteCourseId): JsonResponse
    {
        // Hapus prasyarat untuk kurikulum ini yang berasal dari mata kuliah prasyarat tsb.
        $removed = CoursePrerequisite::query()
            ->where('prerequisite_course_id', $prerequisiteCourseId)
            ->whereHas('curriculumCourse', fn ($q) => $q->where('curriculum_id', $curriculum->id))
            ->get();

        if ($removed->isEmpty()) {
            return $this->respondError('Prasyarat tersebut tidak termuat dalam kurikulum ini.', 404);
        }

        CoursePrerequisite::whereIn('id', $removed->pluck('id'))->delete();

        SimpleAuditLogger::log('curriculum_prerequisite_detached', $curriculum, [
            'prerequisite_course_id' => $prerequisiteCourseId,
            'removed' => $removed->count(),
        ]);

        return $this->respondSingle([
            'curriculum_id' => $curriculum->id,
            'removed_prerequisite_course_id' => $prerequisiteCourseId,
            'removed' => $removed->count(),
        ], 'Prasyarat mata kuliah berhasil dihapus.');
    }

    /**
     * Hitung ulang total_sks = jumlah sks mata kuliah yang termuat,
     * dipanggil di dalam DB::transaction pemanggil.
     */
    private function recomputeTotalSks(Curriculum $curriculum): int
    {
        $total = (int) CurriculumCourse::query()
            ->where('curriculum_id', $curriculum->id)
            ->join('courses', 'courses.id', '=', 'curriculum_courses.course_id')
            ->sum('courses.sks');

        $curriculum->forceFill(['total_sks' => $total])->save();

        return $total;
    }
}
