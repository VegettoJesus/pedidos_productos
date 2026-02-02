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
        Schema::create('empresa_informacion', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 20)->unique();
            $table->string('razon_social', 255);
            $table->string('nombre_comercial', 255)->nullable();
            $table->string('propietario_nombre', 255);
            $table->string('propietario_apellido', 255);
            $table->text('direccion');
            $table->string('ubigeo', 6);
            $table->foreignId('departamento_id')->constrained('departamentos');
            $table->foreignId('provincia_id')->constrained('provincias');
            $table->foreignId('distrito_id')->constrained('distritos');
            $table->text('maps_url')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->timestamps();
            $table->index('ruc');
            $table->index('ubigeo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_informacion');
    }
};