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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('url', 250);
            $table->integer('padre');
            $table->integer('orden');
            $table->string('icono', 100)->nullable();
            $table->timestamps();
        });

        // Crear Menú Padre: Administración del Sistema
        $idPadre = DB::table('menus')->insertGetId([
            'nombre'     => 'Administración del Sistema',
            'url'        => '#',
            'padre'      => 0,
            'orden'      => 1,
            'icono'      => 'bi bi-gear',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear Menú Hijo: Administrar Menús
        $idHijo = DB::table('menus')->insertGetId([
            'nombre'     => 'Administrar Menús',
            'url'        => 'AdministracionDelSistema/administrarMenu',
            'padre'      => $idPadre,
            'orden'      => 1,
            'icono'      => 'bi bi-list',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
