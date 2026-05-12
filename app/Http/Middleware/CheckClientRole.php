<?php
// app/Http/Middleware/CheckClientRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckClientRole
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Cargar relación rol
            if (!$user->relationLoaded('rol')) {
                $user->load('rol');
            }
            $isClient = $user->rol && $user->rol->name === 'client';

            if (!$isClient) {
                // Para peticiones AJAX (como la valoración), devolvemos 401 sin cerrar sesión
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Se requiere cuenta de cliente'], 401);
                }
                // Para peticiones normales, cerramos sesión y redirigimos
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->back()->with('warning', 'Tu cuenta no tiene permisos de cliente. Por favor, crea una cuenta de cliente.');
            }
        }
        return $next($request);
    }
}