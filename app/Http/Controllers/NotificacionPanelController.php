<?php
// app/Http/Controllers/NotificacionPanelController.php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\PermisoNotificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionPanelController extends Controller
{
    /**
     * Verificar que el usuario NO sea cliente
     */
    private function verificarAccesoPanel()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }
        
        if ($user->id_rol == 2) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Los clientes usan otro endpoint.'
            ], 403);
        }
        
        return null; // OK
    }

    /**
     * Listar notificaciones del usuario del PANEL
     */
    public function index(Request $request)
    {
        if ($response = $this->verificarAccesoPanel()) return $response;
        
        $user = Auth::user();
        $limit = $request->get('limit', 20);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $limit;
        
        $notificaciones = Notificacion::getNotificacionesParaUsuarioPanelPaginadas($user->id, $limit, $page);
        $noLeidas = Notificacion::countNoLeidasPanel($user->id);
        
        return response()->json([
            'success' => true,
            'data' => $notificaciones->items(),
            'no_leidas' => $noLeidas,
            'pagination' => [
                'current_page' => $notificaciones->currentPage(),
                'last_page' => $notificaciones->lastPage(),
                'per_page' => $notificaciones->perPage(),
                'total' => $notificaciones->total(),
                'has_more' => $notificaciones->hasMorePages(),
            ]
        ]);
    }

    /**
     * Solo notificaciones no leídas
     */
    public function noLeidas(Request $request)
    {
        if ($response = $this->verificarAccesoPanel()) return $response;
        
        $user = Auth::user();
        $limit = $request->get('limit', 20);
        
        $notificaciones = Notificacion::getNoLeidasPanel($user->id, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $notificaciones,
            'count' => $notificaciones->count(),
        ]);
    }

    /**
     * Marcar como leída
     */
    public function marcarLeida($id)
    {
        if ($response = $this->verificarAccesoPanel()) return $response;
        
        $user = Auth::user();
        $notificacion = Notificacion::find($id);
        
        if (!$notificacion) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], 404);
        }
        
        // Verificar que puede verla (con excepciones)
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
     * Marcar todas como leídas
     */
    public function marcarTodasLeidas()
    {
        if ($response = $this->verificarAccesoPanel()) return $response;
        
        $user = Auth::user();
        Notificacion::marcarTodasLeidasPanel($user->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }

    /**
     * Contar no leídas (para el badge)
     */
    public function countNoLeidas()
    {
        $user = Auth::user();
        
        if (!$user || $user->id_rol == 2) {
            return response()->json([
                'success' => true,
                'count' => 0
            ]);
        }
        
        $count = Notificacion::countNoLeidasPanel($user->id);
        
        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Vista completa de notificaciones del panel
     */
    public function vista(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->id_rol == 2) {
            return redirect()->route('tienda.home')->with('warning', 'No tienes permisos para acceder a esta página.');
        }
        
        $data = new \stdClass();
        $data->script = 'js/notificacionesPanel.js';
        $data->css = 'css/notificaciones.css';
        $data->contenido = 'notificaciones.vista';
        
        $data->usuario = $user;
        
        $data->notificaciones = Notificacion::getNotificacionesParaUsuarioPanelPaginadas($user->id, 15);
        $data->noLeidas = Notificacion::countNoLeidasPanel($user->id);
        
        $data->tiposNotificacion = TipoNotificacion::where('activo', true)
            ->orderBy('nombre')
            ->get();
        
        return view('layouts.contenido', (array) $data);
    }
}