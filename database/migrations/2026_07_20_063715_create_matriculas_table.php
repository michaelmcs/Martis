<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignUuid('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->string('estado')->default('activo');
            $table->timestamps();

            $table->unique(['curso_id', 'estudiante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
