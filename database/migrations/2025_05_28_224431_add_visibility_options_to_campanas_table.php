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
        Schema::table('campanas', function (Blueprint $table) {
            $table->boolean('siempre_visible')->default(false)->after('visible');
            $table->json('dias_visibles')->nullable()->after('siempre_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campanas', function (Blueprint $table) {
            $table->dropColumn(['siempre_visible', 'dias_visibles']);
        });
    }
};
