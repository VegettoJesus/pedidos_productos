<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')->constrained('roles')->onDelete('cascade');
            $table->foreignId('id_menus')->constrained('menus')->onDelete('cascade');
            $table->boolean('ver')->default(false);
            $table->boolean('editar')->default(false);
            $table->boolean('crear')->default(false);
            $table->boolean('eliminar')->default(false);
            $table->timestamps();
        });

        DB::table('permisos')->insert([
            [
                'id_rol'    => 1,
                'id_menus'  => 1,
                'ver'       => true,
                'editar'    => true,
                'crear'     => true,
                'eliminar'  => true,
                'created_at'=> now(),
                'updated_at'=> now(),
            ],
            [
                'id_rol'    => 1,
                'id_menus'  => 2,
                'ver'       => true,
                'editar'    => true,
                'crear'     => true,
                'eliminar'  => true,
                'created_at'=> now(),
                'updated_at'=> now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
