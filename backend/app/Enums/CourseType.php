<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CourseType: string
{
    use HasLabel;

    case Wajib = 'wajib';
    case Pilihan = 'pilihan';

    const LABELS = [
        'wajib' => 'Wajib',
        'pilihan' => 'Pilihan',
    ];
}
