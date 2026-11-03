<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AcademicDegree: string
{
    use HasLabel;

    case D3 = 'D3';
    case D4 = 'D4';
    case S1 = 'S1';
    case S2 = 'S2';
    case S3 = 'S3';

    const LABELS = [
        'D3' => 'Diploma 3 (D3)',
        'D4' => 'Diploma 4 (D4)',
        'S1' => 'Sarjana (S1)',
        'S2' => 'Magister (S2)',
        'S3' => 'Doktor (S3)',
    ];
}
