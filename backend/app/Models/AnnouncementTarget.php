<?php

namespace App\Models;

use App\Enums\AnnouncementTargetType;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: target pengumuman (all / study_program / entry_year / semester).
 */
class AnnouncementTarget extends Model
{
    protected $fillable = ['announcement_id', 'target_type', 'study_program_id', 'entry_year'];

    protected function casts(): array
    {
        return [
            'target_type' => AnnouncementTargetType::class,
            'entry_year' => 'integer',
        ];
    }

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }
}
