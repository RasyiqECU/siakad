<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:students.create
    }

    public function rules(): array
    {
        $maxEntryYear = (int) now()->year + 1;

        return [
            // ---- data inti mahasiswa
            'study_program_id' => ['required', 'integer', Rule::exists('study_programs', 'id')],
            'student_number' => ['required', 'string', 'max:30', Rule::unique('students', 'student_number')],
            'name' => ['required', 'string', 'max:150'],
            'entry_year' => ['required', 'integer', 'digits:4', 'min:2000', "max:{$maxEntryYear}"],
            'academic_status_id' => ['nullable', 'integer', Rule::exists('academic_statuses', 'id')],
            'gender' => ['required', 'string', Rule::enum(Gender::class)],
            'place_of_birth' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'religion' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'string', 'max:255'],

            // ---- orang tua / wali
            'father_name' => ['nullable', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'parent_address' => ['nullable', 'string', 'max:2000'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // ---- akun login yang ingin dibuat & ditautkan (store saja; tidak ada re-link saat update)
            'user_email' => ['nullable', 'email', 'max:150'],
            'user_name' => ['nullable', 'string', 'max:150'],
            'user_password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
