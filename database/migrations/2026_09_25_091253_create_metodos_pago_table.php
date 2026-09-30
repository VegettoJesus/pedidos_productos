<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();      // contra_entrega, transferencia, qr
            $table->string('nombre', 100);              // "Pago contra entrega"
            $table->text('descripcion')->nullable();
            $table->string('icono', 100)->nullable();   // bi bi-cash
            $table->boolean('activo')->default(false);
            $table->integer('orden')->default(0);
            $table->json('configuracion')->nullable();  // Config específica
            $table->timestamps();

            $table->index('activo');
            $table->index('orden');
        });

        // Insertar los 3 métodos base (todos desactivados por defecto)
        DB::table('metodos_pago')->insert([
            [
                'slug' => 'contra_entrega',
                'nombre' => 'Pago contra entrega',
                'descripcion' => 'Paga al recibir tu pedido',
                'icono' => 'bi bi-truck',
                'activo' => false,
                'orden' => 1,
                'configuracion' => json_encode([
                    'titulo' => 'Pago contra entrega',
                    'descripcion' => 'Paga en efectivo al recibir tu pedido.',
                    'instrucciones' => '',
                    'desactivar_si_importe_mayor' => null,
                    'restriccion_categoria_modo' => 'ninguno', // 'ninguno' | 'al_menos_uno' | 'todos'
                    'categorias_desactivar' => [],
                    'restriccion_producto_modo' => 'ninguno',
                    'productos_desactivar' => [],
                    'mensaje_no_disponible' => 'Este método de pago no está disponible para tu pedido.',
                    'cargo_adicional' => [
                        'tipo' => 'porcentaje', // 'porcentaje' | 'fijo'
                        'valor' => 0,
                        'impuesto_activo' => false,
                        'impuesto_porcentaje' => 0,
                        'desactivar_si_importe_mayor' => null,
                        'incluir_en_total' => [
                            'impuestos' => false,
                            'envio' => false,
                        ],
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'transferencia',
                'nombre' => 'Transferencia bancaria directa',
                'descripcion' => 'Transfiere a nuestra cuenta bancaria',
                'icono' => 'bi bi-bank',
                'activo' => false,
                'orden' => 2,
                'configuracion' => json_encode([
                    'titulo' => 'Transferencia bancaria directa',
                    'descripcion' => 'Realiza tu pago mediante transferencia bancaria.',
                    'instrucciones' => '',
                    'detalles_cuenta' => [
                        'nombre_cuenta' => '',
                        'numero_cuenta' => '',
                        'nombre_banco' => '',
                        'cci' => '',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'qr',
                'nombre' => 'Código QR de pago',
                'descripcion' => 'Escanea el código QR para pagar',
                'icono' => 'bi bi-qr-code',
                'activo' => false,
                'orden' => 3,
                'configuracion' => json_encode([
                    'titulo' => 'Código QR de pago',
                    'icono_imagen' => null,
                    'descripcion' => '',
                    'mensaje_emergente' => '',
                    'importe_limite' => null,
                    'mensaje_limite' => '',
                    'telefono_afiliado' => '',
                    'imagen_qr' => null,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};