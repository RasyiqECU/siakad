<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: pertemuan presensi (satu baris = satu pertemuan kelas).
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('meeting_no');
            $table->date('date');
            $table->text('topic')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->string('status', 10)->default('terbuka'); // value App\Enums\AttendanceMeetingStatus
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_class_id', 'meeting_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
