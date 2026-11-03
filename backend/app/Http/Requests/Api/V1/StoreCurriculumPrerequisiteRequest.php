<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah prasyarat mata kuliah (POST /curriculums/{id}/prerequisites).
 * Kepemilikan kurikulum, prasyarat diri sendiri, dan pasangan duplikat
 * dicek di controller dengan pesan error spesifik.
 */
class StoreCurriculumPrerequisiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:curriculums.update
    }

    public function rules(): array
    {
        return [
            'curriculum_course_id' => ['required', 'integer', Rule::exists('curriculum_courses', 'id')],
            'prerequisite_course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
        ];
    }
}
