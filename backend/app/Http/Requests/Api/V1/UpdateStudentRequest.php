<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update mahasiswa: penautan akun user TIDAK diizinkan — field user_*
 * sengaja tidak di-validasi sehingga diabaikan (api sudah ada relasi user_id
 * yang disiplinkan lewat store).
 */
class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:students.update
    }

    public function rules(): array
    {
        $maxEntryYear = (int) now()->year + 1;

        return [
            'study_program_id' => ['sometimes', 'required', 'integer', Rule::exists('study_programs', 'id')],
            'student_number' => [
                'sometimes', 'required', 'string', 'max:30',
                Rule::unique('students', 'student_number')->ignore($this->route('student')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'entry_year' => ['sometimes', 'required', 'integer', 'digits:4', 'min:2000', "max:{$maxEntryYear}"],
            'academic_status_id' => ['sometimes', 'nullable', 'integer', Rule::exists('academic_statuses', 'id')],
            'gender' => ['sometimes', 'required', 'string', Rule::enum(Gender::class)],
            'place_of_birth' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'photo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'father_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'mother_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'parent_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'parent_address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'guardian_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'guardian_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
