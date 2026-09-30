<?php
// app/Http/Controllers/NotificacionController.php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    /**
     * Obtener notificaciones del usuario autenticado
     */
    public function usuarioNotificaciones(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }
        
        // Solo permitir a clientes (rol ID 2)
        if ($user->id_rol != 2) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver notificaciones'
            ], 403);
        }
        
        $limit = $request->get('limit', 20);
        
        $notificaciones = Notificacion::getNotificacionesParaUsuario(
            $user->id,
            $user->id_rol,
            $limit
        );
        
        $noLeidas = Notificacion::countNoLeidas($user->id, $user->id_rol);
        
        return response()->json([
            'success' => true,
            'data' => $notificaciones,
            'no_leidas' => $noLeidas,
            'total' => $notificaciones->count()
        ]);
    }
    
    /**
     * Marcar notificación como leída
     */
    public function marcarLeida($id)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }
        
        // Solo permitir a clientes (rol ID 2)
        if ($user->id_rol != 2) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }
        
        $notificacion = Notificacion::find($id);
        
        if (!$notificacion) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], 404);
        }
        
        // Verificar que el usuario tiene acceso a esta notificación
        if (!$notificacion->usuarioPuedeVer($user)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver esta notificación'
            ], 403);
        }
        
        $notificacion->marcarComoLeidaPorUsuario($user->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }
    
    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }
        
        // Solo permitir a clientes (rol ID 2)
        if ($user->id_rol != 2) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }
        
        Notificacion::marcarTodasLeidasPorUsuario($user->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }
    
    /**
     * Contar notificaciones no leídas
     */
    public function countNoLeidas()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'count' => 0
            ]);
        }
        
        // Solo contar si es cliente (rol ID 2)
        if ($user->id_rol != 2) {
            return response()->json([
                'success' => true,
                'count' => 0
            ]);
        }
        
        $count = Notificacion::countNoLeidas($user->id, $user->id_rol);
        
        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }
}