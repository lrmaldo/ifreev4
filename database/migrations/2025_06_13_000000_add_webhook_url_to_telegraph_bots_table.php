<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookUrlToTelegraphBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Verificar si la tabla telegraph_bots existe
        if (Schema::hasTable('telegraph_bots')) {
            // Verificar si la columna webhook_url NO existe antes de agregarla
            if (!Schema::hasColumn('telegraph_bots', 'webhook_url')) {
                Schema::table('telegraph_bots', function (Blueprint $table) {
                    $table->string('webhook_url')->nullable()->after('token');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('telegraph_bots')) {
            if (Schema::hasColumn('telegraph_bots', 'webhook_url')) {
                Schema::table('telegraph_bots', function (Blueprint $table) {
                    $table->dropColumn('webhook_url');
                });
            }
        }
    }
}
