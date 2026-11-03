<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: detail kehadiran mahasiswa pada satu pertemuan.
 */
class AttendanceDetail extends Model
{
    protected $fillable = ['attendance_id', 'student_id', 'status', 'note'];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
