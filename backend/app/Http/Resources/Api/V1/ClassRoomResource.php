<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response ruang kelas: memuat gedung (kamus kecil) bila eager loaded.
 */
class ClassRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'building_id' => $this->building_id,
            'building' => $this->whenLoaded('building', fn () => [
                'id' => $this->building->id,
                'code' => $this->building->code,
                'name' => $this->building->name,
            ]),
            'code' => $this->code,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'room_type' => $this->room_type,
            'room_type_label' => $this->room_type?->label(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
