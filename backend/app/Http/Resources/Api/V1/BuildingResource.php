<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response gedung + jumlah ruang (bila dihitung).
 */
class BuildingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'class_rooms_count' => $this->whenCounted('classRooms'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
