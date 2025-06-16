<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateSeleccionCampanasEnum extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para MySQL, usamos ALTER TABLE directamente
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE zonas MODIFY seleccion_campanas ENUM('aleatorio', 'prioridad', 'video', 'imagen') DEFAULT 'aleatorio'");
        }
        // Para SQLite o PostgreSQL, primero creamos la columna temporal
        else {
            // Añadir una columna temporal
            Schema::table('zonas', function (Blueprint $table) {
                $table->string('seleccion_campanas_temp')->default('aleatorio');
            });

            // Copiar los datos
            DB::table('zonas')->update([
                'seleccion_campanas_temp' => DB::raw('seleccion_campanas')
            ]);

            // Eliminar la columna original
            Schema::table('zonas', function (Blueprint $table) {
                $table->dropColumn('seleccion_campanas');
            });

            // Añadir la columna con la nueva enumeración
            Schema::table('zonas', function (Blueprint $table) {
                $table->enum('seleccion_campanas', ['aleatorio', 'prioridad', 'video', 'imagen'])->default('aleatorio');
            });

            // Restaurar los datos
            DB::table('zonas')->update([
                'seleccion_campanas' => DB::raw('seleccion_campanas_temp')
            ]);

            // Eliminar la columna temporal
            Schema::table('zonas', function (Blueprint $table) {
                $table->dropColumn('seleccion_campanas_temp');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Para MySQL, usamos ALTER TABLE directamente
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE zonas MODIFY seleccion_campanas ENUM('aleatorio', 'prioridad') DEFAULT 'prioridad'");
        }
        // Para SQLite o PostgreSQL, usamos el mismo procedimiento que arriba pero a la inversa
        else {
            // Añadir una columna temporal
            Schema::table('zonas', function (Blueprint $table) {
                $table->string('seleccion_campanas_temp')->default('prioridad');
            });

            // Copiar los datos (convertir 'video' e 'imagen' a 'aleatorio')
            DB::table('zonas')
                ->whereIn('seleccion_campanas', ['aleatorio', 'prioridad'])
                ->update([
                    'seleccion_campanas_temp' => DB::raw('seleccion_campanas')
                ]);

            // Convertir 'video' e 'imagen' a 'aleatorio'
            DB::table('zonas')
                ->whereIn('seleccion_campanas', ['video', 'imagen'])
                ->update([
                    'seleccion_campanas_temp' => 'aleatorio'
                ]);

            // Eliminar la columna original
            Schema::table('zonas', function (Blueprint $table) {
                $table->dropColumn('seleccion_campanas');
            });

            // Añadir la columna con la nueva enumeración
            Schema::table('zonas', function (Blueprint $table) {
                $table->enum('seleccion_campanas', ['aleatorio', 'prioridad'])->default('prioridad');
            });

            // Restaurar los datos
            DB::table('zonas')->update([
                'seleccion_campanas' => DB::raw('seleccion_campanas_temp')
            ]);

            // Eliminar la columna temporal
            Schema::table('zonas', function (Blueprint $table) {
                $table->dropColumn('seleccion_campanas_temp');            });
        }
    }
}
