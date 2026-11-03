<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas: pembayaran (bukti upload oleh mahasiswa, validasi oleh keuangan).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_bill_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('payment_date');
            $table->string('method', 10)->default('transfer'); // value App\Enums\PaymentMethod
            $table->string('reference_no', 100)->unique()->nullable();
            $table->string('proof_path', 255)->nullable(); // storage/app/public/payment_proofs
            $table->string('status', 10)->default('pending'); // value App\Enums\PaymentStatus
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // index: (tuition_bill_id, status)
            $table->index(['tuition_bill_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
