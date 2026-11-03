<?php

namespace App\Models;

use App\Enums\GradeStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas: nilai mahasiswa per kelas (pasangan 1:1 dengan study_plan_detail).
 */
class Grade extends Model
{
    protected $fillable = [
        'student_id', 'course_class_id', 'study_plan_detail_id', 'component_scores',
        'final_score', 'grade_letter', 'grade_weight', 'status', 'published_at', 'remark',
    ];

    protected function casts(): array
    {
        return [
            'component_scores' => 'array',
            'final_score' => 'decimal:2',
            'grade_weight' => 'decimal:2',
            'status' => GradeStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function studyPlanDetail()
    {
        return $this->belongsTo(StudyPlanDetail::class);
    }

    public function logs()
    {
        return $this->hasMany(GradeLog::class);
    }
}
