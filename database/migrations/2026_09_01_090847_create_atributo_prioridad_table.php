<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atributo_prioridad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atributo_id')->constrained('atributos')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->integer('prioridad')->default(1);
            $table->timestamps();
            
            $table->unique(['producto_id', 'atributo_id'], 'unique_producto_atributo');
            $table->index(['producto_id', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atributo_prioridad');
    }
};