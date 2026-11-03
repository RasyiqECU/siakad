<?php

namespace App\Models;

use App\Enums\AttendanceMeetingStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas: pertemuan presensi (satu baris = satu pertemuan kelas).
 * UNIQUE (course_class_id, meeting_no).
 */
class Attendance extends Model
{
    protected $fillable = [
        'course_class_id', 'meeting_no', 'date', 'topic',
        'prepared_by', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceMeetingStatus::class,
            'date' => 'date',
            'meeting_no' => 'integer',
        ];
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(Lecturer::class, 'prepared_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(AttendanceDetail::class);
    }
}
