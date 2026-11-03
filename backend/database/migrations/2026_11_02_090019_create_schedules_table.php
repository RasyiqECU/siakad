<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: jadwal kelas (hari, waktu, ruang).
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->string('day', 10); // value App\Enums\ScheduleDay
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // index: (class_room_id, day)
            $table->index(['class_room_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
