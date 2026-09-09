<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_notificacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->string('icono', 50)->default('bi-bell');
            $table->string('color', 20)->default('#3498db');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::table('tipos_notificacion')->insert([
            [
                'nombre' => 'Sistema',
                'slug' => 'sistema',
                'descripcion' => 'Notificaciones del sistema, mantenimiento, actualizaciones',
                'icono' => 'bi-gear',
                'color' => '#6c757d',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Compras',
                'slug' => 'compras',
                'descripcion' => 'Notificaciones relacionadas con compras y pedidos',
                'icono' => 'bi-cart',
                'color' => '#28a745',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Seguridad',
                'slug' => 'seguridad',
                'descripcion' => 'Alertas de seguridad, intentos de acceso, cambios de contraseña',
                'icono' => 'bi-shield-lock',
                'color' => '#dc3545',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Inventario',
                'slug' => 'inventario',
                'descripcion' => 'Alertas de stock bajo, productos agotados, reposiciones',
                'icono' => 'bi-box-seam',
                'color' => '#ffc107',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Usuario',
                'slug' => 'usuario',
                'descripcion' => 'Notificaciones personales del usuario: registro, actualización de perfil, cambios de contraseña, etc.',
                'icono' => 'bi-person-check',
                'color' => '#6f42c1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Promociones',
                'slug' => 'promociones',
                'descripcion' => 'Ofertas especiales, descuentos, campañas de marketing',
                'icono' => 'bi-megaphone',
                'color' => '#fd7e14',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Error del Sistema',
                'slug' => 'error-sistema',
                'descripcion' => 'Errores críticos, excepciones, fallos del sistema',
                'icono' => 'bi-exclamation-triangle',
                'color' => '#dc3545',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_notificacion');
    }
};