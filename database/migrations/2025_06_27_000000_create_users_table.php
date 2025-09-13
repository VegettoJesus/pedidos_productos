<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 150);
            $table->string('apellidos', 150);
            $table->string('email', 250)->unique();
            $table->string('password');
            $table->foreignId('id_rol')->constrained('roles')->onDelete('cascade');
            $table->boolean('estado')->default(true);
            $table->boolean('conectado')->default(false);
            $table->boolean('dark_mode')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        DB::table('users')->insert([
            'nombres' => 'Admin',
            'apellidos' => 'Principal',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin123'),
            'id_rol' => DB::table('roles')->where('name', 'admin')->value('id'),
            'estado' => true,
            'conectado' => false, 
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
