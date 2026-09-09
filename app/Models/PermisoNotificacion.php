<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisoNotificacion extends Model
{
    use HasFactory;

    protected $table = 'permisos_notificaciones';

    protected $fillable = [
        'rol_id',
        'tipo_notificacion_id',
        'puede_ver'
    ];

    protected $casts = [
        'puede_ver' => 'boolean'
    ];

    // ============================================
    // RELACIONES
    // ============================================
    
    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }

    public function tipoNotificacion()
    {
        return $this->belongsTo(TipoNotificacion::class);
    }

    // ============================================
    // SCOPES
    // ============================================
    
    public function scopeTienePermiso($query, $rolId, $tipoNotificacionId)
    {
        return $query->where('rol_id', $rolId)
                     ->where('tipo_notificacion_id', $tipoNotificacionId)
                     ->where('puede_ver', true);
    }

    /**
     * Verificar si un rol puede ver un tipo de notificación
     */
    public static function puedeVer($rolId, $tipoNotificacionId)
    {
        return self::tienePermiso($rolId, $tipoNotificacionId)->exists();
    }

    /**
     * Obtener tipos de notificación que un rol puede ver
     */
    public static function tiposPermitidos($rolId)
    {
        return self::where('rol_id', $rolId)
                   ->where('puede_ver', true)
                   ->pluck('tipo_notificacion_id')
                   ->toArray();
    }
}