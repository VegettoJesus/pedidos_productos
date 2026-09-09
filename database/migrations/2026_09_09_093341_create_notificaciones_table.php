<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_notificacion_id')
                  ->constrained('tipos_notificacion')
                  ->onDelete('cascade');
            $table->foreignId('creado_por')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->text('mensaje_corto')->nullable();
            $table->json('data_extra')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('boton_texto', 100)->nullable();
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->foreignId('usuario_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->foreignId('rol_id')
                  ->nullable()
                  ->constrained('roles')
                  ->onDelete('cascade');
            $table->boolean('visible')->default(true);
            $table->boolean('eliminada')->default(false);
            
            $table->timestamps();
            $table->index(['tipo_notificacion_id', 'created_at']);
            $table->index(['usuario_id', 'created_at']);
            $table->index(['rol_id', 'created_at']);
            $table->index(['fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};