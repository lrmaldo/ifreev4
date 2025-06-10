<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para SQLite, necesitamos recrear la tabla para modificar una columna enum
        Schema::getConnection()->statement('PRAGMA foreign_keys=off;');

        // 1. Crear tabla temporal con la nueva definición que incluye 'login'
        Schema::create('hotspot_metrics_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->nullable();
            $table->string('mac_address')->index();
            $table->foreignId('formulario_id')->nullable();
            $table->string('dispositivo')->nullable();
            $table->string('navegador')->nullable();
            // Agregamos 'login' a los valores permitidos
            $table->enum('tipo_visual', [
                'formulario',
                'carrusel',
                'video',
                'portal_cautivo',
                'portal_entrada',
                'login'
            ])->default('formulario');
            $table->integer('duracion_visual')->default(0);
            $table->boolean('clic_boton')->default(false);
            $table->integer('veces_entradas')->default(1);
            $table->timestamps();

            // Índices con nombres únicos
            $table->index(['zona_id', 'mac_address'], 'hm_zona_mac_idx');
            $table->index(['created_at'], 'hm_created_at_idx');
            $table->index(['tipo_visual'], 'hm_tipo_visual_idx');
            $table->index(['veces_entradas'], 'hm_veces_entradas_idx');
        });

        // 2. Copiar los datos de la tabla actual
        DB::statement('INSERT INTO hotspot_metrics_new SELECT * FROM hotspot_metrics');

        // 3. Eliminar la tabla actual
        Schema::drop('hotspot_metrics');

        // 4. Renombrar la nueva tabla
        Schema::rename('hotspot_metrics_new', 'hotspot_metrics');

        // 5. Recrear las restricciones de clave foránea
        Schema::table('hotspot_metrics', function (Blueprint $table) {
            $table->foreign('zona_id')->references('id')->on('zonas')->onDelete('set null');
            $table->foreign('formulario_id')->references('id')->on('form_responses')->onDelete('set null');
        });

        Schema::getConnection()->statement('PRAGMA foreign_keys=on;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotspot_metrics', function (Blueprint $table) {
            //
        });
    }
};
