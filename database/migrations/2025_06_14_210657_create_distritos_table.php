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
        Schema::create('distritos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->decimal('costo_envio', 10, 2);
            $table->foreignId('provincia_id')->constrained('provincias')->onDelete('cascade');
            $table->timestamps();
        });

        $callaoDistritos = [
            'Callao', 'Ventanilla', 'Carmen de la Legua Reynoso', 'Bellavista',
            'La Perla', 'Mi Perú', 'La Punta'
        ];

        foreach ($callaoDistritos as $distritoC) {
            DB::table('distritos')->insert([
                'nombre' => $distritoC,
                'costo_envio' => 0.00,
                'provincia_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $limaDistritos = [
            'Ancón', 'Ate', 'Barranco', 'Breña', 'Carabayllo', 'Chaclacayo', 'Chorrillos', 'Cieneguilla',
            'Comas', 'El Agustino', 'Independencia', 'Jesús María', 'La Molina', 'La Victoria', 'Lima',
            'Lince', 'Los Olivos', 'Lurigancho', 'Lurín', 'Magdalena del Mar', 'Miraflores', 'Pachacámac',
            'Pucusana', 'Pueblo Libre', 'Puente Piedra', 'Punta Hermosa', 'Punta Negra', 'Rímac', 'San Bartolo',
            'San Borja', 'San Isidro', 'San Juan de Lurigancho', 'San Juan de Miraflores', 'San Luis',
            'San Martín de Porres', 'San Miguel', 'Santa Anita', 'Santa María del Mar', 'Santa Rosa',
            'Santiago de Surco', 'Surquillo', 'Villa El Salvador', 'Villa María del Triunfo'
        ];

        foreach ($limaDistritos as $distritoL) {
            DB::table('distritos')->insert([
                'nombre' => $distritoL,
                'costo_envio' => 0.00,
                'provincia_id' => 2,
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
        Schema::dropIfExists('distritos');
    }
};
