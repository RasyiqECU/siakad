<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PaymentMethod: string
{
    use HasLabel;

    case Transfer = 'transfer';
    case Tunai = 'tunai';

    const LABELS = [
        'transfer' => 'Transfer',
        'tunai' => 'Tunai',
    ];
}
