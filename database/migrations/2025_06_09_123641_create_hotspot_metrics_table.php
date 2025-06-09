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
        Schema::create('hotspot_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->onDelete('set null');
            $table->string('mac_address')->index();
            $table->foreignId('formulario_id')->nullable()->constrained('form_responses')->onDelete('set null');
            $table->string('dispositivo')->nullable(); // Android, iOS, Windows, etc.
            $table->string('navegador')->nullable(); // Chrome, Safari, etc.
            $table->enum('tipo_visual', ['formulario', 'carrusel', 'video']); // Qué se mostró
            $table->integer('duracion_visual')->default(0); // Segundos que estuvo en el portal
            $table->boolean('clic_boton')->default(false); // Si hizo clic en algún CTA
            $table->integer('veces_entradas')->default(1); // Número de veces que ha ingresado el dispositivo
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['zona_id', 'mac_address']);
            $table->index(['created_at']);
            $table->index(['tipo_visual']);
            $table->index(['veces_entradas']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_metrics');
    }
};
