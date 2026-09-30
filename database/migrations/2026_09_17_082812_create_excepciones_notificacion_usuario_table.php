<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excepciones_notificacion_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tipo_notificacion_id')->constrained('tipos_notificacion')->onDelete('cascade');
            $table->enum('tipo_excepcion', ['permitir', 'denegar']);
            
            $table->text('motivo')->nullable();  
            $table->foreignId('creado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['usuario_id', 'tipo_notificacion_id'], 'exc_user_tipo_unique');
            $table->index(['usuario_id', 'tipo_excepcion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excepciones_notificacion_usuario');
    }
};