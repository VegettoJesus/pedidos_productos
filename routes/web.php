<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', [LoginController::class, 'logout'])->name('logout'); 
Route::post('/main', [LoginController::class, 'main'])->name('main'); 

Route::get('/main', function () {
    return view('main');  
})->middleware('auth');  

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