<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:academic_years.create
    }

    public function rules(): array
    {
        return [
            'year' => [
                'required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('academic_years', 'year'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
