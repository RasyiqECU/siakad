<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: mata kuliah (katalog, bukan kelas).
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->unsignedTinyInteger('sks')->default(2); // 2..6
            $table->unsignedTinyInteger('semester')->default(1); // 1..14
            $table->string('course_type', 10)->default('wajib'); // value App\Enums\CourseType
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // index: (study_program_id, code), name
            $table->index(['study_program_id', 'code']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
