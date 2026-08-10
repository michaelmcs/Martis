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
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('docente_id')->constrained('users')->cascadeOnDelete();
            $table->string('codigo')->nullable();
            $table->string('nombres');
            $table->string('correo')->nullable();
            $table->timestamps();

            $table->unique(['docente_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
