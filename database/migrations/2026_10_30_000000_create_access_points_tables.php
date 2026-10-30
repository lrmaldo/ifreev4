<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Access points que se consultan por SNMP (aps:sondear) y su historial por minuto.
     */
    public function up(): void
    {
        Schema::create('access_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete();
            $table->string('nombre');
            $table->string('host');
            $table->unsignedInteger('puerto')->default(161);
            $table->string('community')->default('public');
            $table->boolean('activo')->default(true);

            // Última lectura
            $table->boolean('en_linea')->nullable();
            $table->string('modelo')->nullable();
            $table->unsignedInteger('clientes')->nullable();
            $table->json('macs')->nullable();
            $table->unsignedBigInteger('uptime_seg')->nullable();
            $table->unsignedBigInteger('bajada_bps')->nullable();
            $table->unsignedBigInteger('subida_bps')->nullable();
            $table->json('contadores')->nullable();
            $table->string('ultimo_error')->nullable();
            $table->timestamp('ultima_respuesta_at')->nullable();
            $table->timestamp('ultimo_sondeo_at')->nullable();
            $table->timestamps();
        });

        Schema::create('access_point_lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_point_id')->constrained('access_points')->cascadeOnDelete();
            $table->unsignedInteger('clientes')->nullable();
            $table->unsignedBigInteger('bajada_bps')->nullable();
            $table->unsignedBigInteger('subida_bps')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['access_point_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_point_lecturas');
        Schema::dropIfExists('access_points');
    }
};
