<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: isi kurikulum (mata kuliah yang termuat dalam kurikulum).
 */
class CurriculumCourse extends Model
{
    protected $fillable = ['curriculum_id', 'course_id', 'semester', 'is_mandatory'];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'is_mandatory' => 'boolean',
        ];
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function prerequisites()
    {
        return $this->hasMany(CoursePrerequisite::class);
    }

    public function courseClasses()
    {
        return $this->hasMany(CourseClass::class);
    }
}
