<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: mahasiswa (1:1 dengan users).
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->constrained()->restrictOnDelete();
            $table->string('student_number', 30)->unique(); // NIM
            $table->string('name', 150);
            $table->char('entry_year', 4); // angkatan
            $table->foreignId('academic_status_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gender', 1)->nullable(); // L | P (App\Enums\Gender)
            $table->string('place_of_birth', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('religion', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('father_name', 150)->nullable();
            $table->string('mother_name', 150)->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->text('parent_address')->nullable();
            $table->string('guardian_name', 150)->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // index: study_program_id, entry_year
            $table->index('study_program_id');
            $table->index('entry_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
