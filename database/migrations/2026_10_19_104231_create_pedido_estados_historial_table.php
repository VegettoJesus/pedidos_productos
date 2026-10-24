<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_estados_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Quién lo cambió

            // Tipo de cambio
            $table->enum('tipo_cambio', [
                'estado',        // pendiente → pagado
                'estado_pago',   // no_pagado → pagado
                'estado_envio',  // pendiente → en_transito
                'item',          // cancelación de item
                'evidencia',
                'nota',
                'reembolso'
            ])->default('estado');

            $table->string('estado_anterior', 50)->nullable();
            $table->string('estado_nuevo', 50);
            $table->text('motivo')->nullable();
            $table->json('metadata')->nullable(); // Info extra: tracking, monto, etc.

            $table->timestamps();

            $table->index(['pedido_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_estados_historial');
    }
};