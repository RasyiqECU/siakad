<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: detail kehadiran mahasiswa pada satu pertemuan.
     */
    public function up(): void
    {
        Schema::create('attendances_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status', 1)->default('A'); // value App\Enums\AttendanceStatus (H | I | S | A)
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['attendance_id', 'student_id']);

            // index: student_id
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances_details');
    }
};
