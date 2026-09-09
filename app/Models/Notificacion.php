<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'tipo_notificacion_id',
        'creado_por',
        'titulo',
        'mensaje',
        'mensaje_corto',
        'data_extra',
        'url',
        'boton_texto',
        'prioridad',
        'fecha_inicio',
        'fecha_fin',
        'usuario_id',
        'rol_id',
        'visible',
        'eliminada'
    ];

    protected $casts = [
        'data_extra' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime'
    ];

    // ============================================
    // RELACIONES
    // ============================================
    
    public function tipo()
    {
        return $this->belongsTo(TipoNotificacion::class, 'tipo_notificacion_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /**
     * 🔥 Relación con usuarios a través de la tabla pivote
     * Cada notificación puede tener muchos usuarios asociados
     */
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'notificacion_usuario')
                    ->withPivot('leida', 'leida_en', 'fecha_visualizacion')
                    ->withTimestamps();
    }

    // ============================================
    // SCOPES (Filtros)
    // ============================================
    
    /**
     * Notificaciones visibles (no eliminadas)
     */
    public function scopeVisible($query)
    {
        return $query->where('visible', true)->where('eliminada', false);
    }

    /**
     * Notificaciones en fecha de vigencia
     */
    public function scopeEnFecha($query)
    {
        return $query->where(function($q) {
            $q->whereNull('fecha_inicio')
              ->orWhere('fecha_inicio', '<=', now());
        })->where(function($q) {
            $q->whereNull('fecha_fin')
              ->orWhere('fecha_fin', '>=', now());
        });
    }

    /**
     * Notificaciones no leídas (por usuario, desde la pivote)
     */
    public function scopeNoLeidasPorUsuario($query, $usuarioId)
    {
        return $query->whereHas('usuarios', function($q) use ($usuarioId) {
            $q->where('user_id', $usuarioId)
              ->where('leida', false);
        });
    }

    /**
     * Notificaciones leídas (por usuario, desde la pivote)
     */
    public function scopeLeidasPorUsuario($query, $usuarioId)
    {
        return $query->whereHas('usuarios', function($q) use ($usuarioId) {
            $q->where('user_id', $usuarioId)
              ->where('leida', true);
        });
    }

    /**
     * Filtrar por usuario específico
     */
    public function scopePorUsuario($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->whereNull('usuario_id')
              ->orWhere('usuario_id', $userId);
        });
    }

    /**
     * Filtrar por rol específico
     */
    public function scopePorRol($query, $rolId)
    {
        return $query->where(function($q) use ($rolId) {
            $q->whereNull('rol_id')
              ->orWhere('rol_id', $rolId);
        });
    }

    // ============================================
    // MÉTODOS DE ESTADO POR USUARIO
    // ============================================
    
    /**
     * Verificar si la notificación está leída por un usuario
     */
    public function estaLeidaPorUsuario($usuarioId)
    {
        return $this->usuarios()
                    ->where('user_id', $usuarioId)
                    ->wherePivot('leida', true)
                    ->exists();
    }

    /**
     * Marcar notificación como leída para un usuario
     */
    public function marcarComoLeidaPorUsuario($usuarioId)
    {
        $this->usuarios()->syncWithPivotValues([$usuarioId], [
            'leida' => true,
            'leida_en' => now()
        ]);
        
        return $this;
    }

    /**
     * Marcar notificación como no leída para un usuario
     */
    public function marcarComoNoLeidaPorUsuario($usuarioId)
    {
        $this->usuarios()->syncWithPivotValues([$usuarioId], [
            'leida' => false,
            'leida_en' => null
        ]);
        
        return $this;
    }

    /**
     * Registrar visualización de la notificación por un usuario
     */
    public function registrarVisualizacionPorUsuario($usuarioId)
    {
        $this->usuarios()->syncWithPivotValues([$usuarioId], [
            'fecha_visualizacion' => now()
        ]);
        
        return $this;
    }

    // ============================================
    // MÉTODOS ESTÁTICOS PARA CONSULTAS
    // ============================================

    /**
     * Obtener solo notificaciones no leídas para un usuario
     */
    public static function getNoLeidasParaUsuario($userId, $rolId, $limit = 20)
    {
        return self::visible()
            ->enFecha()
            ->porUsuario($userId)
            ->porRol($rolId)
            ->whereHas('usuarios', function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('leida', false);
            })
            ->orderBy('prioridad', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Marcar todas las notificaciones como leídas para un usuario
     */
    public static function marcarTodasLeidasPorUsuario($userId)
    {
        $notificaciones = self::visible()
            ->enFecha()
            ->porUsuario($userId)
            ->porRol(auth()->user()->id_rol ?? 0)
            ->get();

        foreach ($notificaciones as $notificacion) {
            $notificacion->marcarComoLeidaPorUsuario($userId);
        }

        return true;
    }

    // ============================================
    // MÉTODO PARA ASIGNAR USUARIOS
    // ============================================
    
    /**
     * 🔥 Asignar notificación a usuarios según el destino configurado
     * (Se llama automáticamente después de crear la notificación)
     */
    public function asignarAUsuarios()
    {
        // Si ya tiene usuarios asignados, no hacer nada
        if ($this->usuarios()->exists()) {
            return $this;
        }

        // Si tiene usuario específico, solo para ese usuario
        if ($this->usuario_id) {
            $this->usuarios()->attach($this->usuario_id);
            return $this;
        }

        // Si tiene rol específico, para todos los usuarios de ese rol
        if ($this->rol_id) {
            $usuarios = User::where('id_rol', $this->rol_id)->get();
            foreach ($usuarios as $usuario) {
                $this->usuarios()->attach($usuario->id);
            }
            return $this;
        }

        // Si no tiene usuario ni rol, para TODOS los usuarios
        $usuarios = User::all();
        foreach ($usuarios as $usuario) {
            $this->usuarios()->attach($usuario->id);
        }

        return $this;
    }

    // ============================================
    // EVENTOS DEL MODELO (Boot)
    // ============================================
    
    protected static function boot()
    {
        parent::boot();

        static::created(function ($notificacion) {
            $notificacion->asignarAUsuarios();
        });

        static::updated(function ($notificacion) {
            if ($notificacion->isDirty('usuario_id') || $notificacion->isDirty('rol_id')) {
                $notificacion->usuarios()->detach();
                $notificacion->asignarAUsuarios();
            }
        });
    }

    /**
     * Verificar si un usuario puede ver esta notificación
     */
    public function usuarioPuedeVer($usuario)
    {
        if (!$usuario) return false;
        
        // Admin ve todo
        if ($usuario->rol->name === 'admin') {
            return true;
        }
        
        // Si la notificación es para un usuario específico
        if ($this->usuario_id && $this->usuario_id == $usuario->id) {
            return $this->tienePermisoRol($usuario->id_rol);
        }
        
        // Verificar permiso por rol y tipo
        return PermisoNotificacion::puedeVer($usuario->id_rol, $this->tipo_notificacion_id);
    }

    /**
     * Verificar si el rol tiene permiso para este tipo
     */
    private function tienePermisoRol($rolId)
    {
        return PermisoNotificacion::puedeVer($rolId, $this->tipo_notificacion_id);
    }

    /**
     * SOBRESCRIBIR: Obtener solo notificaciones que el usuario puede ver
     */
    public static function getNotificacionesParaUsuario($userId, $rolId, $limit = 50)
    {
        // Obtener tipos de notificación permitidos para este rol
        $tiposPermitidos = PermisoNotificacion::tiposPermitidos($rolId);
        
        // Si no tiene ningún tipo permitido, retornar vacío
        if (empty($tiposPermitidos)) {
            return collect();
        }
        
        return self::visible()
            ->enFecha()
            ->porUsuario($userId)
            ->porRol($rolId)
            ->whereIn('tipo_notificacion_id', $tiposPermitidos) 
            ->orderBy('prioridad', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($notificacion) use ($userId) {
                $notificacion->leida = $notificacion->estaLeidaPorUsuario($userId);
                return $notificacion;
            });
    }

    /**
     * Contar no leídas con permisos
     */
    public static function countNoLeidas($userId, $rolId)
    {
        $tiposPermitidos = PermisoNotificacion::tiposPermitidos($rolId);
        
        if (empty($tiposPermitidos)) {
            return 0;
        }
        
        return self::visible()
            ->enFecha()
            ->porUsuario($userId)
            ->porRol($rolId)
            ->whereIn('tipo_notificacion_id', $tiposPermitidos)
            ->whereHas('usuarios', function($q) use ($userId) {
                $q->where('user_id', $userId)
                ->where('leida', false);
            })
            ->count();
    }
}