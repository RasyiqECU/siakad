<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response user: id, name, email, avatar, phone, is_active,
 * roles [{id, name, display_name}], created_at, deleted_at.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->whenLoaded('roles', fn () => $this->loadMissing('roles'));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'roles' => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
            ])->values(),
            'created_at' => $this->created_at?->toISOString(),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
