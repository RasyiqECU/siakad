<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum GradeStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Published = 'published';
    case Locked = 'locked';

    const LABELS = [
        'draft' => 'Draft',
        'published' => 'Dipublikasikan',
        'locked' => 'Terkunci',
    ];
}
