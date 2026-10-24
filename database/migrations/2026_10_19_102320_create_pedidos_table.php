<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique(); // Ej: PED-2025-000123
            $table->string('numero_orden', 30)->nullable()->index(); // Correlativo interno
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('cliente_nombres', 150);
            $table->string('cliente_apellidos', 150);
            $table->string('cliente_email', 250);
            $table->string('cliente_celular', 20)->nullable();
            $table->string('cliente_documento_tipo', 20)->nullable(); 
            $table->string('cliente_documento_numero', 20)->nullable();
            $table->enum('tipo_entrega', ['delivery', 'retiro'])->default('delivery');
            $table->string('punto_retiro', 255)->nullable(); 
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->foreignId('provincia_id')->nullable()->constrained('provincias')->nullOnDelete();
            $table->foreignId('distrito_id')->nullable()->constrained('distritos')->nullOnDelete();
            $table->string('departamento_nombre', 100)->nullable();
            $table->string('provincia_nombre', 100)->nullable();
            $table->string('distrito_nombre', 100)->nullable();
            $table->string('direccion_entrega', 500)->nullable();
            $table->string('referencia_entrega', 500)->nullable();
            $table->foreignId('metodo_pago_id')->nullable()->constrained('metodos_pago')->nullOnDelete();
            $table->string('metodo_pago_slug', 50);        // contra_entrega, transferencia, qr
            $table->string('metodo_pago_nombre', 100);     // "Pago contra entrega"
            $table->json('metodo_pago_config_snapshot')->nullable(); // Configuración al momento
            $table->string('voucher_path', 500)->nullable();
            $table->timestamp('voucher_subido_en')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);        
            $table->decimal('costo_envio', 10, 2)->default(0);     
            $table->decimal('impuesto_envio', 10, 2)->default(0);
            $table->decimal('cargo_metodo_pago', 10, 2)->default(0); 
            $table->decimal('impuesto_cargo', 10, 2)->default(0);  // IGV del cargo
            $table->decimal('descuento_total', 10, 2)->default(0); // Cupones/descuentos globales
            $table->decimal('total', 12, 2)->default(0);           
            $table->string('moneda', 3)->default('PEN');
            $table->enum('estado', [
                'pendiente',       // Recién creado, sin pagar
                'pago_en_revision',// Subió voucher, esperando validación
                'pagado',          // Pago confirmado por admin
                'en_preparacion',  // Alistando productos
                'enviado',         // En camino
                'entregado',       // Cliente recibió
                'cancelado',       // Cancelado (stock devuelto)
                'reembolsado',     // Devuelto dinero
                'rechazado',       // Pago rechazado
            ])->default('pendiente')->index();

            $table->enum('estado_pago', [
                'no_pagado',
                'pendiente_verificacion',
                'pagado',
                'parcial',
                'reembolsado',
            ])->default('no_pagado')->index();
            $table->timestamp('pagado_en')->nullable();
            $table->enum('estado_envio', [
                'no_aplica',
                'pendiente',
                'preparando',
                'en_transito',
                'entregado',
                'fallido',
            ])->default('pendiente');
            $table->timestamp('enviado_en')->nullable();
            $table->timestamp('entregado_en')->nullable();
            $table->timestamp('fecha_pedido')->useCurrent();
            $table->timestamp('fecha_confirmacion')->nullable(); 
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->text('nota_cliente')->nullable();       // "Dejar en recepción"
            $table->text('nota_admin')->nullable();         // "Cliente pidió factura"
            $table->text('motivo_cancelacion')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_cliente', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'created_at']);
            $table->index(['estado', 'created_at']);
            $table->index(['estado_pago', 'created_at']);
            $table->index('fecha_pedido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};