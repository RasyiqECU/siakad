<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: item KRS (kelas yang diambil dalam rencana studi).
     */
    public function up(): void
    {
        Schema::create('study_plan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['study_plan_id', 'course_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan_details');
    }
};
