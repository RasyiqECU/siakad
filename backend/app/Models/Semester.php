<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data master: semester (ganjil / genap) per tahun akademik.
 * krs_start..krs_end mengatur periode pengisian KRS.
 */
class Semester extends Model
{
    protected $fillable = [
        'academic_year_id', 'name', 'start_date', 'end_date',
        'krs_start', 'krs_end', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'krs_start' => 'datetime',
            'krs_end' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function courseClasses()
    {
        return $this->hasMany(CourseClass::class);
    }

    public function studyPlans()
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function tuitionBills()
    {
        return $this->hasMany(TuitionBill::class);
    }
}
