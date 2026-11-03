<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AcademicDegree;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:study_programs.update
    }

    public function rules(): array
    {
        return [
            'department_id' => ['sometimes', 'required', 'integer', Rule::exists('departments', 'id')],
            'code' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('study_programs', 'code')->ignore($this->route('study_program')),
            ],
            'name' => [
                'sometimes', 'required', 'string', 'max:150',
                Rule::unique('study_programs', 'name')->ignore($this->route('study_program')),
            ],
            'degree' => ['sometimes', 'required', 'string', Rule::enum(AcademicDegree::class)],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
