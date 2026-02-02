<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracion_correo', function (Blueprint $table) {
            $table->id();
            $table->string('servidor_correo', 255);
            $table->integer('puerto');
            $table->string('nombre_acceso', 255);
            $table->text('contraseña');
            $table->enum('seguridad', ['ssl', 'tls', 'ninguna']);
            $table->boolean('activo')
                  ->default(true);
            $table->timestamps();
            $table->index('activo', 'idx_correo_activo');
            $table->index('servidor_correo', 'idx_servidor_correo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_correo');
    }
};