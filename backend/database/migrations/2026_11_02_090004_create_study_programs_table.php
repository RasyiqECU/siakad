<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data master: program studi (D3/S1/... per jurusan).
     * `degree` memakai enum App\Enums\AcademicDegree (disimpan varchar).
     */
    public function up(): void
    {
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150)->unique();
            $table->string('degree', 3)->default('S1'); // value App\Enums\AcademicDegree
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // index: department_id
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_programs');
    }
};
