<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_reembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // usuario que autorizó

            $table->decimal('monto_productos', 12, 2)->default(0);
            $table->decimal('monto_envio', 10, 2)->default(0);
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->json('items_reembolsados')->nullable();

            $table->text('motivo')->nullable();
            $table->enum('tipo', ['parcial', 'total'])->default('parcial');
            $table->timestamps();

            $table->index(['pedido_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_reembolsos');
    }
};