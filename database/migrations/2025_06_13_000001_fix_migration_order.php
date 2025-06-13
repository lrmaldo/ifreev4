<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Esta migración no hace nada, solo registra un mensaje
        echo "Esta migración es un marcador para corregir problemas con el orden de las migraciones.\n";
        echo "Corregir manualmente el orden de las migraciones:\n";
        echo "1. Cambie la fecha de la migración add_sistema_operativo_to_hotspot_metrics.php\n";
        echo "   para que sea posterior a la creación de la tabla hotspot_metrics\n";
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */    public function down()
    {
        // No hay nada que deshacer
    }
};
