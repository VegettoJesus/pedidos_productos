<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeConfiguracion extends Model
{
    protected $table = 'home_configuracion';
    
    protected $fillable = [
        'seccion',
        'tipo',
        'numero_elementos',
        'orden',
        'mostrar',
        'configuracion_json'
    ];

    protected $casts = [
        'mostrar' => 'boolean',
        'configuracion_json' => 'array',
        'numero_elementos' => 'integer',
        'orden' => 'integer'
    ];

    protected $attributes = [
        'configuracion_json' => '{}',
        'mostrar' => true,
        'orden' => 0
    ];

    /**
     * Scope para obtener solo secciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('mostrar', true);
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden', 'asc');
    }

    /**
     * Obtener configuración por sección
     */
    public static function obtenerPorSeccion($seccion)
    {
        return self::where('seccion', $seccion)->first();
    }

    /**
     * Obtener todas las secciones activas ordenadas
     */
    public static function obtenerSeccionesActivas()
    {
        return self::activas()->ordenado()->get();
    }
}