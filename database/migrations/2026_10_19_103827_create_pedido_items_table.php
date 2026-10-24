<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('variacion_id')->nullable()->constrained('producto_variaciones')->nullOnDelete();
            $table->foreignId('producto_padre_id')->nullable()->constrained('productos')->nullOnDelete();
            // ↑ Si el item vino dentro de un producto agrupado, guardamos el padre
            $table->string('nombre_producto', 255);
            $table->string('sku', 100)->nullable();
            $table->string('marca', 255)->nullable();
            // Tipo de línea: para distinguir simple/variación/hijo de agrupado
            $table->enum('tipo_item', ['simple', 'variacion', 'agrupado_hijo'])
                  ->default('simple');
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_unitario_regular', 10, 2)->default(0);
            $table->decimal('precio_unitario_rebajado', 10, 2)->nullable();
            $table->decimal('precio_unitario_final', 10, 2); // Lo que pagó realmente
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('subtotal_linea', 12, 2); // cantidad * precio_unitario_final
            $table->decimal('peso', 8, 2)->nullable();
            $table->string('peso_unidad', 10)->nullable();
            $table->decimal('longitud', 8, 2)->nullable();
            $table->decimal('anchura', 8, 2)->nullable();
            $table->decimal('altura', 8, 2)->nullable();
            $table->string('imagen_path', 500)->nullable();
            $table->enum('estado_item', [
                'activo',
                'cancelado',
                'reembolsado',
            ])->default('activo');

            $table->timestamps();

            $table->index('pedido_id');
            $table->index('producto_id');
            $table->index('variacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};