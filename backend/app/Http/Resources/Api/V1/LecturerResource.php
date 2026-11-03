<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response dosen: memuat studyProgram dan info akun user (email).
 */
class LecturerResource extends JsonResource
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
            'lecturer_number' => $this->lecturer_number,
            'name' => $this->name,
            'academic_position' => $this->academic_position,
            'academic_position_label' => $this->academic_position?->label(),
            'employment_status' => $this->employment_status,
            'employment_status_label' => $this->employment_status?->label(),
            'education_degree' => $this->education_degree,
            'gender' => $this->gender,
            'gender_label' => $this->gender?->label(),
            'phone' => $this->phone,
            'address' => $this->address,
            'photo' => $this->photo,
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
