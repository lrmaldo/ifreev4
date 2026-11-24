<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes de demo que llegan desde las páginas públicas de resultados (/casos/{slug}).
 * Es la primera pieza del módulo de prospectos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_demo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('empresa', 150);
            $table->string('telefono', 20);
            $table->string('correo', 150);
            $table->string('interes', 30); // isp | evento | expositor | otro
            $table->text('mensaje')->nullable();
            $table->string('origen', 80)->nullable(); // slug de la página de la que vino
            $table->string('estado', 20)->default('nueva'); // nueva | contactada | descartada
            $table->timestamps();

            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_demo');
    }
};
