<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateConfiguracionColoresTable extends Migration
{
    public function up()
    {
        Schema::create('configuracion_colores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tema_id')->constrained('temas_colores')->onDelete('cascade');
            $table->string('variable_nombre', 50);
            $table->string('variable_valor', 50); // Cambiado de 20 a 50
            $table->enum('grupo', [
                'bordes',
                'fondos', 
                'textos',
                'sidebar',
                'tablas',
                'cards',
                'botones',
                'tooltips',
                'paginate'
            ]);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
            
            $table->unique(['tema_id', 'variable_nombre']);
            $table->index('grupo');
            $table->index('orden');
        });

        $this->insertarConfiguracionesIniciales();
    }

    private function insertarConfiguracionesIniciales()
    {
        // Primero, insertar configuraciones para tema claro (ID: 1)
        $configClaro = [
            // Bordes
            ['tema_id' => 1, 'variable_nombre' => '--shadow-border', 'variable_valor' => 'rgba(0, 0, 0, 0.1)', 'grupo' => 'bordes', 'descripcion' => 'Borde para sidebar y icono de menú', 'orden' => 1],
            ['tema_id' => 1, 'variable_nombre' => '--color-border-icon', 'variable_valor' => '#000000', 'grupo' => 'bordes', 'descripcion' => 'Color bordes del icono para modo oscuro', 'orden' => 2],
            ['tema_id' => 1, 'variable_nombre' => '--color-border', 'variable_valor' => '#000000', 'grupo' => 'bordes', 'descripcion' => 'Bordes de inputs y elementos internos', 'orden' => 3],
            ['tema_id' => 1, 'variable_nombre' => '--color-border-card', 'variable_valor' => '#aaa', 'grupo' => 'bordes', 'descripcion' => 'Color bordes de inputs en cards', 'orden' => 4],
            
            // Fondos
            ['tema_id' => 1, 'variable_nombre' => '--color-bg', 'variable_valor' => '#FEEBC3', 'grupo' => 'fondos', 'descripcion' => 'Fondo principal de la vista', 'orden' => 1],
            
            // Textos
            ['tema_id' => 1, 'variable_nombre' => '--color-text', 'variable_valor' => '#000000', 'grupo' => 'textos', 'descripcion' => 'Color texto principal (tipo de usuario)', 'orden' => 1],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-secondary', 'variable_valor' => '#000000', 'grupo' => 'textos', 'descripcion' => 'Color texto secundario (iconos, nombre usuario)', 'orden' => 2],
            
            // Tooltips
            ['tema_id' => 1, 'variable_nombre' => '--color-tooltip-bg', 'variable_valor' => '#000000', 'grupo' => 'tooltips', 'descripcion' => 'Fondo del tooltip principal', 'orden' => 1],
            ['tema_id' => 1, 'variable_nombre' => '--color-tooltip-text', 'variable_valor' => '#ffffff', 'grupo' => 'tooltips', 'descripcion' => 'Color texto del tooltip principal', 'orden' => 2],
            ['tema_id' => 1, 'variable_nombre' => '--color-sub-tooltip', 'variable_valor' => '#F4AB28', 'grupo' => 'tooltips', 'descripcion' => 'Fondo del tooltip secundario', 'orden' => 3],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-sub-border-shadow', 'variable_valor' => 'black', 'grupo' => 'tooltips', 'descripcion' => 'Color shadow border del tooltip secundario', 'orden' => 4],
            
            // Botones
            ['tema_id' => 1, 'variable_nombre' => '--color-primary', 'variable_valor' => '#0d6efd', 'grupo' => 'botones', 'descripcion' => 'Botón primary Bootstrap', 'orden' => 1],
            ['tema_id' => 1, 'variable_nombre' => '--color-success', 'variable_valor' => '#198754', 'grupo' => 'botones', 'descripcion' => 'Botón success Bootstrap', 'orden' => 2],
            ['tema_id' => 1, 'variable_nombre' => '--color-danger', 'variable_valor' => '#dc3545', 'grupo' => 'botones', 'descripcion' => 'Botón danger Bootstrap', 'orden' => 3],
            ['tema_id' => 1, 'variable_nombre' => '--color-warning', 'variable_valor' => '#ffc107', 'grupo' => 'botones', 'descripcion' => 'Botón warning Bootstrap', 'orden' => 4],
            ['tema_id' => 1, 'variable_nombre' => '--color-info', 'variable_valor' => '#0dcaf0', 'grupo' => 'botones', 'descripcion' => 'Botón info Bootstrap', 'orden' => 5],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-primary', 'variable_valor' => 'white', 'grupo' => 'botones', 'descripcion' => 'Texto color botón primary', 'orden' => 6],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-success', 'variable_valor' => 'white', 'grupo' => 'botones', 'descripcion' => 'Texto color botón success', 'orden' => 7],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-danger', 'variable_valor' => 'white', 'grupo' => 'botones', 'descripcion' => 'Texto color botón danger', 'orden' => 8],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-warning', 'variable_valor' => 'black', 'grupo' => 'botones', 'descripcion' => 'Texto color botón warning', 'orden' => 9],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-info', 'variable_valor' => 'black', 'grupo' => 'botones', 'descripcion' => 'Texto color botón info', 'orden' => 10],
            
            // Tablas
            ['tema_id' => 1, 'variable_nombre' => '--color-table', 'variable_valor' => '#f4ab28', 'grupo' => 'tablas', 'descripcion' => 'Color fondo de las filas del datatable', 'orden' => 1],
            ['tema_id' => 1, 'variable_nombre' => '--color-table-text', 'variable_valor' => 'black', 'grupo' => 'tablas', 'descripcion' => 'Color texto de las filas del datatable', 'orden' => 2],
            ['tema_id' => 1, 'variable_nombre' => '--color-table-thead', 'variable_valor' => '#f35b08', 'grupo' => 'tablas', 'descripcion' => 'Color fondo cabecera del datatable', 'orden' => 3],
            ['tema_id' => 1, 'variable_nombre' => '--color-table-text-thead', 'variable_valor' => 'white', 'grupo' => 'tablas', 'descripcion' => 'Color texto cabecera del datatable', 'orden' => 4],
            ['tema_id' => 1, 'variable_nombre' => '--color-table-hijo-row', 'variable_valor' => '#c55418', 'grupo' => 'tablas', 'descripcion' => 'Color fondo de hijos en datatable', 'orden' => 5],
            ['tema_id' => 1, 'variable_nombre' => '--color-table-hijo-row-text', 'variable_valor' => 'white', 'grupo' => 'tablas', 'descripcion' => 'Color texto de hijos en datatable', 'orden' => 6],
            ['tema_id' => 1, 'variable_nombre' => '--color-table-hover', 'variable_valor' => 'black', 'grupo' => 'tablas', 'descripcion' => 'Color fondo hover filas datatable', 'orden' => 7],
            ['tema_id' => 1, 'variable_nombre' => '--color-table-hover-text', 'variable_valor' => 'white', 'grupo' => 'tablas', 'descripcion' => 'Color texto hover filas datatable', 'orden' => 8],
            
            // Cards
            ['tema_id' => 1, 'variable_nombre' => '--color-header', 'variable_valor' => '#f35b08', 'grupo' => 'cards', 'descripcion' => 'Color fondo header de cards', 'orden' => 1],
            ['tema_id' => 1, 'variable_nombre' => '--color-header-text', 'variable_valor' => 'black', 'grupo' => 'cards', 'descripcion' => 'Color texto header de cards', 'orden' => 2],
            ['tema_id' => 1, 'variable_nombre' => '--color-bg-card', 'variable_valor' => 'white', 'grupo' => 'cards', 'descripcion' => 'Color fondo del card', 'orden' => 3],
            ['tema_id' => 1, 'variable_nombre' => '--color-datatable-search-card', 'variable_valor' => 'white', 'grupo' => 'cards', 'descripcion' => 'Color fondo de inputs/select en cards', 'orden' => 4],
            ['tema_id' => 1, 'variable_nombre' => '--color-datatable-search-card-text', 'variable_valor' => 'black', 'grupo' => 'cards', 'descripcion' => 'Color texto de inputs/select en cards', 'orden' => 5],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-label', 'variable_valor' => 'black', 'grupo' => 'cards', 'descripcion' => 'Color texto labels', 'orden' => 6],
            
            // Paginate
            ['tema_id' => 1, 'variable_nombre' => '--color-text-paginate-info', 'variable_valor' => 'black', 'grupo' => 'paginate', 'descripcion' => 'Color texto info paginate', 'orden' => 1],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-paginate', 'variable_valor' => '#666', 'grupo' => 'paginate', 'descripcion' => 'Color texto paginate links', 'orden' => 2],
            
            // Sidebar
            ['tema_id' => 1, 'variable_nombre' => '--sidebar-bg', 'variable_valor' => 'linear-gradient(to right, #e5a06c, #f35b08)', 'grupo' => 'sidebar', 'descripcion' => 'Color fondo del sidebar', 'orden' => 1],
            ['tema_id' => 1, 'variable_nombre' => '--color-sidebar-hover', 'variable_valor' => '#FEEBC3', 'grupo' => 'sidebar', 'descripcion' => 'Color fondo hover menú sidebar', 'orden' => 2],
            ['tema_id' => 1, 'variable_nombre' => '--color-sidebar-text-hover', 'variable_valor' => 'black', 'grupo' => 'sidebar', 'descripcion' => 'Color texto hover menú sidebar', 'orden' => 3],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-secondary-sidebar', 'variable_valor' => 'black', 'grupo' => 'sidebar', 'descripcion' => 'Color texto menú sidebar', 'orden' => 4],
            ['tema_id' => 1, 'variable_nombre' => '--color-text-tertiary-sidebar', 'variable_valor' => 'black', 'grupo' => 'sidebar', 'descripcion' => 'Color texto nombre empresa sidebar', 'orden' => 5]
        ];

        $now = now();
        
        // Preparar todos los datos con timestamps
        $allConfigs = [];
        foreach (array_merge($configClaro) as $config) {
            $allConfigs[] = array_merge($config, [
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Insertar en lotes para evitar problemas de memoria
        foreach (array_chunk($allConfigs, 50) as $chunk) {
            DB::table('configuracion_colores')->insert($chunk);
        }
    }

    public function down()
    {
        Schema::dropIfExists('configuracion_colores');
    }
}