<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MenuService;
use Illuminate\Support\Facades\Route;

class VerificarPermisoMenu
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $accion = 'ver')
    {
        // Obtener la ruta actual
        $ruta = $request->path();
        
        // Verificar si el usuario tiene permiso para esta ruta
        if (!MenuService::tienePermiso($ruta, $accion)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'No tienes permiso para acceder a esta función'
                ], 403);
            }
            
            return redirect()->route('main')
                ->with('error', 'No tienes permiso para acceder a esta página');
        }

        return $next($request);
    }
}