<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ClassStatus: string
{
    use HasLabel;

    case Buka = 'buka';
    case Tutup = 'tutup';

    const LABELS = [
        'buka' => 'Buka',
        'tutup' => 'Tutup',
    ];
}
