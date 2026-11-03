<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: nominal tagihan per jenis tagihan + program studi + tahun akademik.
 */
class BillTypeAmount extends Model
{
    protected $fillable = ['bill_type_id', 'study_program_id', 'academic_year_id', 'amount'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function billType()
    {
        return $this->belongsTo(BillType::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
