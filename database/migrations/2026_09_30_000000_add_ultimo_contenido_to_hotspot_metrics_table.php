<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Último contenido (video/imagen) mostrado a esa MAC en la zona, para alternar
     * campañas sin depender de la sesión ni de cookies.
     */
    public function up(): void
    {
        Schema::table('hotspot_metrics', function (Blueprint $table) {
            $table->string('ultimo_contenido', 10)->nullable()->after('tipo_visual');
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_metrics', function (Blueprint $table) {
            $table->dropColumn('ultimo_contenido');
        });
    }
};
