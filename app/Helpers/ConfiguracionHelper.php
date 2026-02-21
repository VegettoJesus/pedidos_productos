<?php
// app/Helpers/ConfiguracionHelper.php

namespace App\Helpers;

use App\Models\ConfiguracionSistema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Services\MenuService;

class ConfiguracionHelper
{
    /**
     * Tiempo de caché en segundos (24 horas)
     */
    const CACHE_TIME = 86400;

    /**
     * Obtener configuración con caché
     */
    private static function getConfig()
    {
        return Cache::remember('configuracion_sistema', self::CACHE_TIME, function () {
            return ConfiguracionSistema::first();
        });
    }

    /**
     * Obtener el favicon de la configuración o el default
     */
    public static function getFavicon()
    {
        $configuracion = self::getConfig();
        
        if ($configuracion && $configuracion->icono_site) {
            $iconoSite = $configuracion->icono_site;
            
            // Si es una URL completa
            if (filter_var($iconoSite, FILTER_VALIDATE_URL)) {
                return $iconoSite;
            }
            
            // Verificar si el archivo existe
            if (file_exists(public_path($iconoSite))) {
                return asset($iconoSite);
            }
        }
        
        return asset('img/img-empresa-default.png');
    }
    
    /**
     * Obtener el título de la página (usando abreviatura si existe)
     */
    public static function getPageTitle($seccion = null)
    {
        $configuracion = self::getConfig();
        
        if ($configuracion) {
            $baseTitle = $configuracion->abreviatura_titulo ?? $configuracion->titulo_site ?? 'HTI';
        } else {
            $baseTitle = 'HTI';
        }
        
        if ($seccion) {
            return "{$seccion} | {$baseTitle}";
        }
        
        return $baseTitle;
    }
    
    /**
     * Obtener el nombre completo de la empresa
     */
    public static function getCompanyName()
    {
        $configuracion = self::getConfig();
        return $configuracion->titulo_site ?? 'Mi Empresa';
    }
    
    /**
     * Obtener la abreviatura
     */
    public static function getAbbreviation()
    {
        $configuracion = self::getConfig();
        return $configuracion->abreviatura_titulo ?? 'EMP';
    }

    /**
     * Obtener descripción corta
     */
    public static function getDescription()
    {
        $configuracion = self::getConfig();
        return $configuracion->descripcion_corta ?? 'Sistema de gestión empresarial';
    }

    /**
     * Obtener email del administrador
     */
    public static function getAdminEmail()
    {
        $configuracion = self::getConfig();
        return $configuracion->email_admin ?? config('mail.from.address');
    }

    /**
     * Obtener footer text
     */
    public static function getFooterText()
    {
        $configuracion = self::getConfig();
        return $configuracion->footer_text ?? 'Todos los derechos reservados.';
    }

    /**
     * Limpiar caché de configuración
     */
    public static function clearCache()
    {
        Cache::forget('configuracion_sistema');
        Cache::forget('configuracion_sistema_array');
        
        // Limpiar también caché de menús
        MenuService::limpiarCache();
    }
}