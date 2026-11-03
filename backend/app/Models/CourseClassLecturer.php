<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: dosen pengampu kelas (koordinator / anggota).
 */
class CourseClassLecturer extends Model
{
    protected $fillable = ['course_class_id', 'lecturer_id', 'role'];

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }
}
