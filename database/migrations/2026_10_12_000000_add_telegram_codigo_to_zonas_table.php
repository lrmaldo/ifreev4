<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Código de un solo uso para ligar un chat de Telegram a una zona (/registrar CODIGO).
 * Antes bastaba el ID de la zona, así que cualquiera podía suscribirse a cualquier zona.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->string('telegram_codigo', 16)->nullable()->unique();
            $table->timestamp('telegram_codigo_expira_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->dropUnique(['telegram_codigo']);
            $table->dropColumn(['telegram_codigo', 'telegram_codigo_expira_at']);
        });
    }
};
