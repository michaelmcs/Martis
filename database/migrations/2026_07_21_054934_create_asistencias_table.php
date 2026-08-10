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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sesion_id')->constrained('sesiones')->cascadeOnDelete();
            $table->foreignUuid('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->string('estado')->default('presente');
            $table->timestamps();

            $table->unique(['sesion_id', 'estudiante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
