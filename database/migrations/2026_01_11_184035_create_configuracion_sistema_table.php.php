<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configuracion_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('titulo_site', 255)->nullable();
            $table->string('abreviatura_titulo', 50)->nullable();
            $table->text('descripcion_corta')->nullable();
            $table->string('icono_site', 255)->nullable();
            $table->string('email_admin', 255)->nullable();
            $table->text('footer_text')->nullable();
            $table->unsignedInteger('max_entradas_home')->default(12);
            $table->timestamp('fecha_actualizacion')->useCurrent();
            $table->index('email_admin', 'idx_email_admin');
        });

    }
    
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('configuracion_sistema');
    }
};