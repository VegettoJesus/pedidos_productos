<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodos_pago';

    protected $fillable = [
        'slug', 'nombre', 'descripcion', 'icono',
        'activo', 'orden', 'configuracion'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
        'configuracion' => 'array',
    ];

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    // ============================================
    // MÉTODOS
    // ============================================

    /**
     * Obtener un campo específico de la configuración
     */
    public function getConfig($key, $default = null)
    {
        return data_get($this->configuracion, $key, $default);
    }

    /**
     * Actualizar un campo específico de la configuración
     */
    public function setConfig($key, $value)
    {
        $config = $this->configuracion ?? [];
        data_set($config, $key, $value);
        $this->configuracion = $config;
        return $this;
    }

    /**
     * Obtener todos los métodos activos ordenados
     */
    public static function obtenerActivos()
    {
        return self::activos()->ordenados()->get();
    }
}