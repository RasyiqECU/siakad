<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AcademicPosition;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:lecturers.create
    }

    public function rules(): array
    {
        return [
            // ---- data inti dosen
            'study_program_id' => ['required', 'integer', Rule::exists('study_programs', 'id')],
            'lecturer_number' => ['required', 'string', 'max:30', Rule::unique('lecturers', 'lecturer_number')],
            'name' => ['required', 'string', 'max:150'],
            'academic_position' => ['required', 'string', Rule::enum(AcademicPosition::class)],
            'employment_status' => ['required', 'string', Rule::enum(EmploymentStatus::class)],
            'education_degree' => ['nullable', 'string', Rule::in(['S2', 'S3'])],
            'gender' => ['required', 'string', Rule::enum(Gender::class)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'string', 'max:255'],

            // ---- akun login yang ingin dibuat & ditautkan (store saja)
            'user_email' => ['nullable', 'email', 'max:150'],
            'user_name' => ['nullable', 'string', 'max:150'],
            'user_password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
