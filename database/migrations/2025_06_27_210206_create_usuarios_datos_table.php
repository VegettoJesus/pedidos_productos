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
        Schema::create('usuarios_datos', function (Blueprint $table) {
            $table->id();
            $table->string('tipoDoc', 50);
            $table->string('numeroDoc', 20);
            $table->string('direccion', 350);
            $table->string('celular', 100);
            $table->date('fecha_nacimiento');
            $table->string('imagen', 255)->nullable();
            $table->foreignId('id_usuario')->constrained('users')->onDelete('cascade');
            $table->string('nacionalidad', 100); //NACIONALIDAD
            $table->string('distrito', 100); //DIRECCION ACTUAL
            $table->string('provincia', 100); //DIRECCION ACTUAL
            $table->string('departamento', 100); //DIRECCION ACTUAL
            $table->string('cod_postal', 6); //DIRECCION ACTUAL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios_datos');
    }
};
