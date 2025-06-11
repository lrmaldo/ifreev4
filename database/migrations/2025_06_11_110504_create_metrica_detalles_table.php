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
        Schema::create('metrica_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metrica_id')->constrained('hotspot_metrics')->onDelete('cascade');
            $table->string('tipo_evento')->comment('clic, vista, formulario, etc');
            $table->string('contenido')->nullable()->comment('identificador del botón, formulario, etc');
            $table->string('detalle', 500)->nullable()->comment('información adicional del evento');
            $table->timestamp('fecha_hora');
            $table->timestamps();

            // Índices para mejorar rendimiento en consultas
            $table->index('tipo_evento');
            $table->index('fecha_hora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrica_detalles');
    }
};
