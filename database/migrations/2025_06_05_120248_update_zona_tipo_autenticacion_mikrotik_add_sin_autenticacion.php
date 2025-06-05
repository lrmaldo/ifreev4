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
        // Para SQLite, necesitamos recrear la tabla para modificar una columna enum
        // Primero creamos una copia temporal de la tabla con la nueva definición
        Schema::getConnection()->statement('PRAGMA foreign_keys=off;');

        // 1. Crear tabla temporal con estructura idéntica a la original pero con el enum modificado
        Schema::create('zonas_temp', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('id_analytics')->nullable();
            $table->foreignId('user_id'); // No agregamos la restricción de clave foránea ahora
            $table->integer('segundos')->default(15);
            $table->enum('tipo_registro', ['formulario', 'redes', 'sin_registro'])->default('formulario');
            $table->boolean('login_sin_registro')->default(true);

            // Aquí definimos la columna con los tres valores posibles
            $table->enum('tipo_autenticacion_mikrotik', ['pin', 'usuario_password', 'sin_autenticacion'])->default('pin');

            $table->text('script_head')->nullable();
            $table->text('script_body')->nullable();
            $table->timestamps();
            $table->string('id_personalizado')->nullable();
            $table->enum('seleccion_campanas', ['aleatorio', 'prioridad'])->default('prioridad');
            $table->integer('tiempo_visualizacion')->default(15);
        });

        // 2. Copiar los datos de la tabla original a la temporal
        DB::statement('INSERT INTO zonas_temp SELECT * FROM zonas');

        // 3. Eliminar la tabla original
        Schema::drop('zonas');

        // 4. Renombrar la tabla temporal a la original
        Schema::rename('zonas_temp', 'zonas');

        // 5. Recrear las restricciones de clave foránea
        Schema::table('zonas', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::getConnection()->statement('PRAGMA foreign_keys=on;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacemos rollback de la columna enum para evitar pérdida de datos
        // Si fuera necesario, podríamos revertir los valores 'sin_autenticacion' a 'pin'
        DB::table('zonas')
            ->where('tipo_autenticacion_mikrotik', 'sin_autenticacion')
            ->update(['tipo_autenticacion_mikrotik' => 'pin']);
    }
};
