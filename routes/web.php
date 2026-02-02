<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdministracionDelSistema;
use App\Http\Controllers\TiendaController;

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
Route::get('/main', [LoginController::class, 'main'])->name('main')->middleware('auth');
Route::get('/get-provincias/{id}', [AdministracionDelSistema::class, 'getProvincias']);
Route::get('/get-distritos/{id}', [AdministracionDelSistema::class, 'getDistritos']);
Route::controller(Temas::class)->group(function () {
    Route::get('/temas-colores', 'editorAdministracion')->name('temas.editor');
    Route::post('/temas-colores', 'editorAdministracion');
    Route::post('/temas/previsualizar', 'previsualizarTema');
    Route::get('/temas-css', 'obtenerCssActivo'); // Para cargar CSS dinámico
});

Route::middleware('auth')->group(function () {

    Route::get('/main', [LoginController::class, 'main'])->name('main');

    Route::get('{controlador}/{metodo}', function ($controlador, $metodo) {
        $controllerClass = 'App\\Http\\Controllers\\' . ucfirst($controlador);

        if (class_exists($controllerClass) && method_exists($controllerClass, $metodo)) {
            return app()->call("$controllerClass@$metodo");
        }

        abort(404);
    });

    Route::post('{controlador}/{metodo}', function ($controlador, $metodo) {
        $controllerClass = 'App\\Http\\Controllers\\' . ucfirst($controlador);

        if (class_exists($controllerClass) && method_exists($controllerClass, $metodo)) {
            return app()->call("$controllerClass@$metodo");
        }

        abort(404);
    });

});
