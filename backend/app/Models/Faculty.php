<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Data master: fakultas.
 * Relasi: faculties 1:N departments (§6 DATABASE_DESIGN).
 */
class Faculty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'description'];

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
