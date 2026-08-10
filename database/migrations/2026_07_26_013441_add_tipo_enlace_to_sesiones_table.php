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
        Schema::table('sesiones', function (Blueprint $table) {
            $table->string('tipo')->default('clase')->after('fecha'); // clase, virtual, feriado, toma_local, parada, actividad
            $table->string('enlace')->nullable()->after('tema');       // Meet/Zoom para clases virtuales
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesiones', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'enlace']);
        });
    }
};
