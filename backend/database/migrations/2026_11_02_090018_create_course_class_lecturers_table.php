<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: dosen pengampu kelas (koordinator / anggota).
     */
    public function up(): void
    {
        Schema::create('course_class_lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
            $table->string('role', 15)->default('anggota'); // koordinator | anggota
            $table->timestamps();

            $table->unique(['course_class_id', 'lecturer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_class_lecturers');
    }
};
