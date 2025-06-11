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
        Schema::table('hotspot_metrics', function (Blueprint $table) {
            $table->string('sistema_operativo')->nullable()->after('navegador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotspot_metrics', function (Blueprint $table) {
            $table->dropColumn('sistema_operativo');
        });
    }
};
