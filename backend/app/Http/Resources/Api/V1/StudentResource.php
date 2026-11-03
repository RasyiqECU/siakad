<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response mahasiswa: memuat studyProgram, academicStatus, dan info akun user (email).
 */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'is_active' => $this->user->is_active,
            ]),
            'study_program_id' => $this->study_program_id,
            'study_program' => $this->whenLoaded('studyProgram', fn () => [
                'id' => $this->studyProgram->id,
                'code' => $this->studyProgram->code,
                'name' => $this->studyProgram->name,
                'degree' => $this->studyProgram->degree,
            ]),
            'academic_status_id' => $this->academic_status_id,
            'academic_status' => $this->whenLoaded('academicStatus', fn () => $this->academicStatus === null ? null : [
                'id' => $this->academicStatus->id,
                'code' => $this->academicStatus->code,
                'name' => $this->academicStatus->name,
                'color' => $this->academicStatus->color,
            ]),
            'student_number' => $this->student_number,
            'name' => $this->name,
            'entry_year' => $this->entry_year,
            'gender' => $this->gender,
            'gender_label' => $this->gender?->label(),
            'place_of_birth' => $this->place_of_birth,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'religion' => $this->religion,
            'address' => $this->address,
            'phone' => $this->phone,
            'photo' => $this->photo,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'parent_phone' => $this->parent_phone,
            'parent_address' => $this->parent_address,
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
