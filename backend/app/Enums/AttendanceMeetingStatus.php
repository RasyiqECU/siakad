<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AttendanceMeetingStatus: string
{
    use HasLabel;

    case Terbuka = 'terbuka';
    case Closed = 'closed';

    const LABELS = [
        'terbuka' => 'Terbuka',
        'closed' => 'Ditutup',
    ];
}
