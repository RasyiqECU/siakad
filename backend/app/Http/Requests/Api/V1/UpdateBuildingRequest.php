<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:buildings.update
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('buildings', 'code')->ignore($this->route('building')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
        ];
    }
}
