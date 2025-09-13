<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Services\MenuService;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            $view->with('menusDinamicos', Auth::check() ? MenuService::obtenerMenusPorUsuario() : []);
            $view->with('darkMode', Auth::check() ? (bool) Auth::user()->dark_mode : false);
        });
        Blade::if('permiso', function ($url, $accion) {
            return MenuService::tienePermiso($url, $accion);
        });
    }
}
