<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: tagihan uang kuliah per mahasiswa per semester.
     */
    public function up(): void
    {
        Schema::create('tuition_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_type_id')->constrained()->cascadeOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 10)->default('unpaid'); // value App\Enums\BillStatus
            $table->timestamps();
            $table->softDeletes();

            // index: (student_id, status)
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_bills');
    }
};
