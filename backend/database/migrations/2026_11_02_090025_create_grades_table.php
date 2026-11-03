<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: nilai mahasiswa per kelas (pasangan dengan study_plan_detail).
     */
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_plan_detail_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('component_scores')->nullable(); // {"tugas": 80, "uts": 75, "uas": 90}
            $table->decimal('final_score', 5, 2)->nullable();
            $table->char('grade_letter', 1)->nullable(); // A | B | C | D | E | F
            $table->decimal('grade_weight', 4, 2)->nullable(); // 4.0 / 3.0 / ...
            $table->string('status', 10)->default('draft'); // value App\Enums\GradeStatus
            $table->dateTime('published_at')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'course_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
