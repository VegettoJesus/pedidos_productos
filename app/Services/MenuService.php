<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Permiso;
use App\Models\Menu;

class MenuService
{
    public static function obtenerMenusPorUsuario()
    {
        $rolId = Auth::user()->id_rol;

        // Obtener todos los permisos del rol
        $permisosRol = Permiso::where('id_rol', $rolId)->get()->keyBy('id_menus');

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
    }

    protected static function construirMenu($menuAgrupado, $padreId, $permisosRol)
    {
        if (!isset($menuAgrupado[$padreId])) return [];

        return $menuAgrupado[$padreId]->map(function ($menu) use ($menuAgrupado, $permisosRol) {
            return [
                'id' => $menu->id,
                'nombre' => $menu->nombre,
                'url' => $menu->url,
                'icono' => $menu->icono,
                'permisos' => $permisosRol[$menu->id]->permisos ?? [], // 👈 aquí están los permisos
                'submenu' => self::construirMenu($menuAgrupado, $menu->id, $permisosRol),
            ];
        });
    }

    /**
     * Verifica si el usuario actual tiene un permiso en un menú.
     */
    public static function tienePermiso($url, $accion)
    {
        $menus = self::obtenerMenusPorUsuario();

        $buscar = function($menus, $url, $accion) use (&$buscar) {
            foreach ($menus as $menu) {
                if ($menu['url'] === $url) {
                    return !empty($menu['permisos'][$accion]) && $menu['permisos'][$accion] === true;
                }
                if (!empty($menu['submenu'])) {
                    $found = $buscar($menu['submenu'], $url, $accion);
                    if ($found) return true;
                }
            }
            return false;
        };

        return $buscar($menus, $url, $accion);
    }
}
