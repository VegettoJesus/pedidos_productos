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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->text('descripcion');
            $table->decimal('precio', 10, 2);
            $table->integer('stock');
            $table->string('imagen1', 255)->nullable();
            $table->string('imagen2', 255)->nullable();
            $table->string('imagen3', 255)->nullable();
            $table->string('imagen4', 255)->nullable();
            $table->string('imagen5', 255)->nullable();
            $table->string('imagen6', 255)->nullable();
            $table->foreignId('id_subCategorias')->constrained('subcategorias')->onDelete('cascade');
            $table->string('marca', 255)->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
