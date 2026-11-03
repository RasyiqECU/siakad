<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Entitas: komponen penilaian kelas (tugas_x, quiz, uts, uas, praktikum, custom).
 * SUM(weight) harus = 100 — divalidasi di GradeService (Phase 4).
 */
class GradeComponent extends Model
{
    protected $fillable = ['course_class_id', 'name', 'weight', 'max_score'];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'max_score' => 'decimal:2',
        ];
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }
}
