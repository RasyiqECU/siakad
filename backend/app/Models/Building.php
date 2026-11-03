<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data master: gedung.
 */
class Building extends Model
{
    protected $fillable = ['code', 'name'];

    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class);
    }
}
