<?php
// app/Models/PermisoNotificacion.php

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
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function tipoNotificacion()
    {
        return $this->belongsTo(TipoNotificacion::class, 'tipo_notificacion_id');
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

    /**
     * Verificar si un ROL puede ver un tipo (sin considerar excepciones)
     */
    public static function puedeVerRol($rolId, $tipoNotificacionId)
    {
        return self::where('rol_id', $rolId)
                   ->where('tipo_notificacion_id', $tipoNotificacionId)
                   ->where('puede_ver', true)
                   ->exists();
    }

    /**
     * Verificar si un USUARIO puede ver un tipo (con excepciones individuales)
     * 
     * Orden de prioridad:
     *   1. Admin → siempre SÍ
     *   2. Excepción individual del usuario
     *   3. Permiso del rol
     */
    public static function puedeVerUsuario($usuario, $tipoNotificacionId)
    {
        if (!$usuario) return false;

        // 1. Admin siempre puede ver TODO
        if ($usuario->rol && $usuario->rol->name === 'admin') {
            return true;
        }

        // 2. Verificar excepción individual
        $excepcion = ExcepcionNotificacionUsuario::obtener($usuario->id, $tipoNotificacionId);
        
        if ($excepcion) {
            return $excepcion->tipo_excepcion === 'permitir';
        }

        // 3. Usar permiso del rol
        return self::puedeVerRol($usuario->id_rol, $tipoNotificacionId);
    }

    /**
     * Obtener tipos de notificación que un usuario puede ver
     * (aplicando excepciones individuales)
     */
    public static function tiposPermitidosUsuario($usuario)
    {
        if (!$usuario) return [];

        // Admin → todos los tipos activos
        if ($usuario->rol && $usuario->rol->name === 'admin') {
            return TipoNotificacion::where('activo', true)->pluck('id')->toArray();
        }

        // Tipos base según el rol
        $tiposDelRol = self::where('rol_id', $usuario->id_rol)
            ->where('puede_ver', true)
            ->pluck('tipo_notificacion_id')
            ->toArray();

        // Excepciones del usuario
        $excepciones = ExcepcionNotificacionUsuario::where('usuario_id', $usuario->id)
            ->get();

        $tiposPermitidos = $tiposDelRol;

        foreach ($excepciones as $exc) {
            if ($exc->tipo_excepcion === 'permitir') {
                // Agregar si no está
                if (!in_array($exc->tipo_notificacion_id, $tiposPermitidos)) {
                    $tiposPermitidos[] = $exc->tipo_notificacion_id;
                }
            } else {
                // 'denegar' → quitar si está
                $key = array_search($exc->tipo_notificacion_id, $tiposPermitidos);
                if ($key !== false) {
                    unset($tiposPermitidos[$key]);
                }
            }
        }

        return array_values($tiposPermitidos);
    }

    /**
     * Obtener todos los tipos con su estado de permiso para un usuario
     * Útil para el panel de administración
     */
    public static function matrizPermisosUsuario($usuario)
    {
        $tipos = TipoNotificacion::where('activo', true)->orderBy('nombre')->get();
        $resultado = [];

        foreach ($tipos as $tipo) {
            $permisoRol = self::puedeVerRol($usuario->id_rol, $tipo->id);
            $excepcion = ExcepcionNotificacionUsuario::obtener($usuario->id, $tipo->id);
            $permisoFinal = self::puedeVerUsuario($usuario, $tipo->id);

            $resultado[] = [
                'tipo_id' => $tipo->id,
                'tipo_nombre' => $tipo->nombre,
                'tipo_slug' => $tipo->slug,
                'tipo_icono' => $tipo->icono,
                'tipo_color' => $tipo->color,
                'permiso_rol' => $permisoRol,
                'excepcion' => $excepcion ? [
                    'id' => $excepcion->id,
                    'tipo_excepcion' => $excepcion->tipo_excepcion,
                    'motivo' => $excepcion->motivo,
                ] : null,
                'permiso_final' => $permisoFinal,
            ];
        }

        return $resultado;
    }
}