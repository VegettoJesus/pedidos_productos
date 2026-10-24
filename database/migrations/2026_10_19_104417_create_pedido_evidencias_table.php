<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->enum('tipo_evidencia', [
                'empaquetado',        // Foto del paquete embalado
                'guia_envio',         // Foto de la guía de la agencia
                'en_transito',        // Foto del paquete en camino
                'entrega_cliente',    // Foto del cliente recibiendo
                'dni_receptor',       // Foto del DNI de quien recibe
                'firma_receptor',     // Foto de la firma
                'incidencia',         // Paquete dañado, extraviado, etc.
                'devolucion',         // Evidencia de devolución
                'otro',               // Fallback
            ])->default('otro');

            $table->string('archivo_path', 500);   // Ruta 
            $table->string('nombre_original', 255)->nullable(); // Nombre al subir
            $table->string('mime_type', 100)->nullable();       // image/jpeg, application/pdf
            $table->unsignedInteger('tamano_bytes')->nullable();
            $table->string('estado_envio_momento', 50)->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('capturada_en')->nullable();
            $table->text('descripcion')->nullable();   // "Paquete embalado con cinta roja"
            $table->boolean('visible_cliente')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pedido_id', 'tipo_evidencia']);
            $table->index(['pedido_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_evidencias');
    }
};