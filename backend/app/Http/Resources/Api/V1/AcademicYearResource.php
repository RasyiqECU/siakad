<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response tahun akademik: id, year, is_active, semesters ringan bila termuat.
 */
class AcademicYearResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'is_active' => $this->is_active,
            'semesters' => $this->whenLoaded('semesters', fn () => $this->semesters->map(fn ($semester) => [
                'id' => $semester->id,
                'name' => $semester->name,
                'is_active' => $semester->is_active,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
