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
        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
            $table->string('mac_address');
            $table->text('dispositivo')->nullable(); // user-agent completo
            $table->string('navegador')->nullable();
            $table->integer('tiempo_activo')->default(0); // segundos
            $table->boolean('formulario_completado')->default(false);
            $table->json('respuestas'); // respuestas del formulario
            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index('mac_address');
            $table->index('zona_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_responses');
    }
};
