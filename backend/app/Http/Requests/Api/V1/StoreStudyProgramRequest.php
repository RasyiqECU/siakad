<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AcademicDegree;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:study_programs.create
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'code' => ['required', 'string', 'max:20', Rule::unique('study_programs', 'code')],
            'name' => ['required', 'string', 'max:150', Rule::unique('study_programs', 'name')],
            'degree' => ['required', 'string', Rule::enum(AcademicDegree::class)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
