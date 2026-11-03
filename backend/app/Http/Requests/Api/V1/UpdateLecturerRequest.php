<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AcademicPosition;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update dosen: penautan akun user TIDAK diizinkan — user_* diabaikan.
 */
class UpdateLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:lecturers.update
    }

    public function rules(): array
    {
        return [
            'study_program_id' => ['sometimes', 'required', 'integer', Rule::exists('study_programs', 'id')],
            'lecturer_number' => [
                'sometimes', 'required', 'string', 'max:30',
                Rule::unique('lecturers', 'lecturer_number')->ignore($this->route('lecturer')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'academic_position' => ['sometimes', 'required', 'string', Rule::enum(AcademicPosition::class)],
            'employment_status' => ['sometimes', 'required', 'string', Rule::enum(EmploymentStatus::class)],
            'education_degree' => ['sometimes', 'nullable', 'string', Rule::in(['S2', 'S3'])],
            'gender' => ['sometimes', 'required', 'string', Rule::enum(Gender::class)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'photo' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
