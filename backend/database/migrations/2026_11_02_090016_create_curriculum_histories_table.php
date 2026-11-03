<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat perubahan kurikulum (add_course / remove_course / edit).
     */
    public function up(): void
    {
        Schema::create('curriculum_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->string('action', 20); // add_course | remove_course | edit
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('detail')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_histories');
    }
};
