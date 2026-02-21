<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdministracionDelSistema;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\PerfilController;
use App\Http\Middleware\VerificarPermisoMenu;
use App\Services\MenuService;

Route::get('/', [TiendaController::class, 'home'])->name('tienda.home');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::post('/toggle-dark-mode', [LoginController::class, 'toggleDarkMode'])->name('toggle-dark-mode');
Route::get('/get-iconos', function () {
    $path = storage_path('app/iconos.csv');
    if (!file_exists($path)) {
        abort(404, 'Archivo no encontrado');
    }
    return response(file_get_contents($path), 200)
        ->header('Content-Type', 'text/plain'); 
});

Route::get('/main', [LoginController::class, 'main'])
    ->name('main')
    ->middleware('auth');

Route::get('/get-provincias/{id}', [AdministracionDelSistema::class, 'getProvincias']);
Route::get('/get-distritos/{id}', [AdministracionDelSistema::class, 'getDistritos']);

Route::middleware(['auth'])->group(function () {
    Route::get('/configuracion', [PerfilController::class, 'index'])->name('perfil.configuracion');
    Route::post('/perfil/obtener', [PerfilController::class, 'obtenerDatos']);
    Route::post('/perfil/actualizar', [PerfilController::class, 'actualizar']);

    Route::get('/main', [LoginController::class, 'main'])->name('main');
    Route::get('{controlador}/{metodo}', function ($controlador, $metodo) {
        $controllerClass = 'App\\Http\\Controllers\\' . ucfirst($controlador);

        if (class_exists($controllerClass) && method_exists($controllerClass, $metodo)) {
            if (!app(MenuService::class)::tienePermiso("{$controlador}/{$metodo}")) {
                abort(403, 'No tienes permiso para acceder a esta página');
            }
            return app()->call("$controllerClass@$metodo");
        }

        abort(404);
    })->middleware('permiso:ver');

    Route::post('{controlador}/{metodo}', function ($controlador, $metodo) {
        $controllerClass = 'App\\Http\\Controllers\\' . ucfirst($controlador);

        if (class_exists($controllerClass) && method_exists($controllerClass, $metodo)) {
            if (!app(MenuService::class)::tienePermiso("{$controlador}/{$metodo}")) {
                abort(403, 'No tienes permiso para acceder a esta función');
            }
            return app()->call("$controllerClass@$metodo");
        }

        abort(404);
    })->middleware('permiso:ver');

    Route::get('administracion/menus/editar/{id}', [AdministracionDelSistema::class, 'editarMenu'])
        ->middleware('permiso:editar');
        
    Route::post('administracion/menus/crear', [AdministracionDelSistema::class, 'crearMenu'])
        ->middleware('permiso:crear');
        
    Route::delete('administracion/menus/eliminar/{id}', [AdministracionDelSistema::class, 'eliminarMenu'])
        ->middleware('permiso:eliminar');
});