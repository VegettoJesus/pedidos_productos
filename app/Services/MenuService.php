<?php
// app/Services/MenuService.php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Permiso;
use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    /**
     * Tiempo de caché en segundos (1 hora)
     */
    const CACHE_TIME = 3600;

    /**
     * Obtener menús permitidos para el usuario actual
     */
    public static function obtenerMenusPorUsuario()
    {
        $userId = Auth::id();
        $rolId = Auth::user()->id_rol;

        if (!$userId) {
            return [];
        }

        // Sin usar tags, solo con clave única por usuario
        return Cache::remember("menus_usuario_{$userId}", self::CACHE_TIME, function () use ($rolId) {
            // Obtener todos los permisos del rol
            $permisosRol = Permiso::where('id_rol', $rolId)
                ->with('menu')
                ->get()
                ->keyBy('id_menus');

            // Filtrar menús con permiso "ver"
            $menusPermitidos = $permisosRol->filter(function($permiso) {
                return isset($permiso->permisos['ver']) && $permiso->permisos['ver'] === true;
            })->pluck('id_menus');

            // Obtener los menús permitidos
            $menus = Menu::whereIn('id', $menusPermitidos)
                ->orderBy('padre')
                ->orderBy('orden')
                ->get();

            $menuAgrupado = $menus->groupBy('padre');

            return self::construirMenu($menuAgrupado, 0, $permisosRol);
        });
    }

    /**
     * Construir estructura jerárquica del menú
     */
    protected static function construirMenu($menuAgrupado, $padreId, $permisosRol)
    {
        if (!isset($menuAgrupado[$padreId])) return [];

        return $menuAgrupado[$padreId]->map(function ($menu) use ($menuAgrupado, $permisosRol) {
            return [
                'id' => $menu->id,
                'nombre' => $menu->nombre,
                'url' => $menu->url,
                'icono' => $menu->icono ?? 'bi bi-circle',
                'permisos' => isset($permisosRol[$menu->id]) ? $permisosRol[$menu->id]->permisos : [],
                'submenu' => self::construirMenu($menuAgrupado, $menu->id, $permisosRol),
            ];
        })->toArray();
    }

    /**
     * Verifica si el usuario actual tiene un permiso específico en una URL
     */
    public static function tienePermiso($url, $accion = 'ver')
    {
        $menus = self::obtenerMenusPorUsuario();
        
        return self::buscarPermisoEnMenu($menus, $url, $accion);
    }

    /**
     * Búsqueda recursiva de permiso en la estructura de menús
     */
    protected static function buscarPermisoEnMenu($menus, $url, $accion)
    {
        foreach ($menus as $menu) {
            // Limpiar URLs para comparación
            $menuUrl = trim($menu['url'], '/');
            $targetUrl = trim($url, '/');
            
            if ($menuUrl === $targetUrl || $menuUrl === $targetUrl . '/') {
                return !empty($menu['permisos'][$accion]) && $menu['permisos'][$accion] === true;
            }
            
            if (!empty($menu['submenu'])) {
                $found = self::buscarPermisoEnMenu($menu['submenu'], $url, $accion);
                if ($found) return true;
            }
        }
        
        return false;
    }

    /**
     * Limpiar caché de menús del usuario - Versión sin tags
     */
    public static function limpiarCache($userId = null)
    {
        if ($userId) {
            Cache::forget("menus_usuario_{$userId}");
        } else {
            $version = Cache::get('menus_cache_version', 1);
            Cache::forever('menus_cache_version', $version + 1);
        }
    }

    /**
     * Obtener clave de caché con versión - Para invalidación por grupo
     */
    protected static function getCacheKey($key)
    {
        $version = Cache::get('menus_cache_version', 1);
        return "{$key}_v{$version}";
    }

    /**
     * Versión alternativa que usa versión para invalidación por grupo
     */
    public static function obtenerMenusPorUsuarioConVersion()
    {
        $userId = Auth::id();
        $rolId = Auth::user()->id_rol;

        if (!$userId) {
            return [];
        }

        $cacheKey = self::getCacheKey("menus_usuario_{$userId}");

        return Cache::remember($cacheKey, self::CACHE_TIME, function () use ($rolId) {
            // Misma lógica de obtención de menús
            $permisosRol = Permiso::where('id_rol', $rolId)
                ->with('menu')
                ->get()
                ->keyBy('id_menus');

            $menusPermitidos = $permisosRol->filter(function($permiso) {
                return isset($permiso->permisos['ver']) && $permiso->permisos['ver'] === true;
            })->pluck('id_menus');

            $menus = Menu::whereIn('id', $menusPermitidos)
                ->orderBy('padre')
                ->orderBy('orden')
                ->get();

            $menuAgrupado = $menus->groupBy('padre');

            return self::construirMenu($menuAgrupado, 0, $permisosRol);
        });
    }

    /**
     * Limpiar toda la caché de menús incrementando la versión
     */
    public static function limpiarTodaLaCache()
    {
        $version = Cache::get('menus_cache_version', 1);
        Cache::forever('menus_cache_version', $version + 1);
    }
}