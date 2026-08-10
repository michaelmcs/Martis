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
        Schema::create('horarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia'); // 1=Lunes ... 7=Domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('aula')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
