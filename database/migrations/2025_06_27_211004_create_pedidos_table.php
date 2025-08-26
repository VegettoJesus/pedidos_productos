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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['Pendiente', 'Aprobado', 'Rechazado', 'En proceso', 'Entregado', 'Cancelado'])->default('Pendiente');
            //Pendiente:	Pedido recibido, esperando revisión (cliente ya envió)
            //Aprobado:	    Pedido aceptado y validado por el usuario encargado
            //Rechazado:	Pedido denegado por datos inválidos o voucher incorrecto
            //En proceso:	Pedido en preparación o coordinación de entrega
            //Entregado:	Pedido ya fue entregado al cliente
            //Cancelado:	Pedido cancelado por el cliente o por problemas
            $table->string('tipo_entrega', 50); // Ej: delivery o retiro
            // Zona geográfica
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->onDelete('restrict'); // Solo si es delivery
            $table->foreignId('provincia_id')->nullable()->constrained('provincias')->onDelete('restrict'); // Solo si es delivery
            $table->foreignId('id_distrito')->nullable()->constrained('distritos')->onDelete('restrict'); // Solo si es delivery
            $table->string('direccion_entrega', 350)->nullable(); // Solo si es delivery
            $table->string('punto_retiro', 350)->nullable();      // Solo si es retiro

            $table->decimal('costo_envio', 10, 2);
            $table->decimal('total', 10, 2);

            $table->dateTime('fecha_entrega')->nullable();
            $table->string('tipo_pago', 50); // Ej: Yape, Plin, Transferencia, Efectivo
            $table->string('imagen', 255)->nullable(); // imagen del voucher

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
