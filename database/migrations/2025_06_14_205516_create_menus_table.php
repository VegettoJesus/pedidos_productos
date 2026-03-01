<?php
// database/migrations/[timestamp]_create_menus_table.php

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
            $table->integer('padre')->default(0);
            $table->integer('orden');
            $table->string('icono', 100)->nullable();
            $table->timestamps();
        });

        $menus = [
            // Menús Padre (padre = 0)
            [
                'id' => 1,
                'nombre' => 'Administración del Sistema',
                'url' => '#',
                'padre' => 0,
                'orden' => 1,
                'icono' => 'bi bi-gear',
            ],
            [
                'id' => 2,
                'nombre' => 'Catalogo',
                'url' => '#',
                'padre' => 0,
                'orden' => 2,
                'icono' => 'bi bi-grid',
            ],
            [
                'id' => 3,
                'nombre' => 'Empresa',
                'url' => '#',
                'padre' => 0,
                'orden' => 3,
                'icono' => 'bi bi-building',
            ],
            [
                'id' => 4,
                'nombre' => 'Ubicación',
                'url' => '#',
                'padre' => 0,
                'orden' => 4,
                'icono' => 'bi bi-geo-alt',
            ],
            
            // Hijos de Administración del Sistema (padre = 1)
            [
                'id' => 5,
                'nombre' => 'Administrar Menús',
                'url' => 'AdministracionDelSistema/administrarMenu',
                'padre' => 1,
                'orden' => 1,
                'icono' => 'bi bi-list-ul',
            ],
            [
                'id' => 6,
                'nombre' => 'Usuarios',
                'url' => 'AdministracionDelSistema/usuarios',
                'padre' => 1,
                'orden' => 2,
                'icono' => 'bi bi-people',
            ],
            
            // Hijos de Catalogo (padre = 2)
            [
                'id' => 7,
                'nombre' => 'Gestión de Catálogo',
                'url' => 'Catalogo/gestionCatalogo',
                'padre' => 2,
                'orden' => 1,
                'icono' => 'bi bi-pencil-square',
            ],
            [
                'id' => 8,
                'nombre' => 'Productos',
                'url' => 'Catalogo/productos',
                'padre' => 2,
                'orden' => 2,
                'icono' => 'bi bi-box',
            ],
            
            // Hijos de Empresa (padre = 3)
            [
                'id' => 9,
                'nombre' => 'Datos de la Empresa',
                'url' => 'Empresa/datosEmpresa',
                'padre' => 3,
                'orden' => 1,
                'icono' => 'bi bi-info-circle',
            ],
            [
                'id' => 10,
                'nombre' => 'Configuración del Sitio',
                'url' => 'Empresa/configuracionSitio',
                'padre' => 3,
                'orden' => 2,
                'icono' => 'bi bi-sliders',
            ],
            [
                'id' => 11,
                'nombre' => 'Servidor del Correo',
                'url' => 'Empresa/servidorCorreo',
                'padre' => 3,
                'orden' => 3,
                'icono' => 'bi bi-envelope',
            ],
            
            // Hijos de Ubicación (padre = 4)
            [
                'id' => 12,
                'nombre' => 'Sitio',
                'url' => 'Ubicaciones/sitio',
                'padre' => 4,
                'orden' => 1,
                'icono' => 'bi bi-pin-map',
            ],
        ];

        foreach ($menus as $menu) {
            DB::table('menus')->insert([
                'id' => $menu['id'],
                'nombre' => $menu['nombre'],
                'url' => $menu['url'],
                'padre' => $menu['padre'],
                'orden' => $menu['orden'],
                'icono' => $menu['icono'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement("ALTER TABLE menus AUTO_INCREMENT = " . (count($menus) + 1));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};