<?php
// database/migrations/[timestamp]_create_permisos_table.php

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
            $table->json('permisos')->nullable();
            $table->timestamps();
        });

        $permisosDefault = json_encode([
            'ver' => true,
            'editar' => true,
            'crear' => true,
            'eliminar' => true,
            'configurar' => true,
        ]);

        // Insertar permisos para cada menú
        for ($menuId = 1; $menuId <= 12; $menuId++) {
            DB::table('permisos')->insert([
                'id_rol' => 1,
                'id_menus' => $menuId,
                'permisos' => $permisosDefault,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};