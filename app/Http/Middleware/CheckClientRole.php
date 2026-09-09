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
            
            if ($user->id_rol != 2) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Se requiere cuenta de cliente',
                        'authenticated' => false
                    ], 401);
                }
                
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->back()->with('warning', 'Tu cuenta no tiene permisos de cliente. Por favor, crea una cuenta de cliente.');
            }
        }
        return $next($request);
    }
}