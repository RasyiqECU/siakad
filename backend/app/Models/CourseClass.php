<?php

namespace App\Models;

use App\Enums\ClassStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas: kelas kuliah — realisasi mata kuliah pada semester (Kelas A/B/...).
 */
class CourseClass extends Model
{
    protected $fillable = [
        'semester_id', 'course_id', 'curriculum_course_id', 'name',
        'coordinator_lecturer_id', 'capacity', 'enrolled_count', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClassStatus::class,
            'capacity' => 'integer',
            'enrolled_count' => 'integer',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function curriculumCourse()
    {
        return $this->belongsTo(CurriculumCourse::class);
    }

    public function coordinator()
    {
        return $this->belongsTo(Lecturer::class, 'coordinator_lecturer_id');
    }

    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'course_class_lecturers')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function courseClassLecturers()
    {
        return $this->hasMany(CourseClassLecturer::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function gradeComponents()
    {
        return $this->hasMany(GradeComponent::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function studyPlanDetails()
    {
        return $this->hasMany(StudyPlanDetail::class);
    }

    /**
     * Mahasiswa yang mengambil kelas ini — via study_plan_details + study_plans.
     */
    public function students()
    {
        $studyPlanIds = StudyPlanDetail::query()
            ->where('course_class_id', $this->getKey())
            ->select('study_plan_id');

        return Student::query()->whereIn('id', StudyPlan::query()
            ->whereIn('id', $studyPlanIds)
            ->select('student_id'));
    }
}
