<?php
// app/Providers/CacheServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ConfiguracionSistema;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Support\Facades\Cache;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        ConfiguracionSistema::saved(function ($configuracion) {
            Cache::forget('configuracion_sistema');
            Cache::forget('configuracion_sistema_array');
            MenuService::limpiarTodaLaCache();
        });

        ConfiguracionSistema::deleted(function ($configuracion) {
            Cache::forget('configuracion_sistema');
            Cache::forget('configuracion_sistema_array');
            MenuService::limpiarTodaLaCache();
        });

        Menu::saved(function ($menu) {
            MenuService::limpiarTodaLaCache();
        });

        Menu::deleted(function ($menu) {
            MenuService::limpiarTodaLaCache();
        });
    }
}