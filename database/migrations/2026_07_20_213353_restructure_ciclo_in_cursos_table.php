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
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('regimen')->default('semestral')->after('nombre');
            $table->unsignedSmallInteger('anio')->nullable()->after('regimen');
            $table->string('periodo')->nullable()->after('anio');
            $table->dropColumn('ciclo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('ciclo')->nullable()->after('nombre');
            $table->dropColumn(['regimen', 'anio', 'periodo']);
        });
    }
};
