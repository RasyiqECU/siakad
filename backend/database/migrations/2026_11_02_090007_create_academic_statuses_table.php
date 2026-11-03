<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data master: status akademik mahasiswa (Baru, Aktif, Cuti, Lulus, Keluar, DO).
     */
    public function up(): void
    {
        Schema::create('academic_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('color', 7)->nullable(); // hex #rrggbb
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_statuses');
    }
};
