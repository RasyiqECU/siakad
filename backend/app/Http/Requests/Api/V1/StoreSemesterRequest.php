<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:semesters.create
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            // UNIQUE (academic_year_id, name) — nama hanya ganjil/genap per tahun.
            'name' => [
                'required', 'string', 'max:10', Rule::in(['ganjil', 'genap']),
                Rule::unique('semesters')->where(function ($query) {
                    return $query->where('academic_year_id', $this->integer('academic_year_id'));
                }),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'krs_start' => ['nullable', 'date'],
            'krs_end' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * after_or_equal gagal bila field pembanding tidak ikut dikirim (PATCH parsial),
     * sehingga perbandingan tanggal diterapkan lewat withValidator.
     */
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
