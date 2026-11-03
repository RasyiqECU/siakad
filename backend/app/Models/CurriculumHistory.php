<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat perubahan kurikulum (add_course / remove_course / edit).
 */
class CurriculumHistory extends Model
{
    protected $fillable = ['curriculum_id', 'action', 'changed_by', 'detail'];

    protected function casts(): array
    {
        return [
            'detail' => 'array',
        ];
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
