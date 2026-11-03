<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: kelas kuliah — realisasi mata kuliah pada semester aktif (Kelas A, B, dst).
     */
    public function up(): void
    {
        Schema::create('course_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_course_id')->nullable()->constrained('curriculum_courses')->nullOnDelete();
            $table->string('name', 20); // kelas A / B / ...
            $table->foreignId('coordinator_lecturer_id')->constrained('lecturers')->restrictOnDelete();
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('enrolled_count')->default(0); // denormalisasi, diupdate Service
            $table->string('status', 10)->default('buka'); // value App\Enums\ClassStatus
            $table->timestamps();

            $table->unique(['semester_id', 'course_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_classes');
    }
};
