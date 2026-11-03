<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data master: ruang kelas (teori / laboratorium / seminar).
     */
    public function up(): void
    {
        Schema::create('class_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->unsignedInteger('capacity')->default(0);
            $table->string('room_type', 15)->default('teori'); // value App\Enums\RoomType
            $table->timestamps();

            // index: building_id, code
            $table->index('building_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_rooms');
    }
};
