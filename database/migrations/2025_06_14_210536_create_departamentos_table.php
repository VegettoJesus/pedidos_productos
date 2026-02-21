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
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar los departamentos
        DB::table('departamentos')->insert([
            ['nombre' => 'AMAZONAS', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'ANCASH', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'APURIMAC', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'AREQUIPA', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'AYACUCHO', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CAJAMARCA', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CALLAO', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CUSCO', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'HUANCAVELICA', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'HUANUCO', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'ICA', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'JUNIN', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LA LIBERTAD', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LAMBAYEQUE', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LIMA', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LORETO', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'MADRE DE DIOS', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'MOQUEGUA', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PASCO', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PIURA', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PUNO', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'SAN MARTIN', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'TACNA', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'TUMBES', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'UCAYALI', 'activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departamentos');
    }
};