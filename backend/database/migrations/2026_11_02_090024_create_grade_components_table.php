<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: komponen penilaian kelas (tugas, quiz, UTS, UAS, ...).
     * SUM(weight) harus = 100 — divalidasi di Service.
     */
    public function up(): void
    {
        Schema::create('grade_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50); // tugas_x | quiz | uts | uas | praktikum | custom
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['course_class_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_components');
    }
};
