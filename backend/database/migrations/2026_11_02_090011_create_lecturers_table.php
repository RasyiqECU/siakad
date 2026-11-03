<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: dosen (1:1 dengan users).
     */
    public function up(): void
    {
        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->constrained()->restrictOnDelete();
            $table->string('lecturer_number', 30)->unique(); // NIDN
            $table->string('name', 150);
            $table->string('academic_position', 30)->nullable(); // value App\Enums\AcademicPosition
            $table->string('employment_status', 10)->nullable(); // value App\Enums\EmploymentStatus
            $table->string('education_degree', 20)->nullable(); // S2 / S3
            $table->string('gender', 1)->nullable(); // L | P (App\Enums\Gender)
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('photo', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // index: lecturer_number
            $table->index('lecturer_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturers');
    }
};
