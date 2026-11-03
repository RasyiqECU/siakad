<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response semester: memuat tahun akademik (year) bila eager loaded.
 */
class SemesterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => [
                'id' => $this->academicYear->id,
                'year' => $this->academicYear->year,
                'is_active' => $this->academicYear->is_active,
            ]),
            'name' => $this->name,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'krs_start' => $this->krs_start?->toISOString(),
            'krs_end' => $this->krs_end?->toISOString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
