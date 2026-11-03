<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entitas: mahasiswa (1:1 dengan users).
 * Riwayat akademik diambil dari study_plans/grades/tuition_bills (tanpa duplikasi).
 */
class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'study_program_id', 'student_number', 'name', 'entry_year',
        'academic_status_id', 'gender', 'place_of_birth', 'date_of_birth', 'religion',
        'address', 'phone', 'photo', 'father_name', 'mother_name', 'parent_phone',
        'parent_address', 'guardian_name', 'guardian_phone', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'date_of_birth' => 'date',
            'entry_year' => 'integer',
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

    public function academicStatus()
    {
        return $this->belongsTo(AcademicStatus::class);
    }

    public function studyPlans()
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function tuitionBills()
    {
        return $this->hasMany(TuitionBill::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function attendanceDetails()
    {
        return $this->hasMany(AttendanceDetail::class);
    }
}
