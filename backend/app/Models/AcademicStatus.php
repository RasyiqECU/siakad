<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data master: status akademik mahasiswa (Baru, Aktif, Cuti, Lulus, Keluar, DO).
 */
class AcademicStatus extends Model
{
    protected $fillable = ['code', 'name', 'color'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
