<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacto_mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('email', 150);
            $table->string('telefono', 30)->nullable();
            $table->string('asunto', 150);
            $table->text('mensaje');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('estado', ['nuevo', 'leido', 'respondido', 'archivado'])
                  ->default('nuevo');

            $table->text('nota_admin')->nullable();
            $table->timestamp('leido_en')->nullable();
            $table->timestamp('respondido_en')->nullable();
            $table->foreignId('respondido_por')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('estado');
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacto_mensajes');
    }
};