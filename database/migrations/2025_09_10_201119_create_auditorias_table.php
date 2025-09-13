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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Quién hizo la acción
            $table->string('accion'); // Ejemplo: "Eliminar Producto", "Actualizar Rol"
            $table->string('tabla_afectada')->nullable(); // Ejemplo: "productos", "roles"
            $table->unsignedBigInteger('registro_id')->nullable(); // ID del registro afectado
            $table->text('descripcion')->nullable(); // Texto libre con detalles
            $table->ipAddress('ip')->nullable(); // IP del usuario
            $table->string('navegador')->nullable(); // Agregar info del navegador/dispositivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
