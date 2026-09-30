<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcepcionNotificacionUsuario extends Model
{
    use HasFactory;

    protected $table = 'excepciones_notificacion_usuario';

    protected $fillable = [
        'usuario_id',
        'tipo_notificacion_id',
        'tipo_excepcion',
        'motivo',
        'creado_por'
    ];

    // ============================================
    // RELACIONES
    // ============================================
    
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function tipoNotificacion()
    {
        return $this->belongsTo(TipoNotificacion::class, 'tipo_notificacion_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // ============================================
    // SCOPES
    // ============================================
    
    public function scopeParaUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeParaTipo($query, $tipoId)
    {
        return $query->where('tipo_notificacion_id', $tipoId);
    }

    public function scopeDeTipo($query, $tipo)
    {
        return $query->where('tipo_excepcion', $tipo);
    }

    // ============================================
    // MÉTODOS ESTÁTICOS
    // ============================================
    
    /**
     * Obtener la excepción para un usuario y tipo específicos
     */
    public static function obtener($usuarioId, $tipoNotificacionId)
    {
        return self::where('usuario_id', $usuarioId)
                   ->where('tipo_notificacion_id', $tipoNotificacionId)
                   ->first();
    }

    /**
     * Verificar si existe una excepción
     */
    public static function existe($usuarioId, $tipoNotificacionId)
    {
        return self::where('usuario_id', $usuarioId)
                   ->where('tipo_notificacion_id', $tipoNotificacionId)
                   ->exists();
    }
}