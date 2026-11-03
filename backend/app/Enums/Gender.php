<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Gender: string
{
    use HasLabel;

    case L = 'L';
    case P = 'P';

    const LABELS = [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ];
}
