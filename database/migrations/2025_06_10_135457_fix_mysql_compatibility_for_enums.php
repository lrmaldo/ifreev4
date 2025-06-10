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
        // Solo ejecutar en MySQL, no en SQLite
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Verificar si la tabla zonas existe y necesita actualizarse
            if (Schema::hasTable('zonas') && Schema::hasColumn('zonas', 'tipo_autenticacion_mikrotik')) {
                // Actualizar ENUM de zonas para incluir 'sin_autenticacion'
                try {
                    DB::statement("ALTER TABLE zonas MODIFY tipo_autenticacion_mikrotik ENUM('pin', 'usuario_password', 'sin_autenticacion') DEFAULT 'pin'");
                } catch (\Exception $e) {
                    // Si falla, probablemente ya está actualizado
                }
            }

            // Verificar si la tabla hotspot_metrics existe y necesita actualizarse
            if (Schema::hasTable('hotspot_metrics') && Schema::hasColumn('hotspot_metrics', 'tipo_visual')) {
                // Actualizar ENUM de hotspot_metrics para incluir los nuevos valores
                try {
                    DB::statement("ALTER TABLE hotspot_metrics MODIFY tipo_visual ENUM('formulario', 'carrusel', 'video', 'portal_cautivo', 'portal_entrada', 'login') DEFAULT 'formulario'");
                } catch (\Exception $e) {
                    // Si falla, probablemente ya está actualizado
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir solo en MySQL
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Revertir zona
            if (Schema::hasTable('zonas') && Schema::hasColumn('zonas', 'tipo_autenticacion_mikrotik')) {
                DB::statement("ALTER TABLE zonas MODIFY tipo_autenticacion_mikrotik ENUM('pin', 'usuario_password') DEFAULT 'pin'");
            }

            // Revertir hotspot_metrics
            if (Schema::hasTable('hotspot_metrics') && Schema::hasColumn('hotspot_metrics', 'tipo_visual')) {
                DB::statement("ALTER TABLE hotspot_metrics MODIFY tipo_visual ENUM('formulario', 'carrusel', 'video') DEFAULT 'formulario'");
            }
        }
    }
};
