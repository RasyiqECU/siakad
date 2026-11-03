<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Unggah mata kuliah ke kurikulum (POST /curriculums/{id}/courses).
 * Duplikat & cross-program dicek di controller (pesan error spesifik).
 */
class StoreCurriculumCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:curriculums.update
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'is_mandatory' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_mandatory' => $this->boolean('is_mandatory', true),
        ]);
    }
}
