<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entitas: kurikulum (per program studi + tahun akademik).
 */
class Curriculum extends Model
{
    use SoftDeletes;

    protected $table = 'curriculums';

    protected $fillable = [
        'study_program_id', 'academic_year_id', 'code', 'name',
        'total_sks', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'total_sks' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'curriculum_courses')
            ->withPivot(['semester', 'is_mandatory'])
            ->withTimestamps();
    }

    public function curriculumCourses()
    {
        return $this->hasMany(CurriculumCourse::class);
    }

    public function histories()
    {
        return $this->hasMany(CurriculumHistory::class);
    }
}
