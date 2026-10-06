<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pantalla en vivo por zona (link secreto) y ganadores de la rifa.
     */
    public function up(): void
    {
        Schema::table('zonas', function (Blueprint $table) {
            $table->string('pantalla_token', 64)->nullable()->unique();
        });

        Schema::create('rifa_ganadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
            $table->foreignId('form_response_id')->constrained('form_responses')->onDelete('cascade');
            $table->string('nombre');
            $table->string('telefono_final', 4);
            $table->string('premio')->nullable();
            $table->timestamps();
            $table->unique(['zona_id', 'form_response_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rifa_ganadores');

        Schema::table('zonas', function (Blueprint $table) {
            $table->dropUnique(['pantalla_token']);
            $table->dropColumn('pantalla_token');
        });
    }
};
