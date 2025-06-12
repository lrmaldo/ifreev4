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
        Schema::create('telegram_chats', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique()->comment('ID del chat de Telegram');
            $table->string('nombre')->comment('Nombre descriptivo del grupo');
            $table->text('descripcion')->nullable()->comment('Descripción del grupo');
            $table->boolean('activo')->default(true)->comment('Si el chat está activo para recibir notificaciones');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_chats');
    }
};
