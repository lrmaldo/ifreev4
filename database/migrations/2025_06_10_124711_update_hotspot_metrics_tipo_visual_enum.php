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
        // Verificar el motor de base de datos
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Para MySQL, podemos modificar el ENUM directamente
            DB::statement("ALTER TABLE hotspot_metrics MODIFY tipo_visual ENUM('formulario', 'carrusel', 'video', 'portal_cautivo', 'portal_entrada', 'login') DEFAULT 'formulario'");
        } elseif ($driver === 'sqlite') {
            // Para SQLite, necesitamos recrear la tabla para modificar una columna enum
            Schema::getConnection()->statement('PRAGMA foreign_keys=off;');

        // 1. Crear tabla temporal con la nueva definición
        Schema::create('hotspot_metrics_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->nullable();
            $table->string('mac_address')->index();
            $table->foreignId('formulario_id')->nullable();
            $table->string('dispositivo')->nullable();
            $table->string('navegador')->nullable();
            // Agregamos todos los valores posibles que se usan en el código
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

            // Índices
            $table->index(['zona_id', 'mac_address']);
            $table->index(['created_at']);
            $table->index(['tipo_visual']);
            $table->index(['veces_entradas']);
        });

        // 2. Copiar los datos de la tabla original a la temporal
        DB::statement('INSERT INTO hotspot_metrics_temp SELECT * FROM hotspot_metrics');

        // 3. Eliminar la tabla original
        Schema::drop('hotspot_metrics');

        // 4. Renombrar la tabla temporal a la original
        Schema::rename('hotspot_metrics_temp', 'hotspot_metrics');

        // 5. Recrear las restricciones de clave foránea
        Schema::table('hotspot_metrics', function (Blueprint $table) {
            $table->foreign('zona_id')->references('id')->on('zonas')->onDelete('set null');
            $table->foreign('formulario_id')->references('id')->on('form_responses')->onDelete('set null');
        });

        Schema::getConnection()->statement('PRAGMA foreign_keys=on;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verificar el motor de base de datos
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Para MySQL, revertir el ENUM a los valores originales
            DB::statement("ALTER TABLE hotspot_metrics MODIFY tipo_visual ENUM('formulario', 'carrusel', 'video') DEFAULT 'formulario'");

            // Actualizar registros que tengan valores no válidos
            DB::table('hotspot_metrics')
                ->whereIn('tipo_visual', ['portal_cautivo', 'portal_entrada', 'login'])
                ->update(['tipo_visual' => 'formulario']);

        } elseif ($driver === 'sqlite') {
            // Para SQLite, recrear la tabla con los valores originales
            Schema::getConnection()->statement('PRAGMA foreign_keys=off;');

        Schema::create('hotspot_metrics_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->nullable();
            $table->string('mac_address')->index();
            $table->foreignId('formulario_id')->nullable();
            $table->string('dispositivo')->nullable();
            $table->string('navegador')->nullable();
            $table->enum('tipo_visual', ['formulario', 'carrusel', 'video']); // Valores originales
            $table->integer('duracion_visual')->default(0);
            $table->boolean('clic_boton')->default(false);
            $table->integer('veces_entradas')->default(1);
            $table->timestamps();

            $table->index(['zona_id', 'mac_address']);
            $table->index(['created_at']);
            $table->index(['tipo_visual']);
            $table->index(['veces_entradas']);
        });

        // Copiar datos (solo los que tengan valores válidos)
        DB::statement("INSERT INTO hotspot_metrics_temp SELECT * FROM hotspot_metrics WHERE tipo_visual IN ('formulario', 'carrusel', 'video')");

        Schema::drop('hotspot_metrics');
        Schema::rename('hotspot_metrics_temp', 'hotspot_metrics');

        Schema::table('hotspot_metrics', function (Blueprint $table) {
            $table->foreign('zona_id')->references('id')->on('zonas')->onDelete('set null');
            $table->foreign('formulario_id')->references('id')->on('form_responses')->onDelete('set null');
        });

        Schema::getConnection()->statement('PRAGMA foreign_keys=on;');
        }
    }
};
