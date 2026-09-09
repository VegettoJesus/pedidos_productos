<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('variacion_id')->nullable()->constrained('producto_variaciones')->onDelete('cascade');
            $table->integer('cantidad_anterior');
            $table->integer('nueva_cantidad');
            $table->integer('diferencia');
            $table->string('motivo')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete(); 
            $table->timestamps();
            
            $table->index('producto_id');
            $table->index('variacion_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_stock');
    }
};