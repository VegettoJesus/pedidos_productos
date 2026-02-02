<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('home_configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('seccion', 50);
            $table->string('tipo', 50)->default('productos');
            $table->integer('numero_elementos');
            $table->integer('orden')->default(0);
            $table->boolean('mostrar')->default(true);
            $table->json('configuracion_json')->nullable();
            $table->timestamps();
            
            $table->unique('seccion');
            $table->index('orden');
            $table->index('mostrar');
        });

        // Insertar datos iniciales
        $this->insertarDatosIniciales();
    }

    private function insertarDatosIniciales()
    {
        $secciones = [
            [
                'seccion' => 'destacados',
                'tipo' => 'productos',
                'numero_elementos' => 8,
                'orden' => 1,
                'mostrar' => true,
                'configuracion_json' => json_encode([
                    'criterio' => 'manual',
                    'productos_ids' => [],
                    'mostrar_precio' => true,
                    'mostrar_boton' => true
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'seccion' => 'nuevos',
                'tipo' => 'productos',
                'numero_elementos' => 6,
                'orden' => 2,
                'mostrar' => true,
                'configuracion_json' => json_encode([
                    'dias_recientes' => 30,
                    'ordenar_por' => 'fecha_desc'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'seccion' => 'ofertas',
                'tipo' => 'productos',
                'numero_elementos' => 6,
                'orden' => 3,
                'mostrar' => true,
                'configuracion_json' => json_encode([
                    'descuento_minimo' => 15,
                    'mostrar_temporizador' => true
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'seccion' => 'mas_vendidos',
                'tipo' => 'productos',
                'numero_elementos' => 8,
                'orden' => 4,
                'mostrar' => true,
                'configuracion_json' => json_encode([
                    'periodo' => 'mes_actual',
                    'categoria_id' => null
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'seccion' => 'recomendados',
                'tipo' => 'productos',
                'numero_elementos' => 12,
                'orden' => 5,
                'mostrar' => false,
                'configuracion_json' => json_encode([
                    'algoritmo' => 'basado_historial',
                    'fallback' => 'mas_vendidos',
                    'max_similares' => 8,
                    'requiere_login' => true
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('home_configuracion')->insert($secciones);
    }

    public function down()
    {
        Schema::dropIfExists('home_configuracion');
    }
};