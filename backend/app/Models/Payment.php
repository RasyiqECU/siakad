<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas: pembayaran (bukti upload oleh mahasiswa, validasi oleh keuangan).
 */
class Payment extends Model
{
    protected $fillable = [
        'tuition_bill_id', 'amount', 'payment_date', 'method', 'reference_no',
        'proof_path', 'status', 'verified_by', 'verified_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'payment_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function tuitionBill()
    {
        return $this->belongsTo(TuitionBill::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
