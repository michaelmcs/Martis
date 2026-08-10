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
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('unidad_id')->constrained('unidades')->cascadeOnDelete();
            $table->string('titulo');
            $table->string('tipo')->default('trabajo');
            $table->text('descripcion')->nullable();
            $table->unsignedTinyInteger('peso')->default(0);
            $table->date('fecha_limite')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};
