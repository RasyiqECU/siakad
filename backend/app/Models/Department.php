<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Data master: jurusan (bagian dari fakultas).
 */
class Department extends Model
{
    use SoftDeletes;

    protected $fillable = ['faculty_id', 'code', 'name'];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function studyPrograms()
    {
        return $this->hasMany(StudyProgram::class);
    }
}
