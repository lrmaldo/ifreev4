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
            // Asegurarnos de que el campo acepte 'sin_autenticacion' como valor
            if (Schema::hasColumn('zonas', 'tipo_autenticacion_mikrotik')) {
                // Actualizar registros que no tengan valor asignado - SQLite compatible
                DB::table('zonas')
                    ->whereNull('tipo_autenticacion_mikrotik')
                    ->update(['tipo_autenticacion_mikrotik' => 'pin']);

                // También actualizar los registros vacíos
                DB::table('zonas')
                    ->where('tipo_autenticacion_mikrotik', '')
                    ->update(['tipo_autenticacion_mikrotik' => 'pin']);
            } else {
                // Añadir la columna si no existe
                $table->string('tipo_autenticacion_mikrotik')->default('pin')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacemos rollback para evitar pérdida de datos
    }
};
