<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response mata kuliah: memuat nama study program bila eager loaded.
 */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'study_program_id' => $this->study_program_id,
            'study_program' => $this->whenLoaded('studyProgram', fn () => [
                'id' => $this->studyProgram->id,
                'code' => $this->studyProgram->code,
                'name' => $this->studyProgram->name,
                'degree' => $this->studyProgram->degree,
            ]),
            'code' => $this->code,
            'name' => $this->name,
            'sks' => $this->sks,
            'semester' => $this->semester,
            'course_type' => $this->course_type,
            'course_type_label' => $this->course_type?->label(),
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
