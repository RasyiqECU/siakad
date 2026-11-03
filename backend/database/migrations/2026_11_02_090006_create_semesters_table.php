<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data master: semester (ganjil/genap) per tahun akademik.
     * `krs_start`..`krs_end` mengatur periode pengisian KRS.
     */
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name', 10); // ganjil | genap
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->dateTime('krs_start')->nullable();
            $table->dateTime('krs_end')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
