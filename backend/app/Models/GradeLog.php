<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat perubahan nilai (koreksi pasca publish, dll).
 */
class GradeLog extends Model
{
    protected $fillable = ['grade_id', 'old_values', 'new_values', 'changed_by', 'reason'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
