<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: item KRS (kelas yang diambil dalam rencana studi).
 */
class StudyPlanDetail extends Model
{
    protected $fillable = ['study_plan_id', 'course_class_id'];

    public function studyPlan()
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function grade()
    {
        return $this->hasOne(Grade::class);
    }
}
