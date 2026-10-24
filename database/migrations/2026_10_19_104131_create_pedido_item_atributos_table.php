<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_item_atributos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_item_id')->constrained('pedido_items')->cascadeOnDelete();
            $table->foreignId('atributo_id')->nullable()->constrained('atributos')->nullOnDelete();
            $table->foreignId('atributo_termino_id')->nullable()->constrained('atributo_terminos')->nullOnDelete();
            $table->string('atributo_nombre', 100);  // "Color"
            $table->string('termino_nombre', 100);   // "Rojo"
            $table->string('valor_extra', 255)->nullable(); // "#FF0000" o URL de imagen

            $table->timestamps();

            $table->index('pedido_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_item_atributos');
    }
};