<?php

namespace App\Models;

use App\Enums\BillStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entitas: tagihan uang kuliah per mahasiswa per semester.
 */
class TuitionBill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id', 'semester_id', 'bill_type_id', 'description',
        'amount', 'due_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => BillStatus::class,
            'due_date' => 'date',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function billType()
    {
        return $this->belongsTo(BillType::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
