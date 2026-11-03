<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response kurikulum. Pada detail (show) memuat isi kurikulum:
 * - courses: [{id, course_id, course, semester, is_mandatory, prerequisites[]}]
 * - prerequisites: peta datar {curriculum_course_id → course prasyarat}.
 */
class CurriculumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detailLoaded = $this->relationLoaded('curriculumCourses');

        return [
            'id' => $this->id,
            'study_program_id' => $this->study_program_id,
            'study_program' => $this->whenLoaded('studyProgram', fn () => [
                'id' => $this->studyProgram->id,
                'code' => $this->studyProgram->code,
                'name' => $this->studyProgram->name,
                'degree' => $this->studyProgram->degree,
            ]),
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => [
                'id' => $this->academicYear->id,
                'year' => $this->academicYear->year,
                'is_active' => $this->academicYear->is_active,
            ]),
            'code' => $this->code,
            'name' => $this->name,
            'total_sks' => $this->total_sks,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'courses' => $this->whenLoaded('curriculumCourses', fn () => $this->curriculumCourses->map(fn ($item) => [
                'id' => $item->id,
                'curriculum_course_id' => $item->id,
                'course_id' => $item->course_id,
                'course' => $item->course,
                'semester' => $item->semester,
                'is_mandatory' => $item->is_mandatory,
                'prerequisites' => $item->relationLoaded('prerequisites') ? $item->prerequisites->map(fn ($prereq) => [
                    'curriculum_course_id' => $prereq->curriculum_course_id,
                    'prerequisite_course_id' => $prereq->prerequisite_course_id,
                    'prerequisite_course' => $prereq->prerequisiteCourse,
                ])->values() : [],
            ])->values()),
            'prerequisites' => $this->whenLoaded('curriculumCourses', fn () => $this->curriculumCourses
                ->filter(fn ($item) => $item->relationLoaded('prerequisites'))
                ->flatMap(fn ($item) => $item->prerequisites->map(fn ($prereq) => [
                    'curriculum_course_id' => $prereq->curriculum_course_id,
                    'prerequisite_course_id' => $prereq->prerequisite_course_id,
                    'course_id' => $item->course_id,
                    'prerequisite_course' => $prereq->prerequisiteCourse,
                ]))->values()),
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
