<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan sistem: key-value jsonb per group.
 * Contoh kunci: app.name, academic.max_sks.default, academic.sks.by_gpa, grade.scale, krs.period.
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
