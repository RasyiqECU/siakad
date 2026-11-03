<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: nominal tagihan per jenis tagihan + program studi + tahun akademik.
     */
    public function up(): void
    {
        Schema::create('bill_type_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['bill_type_id', 'study_program_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_type_amounts');
    }
};
