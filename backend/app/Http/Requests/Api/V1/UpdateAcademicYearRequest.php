<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:academic_years.update
    }

    public function rules(): array
    {
        return [
            'year' => [
                'sometimes', 'required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('academic_years', 'year')->ignore($this->route('academic_year')),
            ],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
