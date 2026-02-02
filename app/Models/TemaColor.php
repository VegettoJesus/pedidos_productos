<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemaColor extends Model
{
    use HasFactory;

    protected $table = 'temas_colores';
    
    protected $fillable = [
        'nombre_tema',
        'descripcion',
        'es_predeterminado',
        'activo'
    ];

    protected $casts = [
        'es_predeterminado' => 'boolean',
        'activo' => 'boolean'
    ];

    public function configuraciones()
    {
        return $this->hasMany(ConfiguracionColor::class, 'tema_id');
    }

    // Método para activar un tema
    public function activar()
    {
        // Desactivar todos los temas primero
        self::where('activo', true)->update(['activo' => false]);
        
        // Activar este tema
        $this->update(['activo' => true]);
    }

    // Método para verificar si es predeterminado
    public function esPredeterminado()
    {
        return $this->es_predeterminado;
    }

    // Método para obtener el tema activo
    public static function obtenerActivo()
    {
        return self::where('activo', true)->first();
    }
}