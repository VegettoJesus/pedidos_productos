<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdministracionDelSistema;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
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
Route::get('/main', [LoginController::class, 'main'])->name('main')->middleware('auth');
Route::get('/get-provincias/{id}', [AdministracionDelSistema::class, 'getProvincias']);
Route::get('/get-distritos/{id}', [AdministracionDelSistema::class, 'getDistritos']);

Route::get('{controlador}/{metodo}', function ($controlador, $metodo) {
    $controllerClass = 'App\\Http\\Controllers\\' . ucfirst($controlador);

    if (class_exists($controllerClass) && method_exists($controllerClass, $metodo)) {
        return app()->call("$controllerClass@$metodo");
    } else {
        abort(404); 
    }
})->middleware('auth');

Route::post('{controlador}/{metodo}', function ($controlador, $metodo) {
    $controllerClass = 'App\\Http\\Controllers\\' . ucfirst($controlador);

    if (class_exists($controllerClass) && method_exists($controllerClass, $metodo)) {
        return app()->call("$controllerClass@$metodo");
    } else {
        abort(404);
    }
})->middleware('auth');