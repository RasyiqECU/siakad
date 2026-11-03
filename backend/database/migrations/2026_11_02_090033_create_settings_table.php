<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan sistem (key-value, jsonb).
     * Contoh: app.name, academic.max_sks.default, academic.sks.by_gpa, grade.scale, krs.period.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->jsonb('value')->nullable();
            $table->string('type', 30)->default('string'); // string | integer | boolean | json
            $table->string('group', 50)->index(); // app | academic | grade | krs | institution
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
