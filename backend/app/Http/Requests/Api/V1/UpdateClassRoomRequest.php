<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:class_rooms.update
    }

    public function rules(): array
    {
        return [
            'building_id' => ['sometimes', 'required', 'integer', Rule::exists('buildings', 'id')],
            'code' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('class_rooms', 'code')->ignore($this->route('class_room')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'room_type' => ['sometimes', 'required', 'string', Rule::enum(RoomType::class)],
        ];
    }
}
