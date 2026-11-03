<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:departments.update
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['sometimes', 'required', 'integer', Rule::exists('faculties', 'id')],
            'code' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('departments', 'code')->ignore($this->route('department')),
            ],
            'name' => [
                'sometimes', 'required', 'string', 'max:150',
                Rule::unique('departments', 'name')->ignore($this->route('department')),
            ],
        ];
    }
}
