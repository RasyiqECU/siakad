<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data master: tahun akademik (format `2025/2026`).
 * Constraint aplikasi: hanya satu baris is_active = true (dipastikan di Service).
 */
class AcademicYear extends Model
{
    protected $fillable = ['year', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    public function curriculums()
    {
        return $this->hasMany(Curriculum::class);
    }

    public function billTypeAmounts()
    {
        return $this->hasMany(BillTypeAmount::class);
    }
}
