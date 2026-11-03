<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum StudyPlanStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revision = 'revision';

    const LABELS = [
        'draft' => 'Draft',
        'pending' => 'Menunggu Persetujuan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'revision' => 'Perlu Revisi',
    ];
}
