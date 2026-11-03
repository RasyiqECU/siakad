<?php

namespace App\Models;

use App\Enums\CourseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entitas: mata kuliah (katalog; realisasi berjalan pada course_classes).
 */
class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'study_program_id', 'code', 'name', 'sks', 'semester',
        'course_type', 'description',
    ];

    protected function casts(): array
    {
        return [
            'course_type' => CourseType::class,
            'sks' => 'integer',
            'semester' => 'integer',
        ];
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function courseClasses()
    {
        return $this->hasMany(CourseClass::class);
    }

    public function curriculumCourses()
    {
        return $this->hasMany(CurriculumCourse::class);
    }

    /**
     * Mata kuliah prasyarat — diambil via curriculum_courses dan course_prerequisites.
     */
    public function prerequisiteCourses()
    {
        return Course::query()->whereIn('id', CoursePrerequisite::query()
            ->whereIn('curriculum_course_id', $this->curriculumCourses()->select('id'))
            ->select('prerequisite_course_id'));
    }
}
