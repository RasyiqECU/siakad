<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:departments.create
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')],
            'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')],
            'name' => ['required', 'string', 'max:150', Rule::unique('departments', 'name')],
        ];
    }
}
