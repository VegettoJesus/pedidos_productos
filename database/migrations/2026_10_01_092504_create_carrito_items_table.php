<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrito_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('variacion_id')->nullable()
                  ->constrained('producto_variaciones')->onDelete('cascade');

            // 🔥 Solo para agrupados: referencia al padre del bundle
            $table->foreignId('producto_padre_id')->nullable()
                  ->constrained('productos')->onDelete('cascade');

            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2); // snapshot del precio al agregar
            $table->timestamps();

            $table->index(['carrito_id']);
            $table->index(['producto_id', 'variacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrito_items');
    }
};