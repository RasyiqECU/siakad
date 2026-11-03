<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response fakultas: id, code, name, description + department count ringan.
 */
class FacultyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'departments_count' => $this->whenCounted('departments'),
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
