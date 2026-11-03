<?php

namespace App\Models;

use App\Enums\ScheduleDay;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas: jadwal kelas ( hari, waktu, ruang ).
 */
class Schedule extends Model
{
    protected $fillable = ['course_class_id', 'day', 'start_time', 'end_time', 'class_room_id'];

    protected function casts(): array
    {
        return [
            'day' => ScheduleDay::class,
        ];
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }
}
