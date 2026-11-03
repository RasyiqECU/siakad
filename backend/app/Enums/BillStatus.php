<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BillStatus: string
{
    use HasLabel;

    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    const LABELS = [
        'unpaid' => 'Belum Dibayar',
        'partial' => 'Dibayar Sebagian',
        'paid' => 'Lunas',
        'cancelled' => 'Dibatalkan',
    ];
}
