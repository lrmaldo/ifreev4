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
        Schema::table('zonas', function (Blueprint $table) {
            $table->enum('seleccion_campanas', ['aleatorio', 'prioridad'])->default('prioridad');
            $table->integer('tiempo_visualizacion')->default(15);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->dropColumn(['seleccion_campanas', 'tiempo_visualizacion']);
        });
    }
};
