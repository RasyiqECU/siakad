<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\AcademicDegree;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response program studi: department + department.faculty di-eager load.
 */
class StudyProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => $this->department === null ? null : [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name' => $this->department->name,
                'faculty' => $this->when($this->department->relationLoaded('faculty') && $this->department->faculty !== null, fn () => [
                    'id' => $this->department->faculty->id,
                    'code' => $this->department->faculty->code,
                    'name' => $this->department->faculty->name,
                ]),
            ]),
            'code' => $this->code,
            'name' => $this->name,
            'degree' => $this->degree,
            'degree_label' => $this->degree?->label(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
