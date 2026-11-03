<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:class_rooms.create
    }

    public function rules(): array
    {
        return [
            'building_id' => ['required', 'integer', Rule::exists('buildings', 'id')],
            'code' => ['required', 'string', 'max:20', Rule::unique('class_rooms', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'capacity' => ['required', 'integer', 'min:1'],
            'room_type' => ['required', 'string', Rule::enum(RoomType::class)],
        ];
    }
}
