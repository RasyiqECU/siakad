<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data master: jenis tagihan keuangan (UKT, Wisuda, Laboratorium, ...).
 */
class BillType extends Model
{
    protected $fillable = ['code', 'name', 'default_amount'];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
        ];
    }

    public function amounts()
    {
        return $this->hasMany(BillTypeAmount::class);
    }
}
