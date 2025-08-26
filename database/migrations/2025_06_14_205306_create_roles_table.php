<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        // Insertar roles por defecto
        DB::table('roles')->insert([
            ['name' => 'admin',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'client',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'user',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'marketing',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'developer',     'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
