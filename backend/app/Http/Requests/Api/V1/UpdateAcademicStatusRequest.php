<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:academic_statuses.update
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('academic_statuses', 'code')->ignore($this->route('academic_status')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['sometimes', 'nullable', 'string', 'regex:/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$/'],
        ];
    }
}
