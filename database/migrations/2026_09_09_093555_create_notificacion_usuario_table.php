<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacion_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notificacion_id')->constrained('notificaciones')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_en')->nullable();
            $table->timestamp('fecha_visualizacion')->nullable();
            $table->timestamps();

            $table->index(['notificacion_id', 'usuario_id']);
            $table->index(['usuario_id', 'leida']);
            $table->unique(['notificacion_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion_usuario');
    }
};