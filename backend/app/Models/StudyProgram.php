<?php

namespace App\Models;

use App\Enums\AcademicDegree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Data master: program studi.
 */
class StudyProgram extends Model
{
    use SoftDeletes;

    protected $fillable = ['department_id', 'code', 'name', 'degree', 'is_active'];

    protected function casts(): array
    {
        return [
            'degree' => AcademicDegree::class,
            'is_active' => 'boolean',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function curriculums()
    {
        return $this->hasMany(Curriculum::class);
    }

    public function billTypeAmounts()
    {
        return $this->hasMany(BillTypeAmount::class);
    }
}
