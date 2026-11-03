<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum RoomType: string
{
    use HasLabel;

    case Teori = 'teori';
    case Laboratorium = 'laboratorium';
    case Seminar = 'seminar';

    const LABELS = [
        'teori' => 'Ruang Teori',
        'laboratorium' => 'Laboratorium',
        'seminar' => 'Ruang Seminar',
    ];
}
