<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CourseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:courses.create
    }

    public function rules(): array
    {
        return [
            'study_program_id' => ['required', 'integer', Rule::exists('study_programs', 'id')],
            'code' => ['required', 'string', 'max:20', Rule::unique('courses', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:2', 'max:6'],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'course_type' => ['required', 'string', Rule::enum(CourseType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
