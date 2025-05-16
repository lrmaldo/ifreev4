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
        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('id_analytics')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('segundos')->default(15);
            $table->enum('tipo_registro', ['formulario', 'redes', 'sin_registro'])->default('formulario');
            $table->boolean('login_sin_registro')->default(true);
            $table->enum('tipo_autenticacion_mikrotik', ['pin', 'usuario_password'])->default('pin');
            $table->text('script_head')->nullable();
            $table->text('script_body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zona');
    }
};
