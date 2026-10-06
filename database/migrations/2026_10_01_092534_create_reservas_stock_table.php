<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_item_id')->nullable()
                  ->constrained('carrito_items')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('variacion_id')->nullable()
                  ->constrained('producto_variaciones')->onDelete('cascade');
            $table->integer('cantidad');
            $table->boolean('liberada')->default(false);
            $table->timestamps();

            $table->index(['producto_id', 'variacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas_stock');
    }
};