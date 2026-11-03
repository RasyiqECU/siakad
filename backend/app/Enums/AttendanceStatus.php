<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AttendanceStatus: string
{
    use HasLabel;

    case H = 'H';
    case I = 'I';
    case S = 'S';
    case A = 'A';

    const LABELS = [
        'H' => 'Hadir',
        'I' => 'Izin',
        'S' => 'Sakit',
        'A' => 'Alpa',
    ];
}
