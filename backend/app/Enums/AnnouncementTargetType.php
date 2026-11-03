<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AnnouncementTargetType: string
{
    use HasLabel;

    case All = 'all';
    case StudyProgram = 'study_program';
    case EntryYear = 'entry_year';
    case Semester = 'semester';

    const LABELS = [
        'all' => 'Semua',
        'study_program' => 'Program Studi',
        'entry_year' => 'Angkatan',
        'semester' => 'Semester',
    ];
}
