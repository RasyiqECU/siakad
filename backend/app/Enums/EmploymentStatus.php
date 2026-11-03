<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EmploymentStatus: string
{
    use HasLabel;

    case Tetap = 'tetap';
    case NonTetap = 'non_tetap';

    const LABELS = [
        'tetap' => 'Tetap',
        'non_tetap' => 'Tidak Tetap',
    ];
}
