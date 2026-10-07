<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El título de la campaña es opcional (el portal lo oculta si está vacío).
     */
    public function up(): void
    {
        Schema::table('campanas', function (Blueprint $table) {
            $table->string('titulo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('campanas', function (Blueprint $table) {
            $table->string('titulo')->nullable(false)->default('')->change();
        });
    }
};
