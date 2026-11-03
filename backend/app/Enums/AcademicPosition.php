<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AcademicPosition: string
{
    use HasLabel;

    case TenagaPengajar = 'Tenaga_Pengajar';
    case AsistenAhli = 'Asisten_Ahli';
    case Lektor = 'Lektor';
    case LektorKepala = 'Lektor_Kepala';
    case GuruBesar = 'Guru_Besar';

    const LABELS = [
        'Tenaga_Pengajar' => 'Tenaga Pengajar',
        'Asisten_Ahli' => 'Asisten Ahli',
        'Lektor' => 'Lektor',
        'Lektor_Kepala' => 'Lektor Kepala',
        'Guru_Besar' => 'Guru Besar',
    ];
}
