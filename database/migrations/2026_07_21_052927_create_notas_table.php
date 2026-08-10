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
        Schema::create('notas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evaluacion_id')->constrained('evaluaciones')->cascadeOnDelete();
            $table->foreignUuid('criterio_id')->constrained('criterios')->cascadeOnDelete();
            $table->foreignUuid('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->decimal('valor', 5, 2)->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->unique(['criterio_id', 'estudiante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
