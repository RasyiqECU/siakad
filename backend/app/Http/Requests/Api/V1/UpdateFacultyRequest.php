<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:faculties.update
    }

    public function rules(): array
    {
        return [
            // unique abaikan record yang sedang diedit & record yang sudah dihapus lunak.
            'code' => [
                'sometimes', 'required', 'string', 'max:10',
                Rule::unique('faculties', 'code')->ignore($this->route('faculty')),
            ],
            'name' => [
                'sometimes', 'required', 'string', 'max:150',
                Rule::unique('faculties', 'name')->ignore($this->route('faculty')),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
