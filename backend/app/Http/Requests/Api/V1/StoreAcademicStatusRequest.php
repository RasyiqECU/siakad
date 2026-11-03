<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:academic_statuses.create
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('academic_statuses', 'code')],
            'name' => ['required', 'string', 'max:100'],
            // Format warna hex: #RRGGBB atau #RGB.
            'color' => ['nullable', 'string', 'regex:/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$/'],
        ];
    }
}
