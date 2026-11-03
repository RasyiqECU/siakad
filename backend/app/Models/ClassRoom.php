<?php

namespace App\Models;

use App\Enums\RoomType;
use Illuminate\Database\Eloquent\Model;

/**
 * Data master: ruang kelas (teori / laboratorium / seminar).
 */
class ClassRoom extends Model
{
    protected $fillable = ['building_id', 'code', 'name', 'capacity', 'room_type'];

    protected function casts(): array
    {
        return [
            'room_type' => RoomType::class,
            'capacity' => 'integer',
        ];
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
