<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: prasyarat mata kuliah (per item kurikulum).
 */
class CoursePrerequisite extends Model
{
    protected $fillable = ['curriculum_course_id', 'prerequisite_course_id'];

    public function curriculumCourse()
    {
        return $this->belongsTo(CurriculumCourse::class);
    }

    public function prerequisiteCourse()
    {
        return $this->belongsTo(Course::class, 'prerequisite_course_id');
    }
}
