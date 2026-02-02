<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTemasColoresTable extends Migration
{
    public function up()
    {
        Schema::create('temas_colores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_tema', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('es_predeterminado')->default(false);
            $table->boolean('activo')->default(false);
            $table->timestamps();
            
            $table->index('activo');
            $table->index('es_predeterminado');
        });

        $this->insertarTemasIniciales();
    }

    private function insertarTemasIniciales()
    {
        DB::table('temas_colores')->insert([
            [
                'id' => 1,
                'nombre_tema' => 'Tema Claro Predeterminado',
                'descripcion' => 'Tema claro por defecto del sistema',
                'es_predeterminado' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('temas_colores');
    }
}