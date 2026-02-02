<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TemaColor;
use App\Models\ConfiguracionColor;

class TemaServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Compartir el tema activo con todas las vistas
        view()->composer('*', function ($view) {
            $temaActivo = TemaColor::obtenerActivo();
            $cssTema = '';
            
            if ($temaActivo) {
                $cssTema = ConfiguracionColor::generarCssTemaActivo();
            }
            
            $view->with('temaActivo', $temaActivo)
                 ->with('temaCss', $cssTema);
        });
    }
}