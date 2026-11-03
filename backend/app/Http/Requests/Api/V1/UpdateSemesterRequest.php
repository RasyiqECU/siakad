<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:semesters.update
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['sometimes', 'required', 'integer', Rule::exists('academic_years', 'id')],
            'name' => [
                'sometimes', 'required', 'string', 'max:10', Rule::in(['ganjil', 'genap']),
                Rule::unique('semesters')->where(function ($query) {
                    return $query->where('academic_year_id', $this->input('academic_year_id', $this->route('semester')?->academic_year_id));
                })->ignore($this->route('semester')),
            ],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'krs_start' => ['sometimes', 'nullable', 'date'],
            'krs_end' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $pairs = [
                ['end_date', 'start_date', 'end_date harus >= start_date.'],
                ['krs_end', 'krs_start', 'krs_end harus >= krs_start.'],
            ];

            foreach ($pairs as [$end, $start, $message]) {
                $endValue = $this->input($end);
                $startValue = $this->input($start);

                if ($startValue && $endValue && strtotime((string) $endValue) < strtotime((string) $startValue)) {
                    $v->errors()->add($end, $message);
                }
            }
        });
    }
}
