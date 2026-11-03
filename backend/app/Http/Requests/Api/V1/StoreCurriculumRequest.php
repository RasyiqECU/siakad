<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:curriculums.create
    }

    public function rules(): array
    {
        return [
            'study_program_id' => ['required', 'integer', Rule::exists('study_programs', 'id')],
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            'code' => ['required', 'string', 'max:30', Rule::unique('curriculums', 'code')],
            'name' => ['required', 'string', 'max:150'],
            // total_sks dihitung sistem dari mata kuliah termuat — bukan input klien.
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
