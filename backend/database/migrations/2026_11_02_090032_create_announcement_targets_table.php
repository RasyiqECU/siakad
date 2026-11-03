<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: target pengumuman (all / study_program / entry_year / semester).
     * Multiple targets per pengumuman diperbolehkan.
     */
    public function up(): void
    {
        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 20)->default('all'); // value App\Enums\AnnouncementTargetType
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('entry_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
    }
};
