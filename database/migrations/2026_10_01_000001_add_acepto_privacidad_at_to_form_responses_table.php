<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha en que el usuario aceptó el aviso de privacidad en el portal (prueba del consentimiento).
     */
    public function up(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->timestamp('acepto_privacidad_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->dropColumn('acepto_privacidad_at');
        });
    }
};
