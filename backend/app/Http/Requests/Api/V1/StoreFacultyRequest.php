<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:faculties.create
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('faculties', 'code')],
            'name' => ['required', 'string', 'max:150', Rule::unique('faculties', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
