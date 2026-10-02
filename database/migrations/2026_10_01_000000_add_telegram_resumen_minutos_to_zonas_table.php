<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada cuántos minutos mandar a Telegram un resumen de la zona.
     * NULL = una notificación por cada dispositivo nuevo (comportamiento original).
     */
    public function up(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->unsignedSmallInteger('telegram_resumen_minutos')->nullable();
            $table->timestamp('telegram_ultimo_resumen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->dropColumn(['telegram_resumen_minutos', 'telegram_ultimo_resumen_at']);
        });
    }
};
