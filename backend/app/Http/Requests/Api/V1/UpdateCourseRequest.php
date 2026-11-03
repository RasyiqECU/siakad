<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CourseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:courses.update
    }

    public function rules(): array
    {
        return [
            'study_program_id' => ['sometimes', 'required', 'integer', Rule::exists('study_programs', 'id')],
            'code' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('courses', 'code')->ignore($this->route('course')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'sks' => ['sometimes', 'required', 'integer', 'min:2', 'max:6'],
            'semester' => ['sometimes', 'required', 'integer', 'min:1', 'max:14'],
            'course_type' => ['sometimes', 'required', 'string', Rule::enum(CourseType::class)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
