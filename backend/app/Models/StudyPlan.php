<?php

namespace App\Models;

use App\Enums\StudyPlanStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas: KRS (rencana studi mahasiswa per semester).
 * UNIQUE (student_id, semester_id).
 */
class StudyPlan extends Model
{
    protected $fillable = [
        'student_id', 'semester_id', 'advisor_lecturer_id', 'status',
        'submitted_at', 'approved_at', 'rejection_note', 'advisor_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudyPlanStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function advisor()
    {
        return $this->belongsTo(Lecturer::class, 'advisor_lecturer_id');
    }

    public function details()
    {
        return $this->hasMany(StudyPlanDetail::class);
    }
}
