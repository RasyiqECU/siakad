<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:curriculums.update
    }

    public function rules(): array
    {
        return [
            'study_program_id' => ['sometimes', 'required', 'integer', Rule::exists('study_programs', 'id')],
            'academic_year_id' => ['sometimes', 'required', 'integer', Rule::exists('academic_years', 'id')],
            'code' => [
                'sometimes', 'required', 'string', 'max:30',
                Rule::unique('curriculums', 'code')->ignore($this->route('curriculum')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            // total_sks dihitung sistem (di-recompute setelah attach/detach mata kuliah).
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
