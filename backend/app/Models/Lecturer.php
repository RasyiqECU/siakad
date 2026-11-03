<?php

namespace App\Models;

use App\Enums\AcademicPosition;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entitas: dosen (1:1 dengan users).
 */
class Lecturer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'study_program_id', 'lecturer_number', 'name', 'academic_position',
        'employment_status', 'education_degree', 'gender', 'phone', 'address', 'photo',
    ];

    protected function casts(): array
    {
        return [
            'academic_position' => AcademicPosition::class,
            'employment_status' => EmploymentStatus::class,
            'gender' => Gender::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function courseClassLecturers()
    {
        return $this->hasMany(CourseClassLecturer::class);
    }

    public function courseClasses()
    {
        return $this->belongsToMany(CourseClass::class, 'course_class_lecturers')
            ->withPivot('role');
    }

    /** Kelas yang diampu sebagai koordinator */
    public function coordinatedClasses()
    {
        return $this->hasMany(CourseClass::class, 'coordinator_lecturer_id');
    }

    /** KRS yang disetujui sebagai dosen wali */
    public function advisedStudyPlans()
    {
        return $this->hasMany(StudyPlan::class, 'advisor_lecturer_id');
    }
}
