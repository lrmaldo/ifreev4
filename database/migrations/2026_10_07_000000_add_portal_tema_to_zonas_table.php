<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apariencia del portal cautivo por zona.
     */
    public function up(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->string('portal_tema', 20)->default('clasico');
            $table->string('portal_mensaje', 160)->nullable();
            $table->boolean('portal_marca_evento')->default(false);
            $table->boolean('portal_rifa')->default(false);
            $table->boolean('portal_socios')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->dropColumn(['portal_tema', 'portal_mensaje', 'portal_marca_evento', 'portal_rifa', 'portal_socios']);
        });
    }
};
