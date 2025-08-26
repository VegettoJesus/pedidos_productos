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

        $menusPermitidos = Permiso::where('id_rol', $rolId)
            ->where('ver', true)
            ->pluck('id_menus');

        $menus = Menu::whereIn('id', $menusPermitidos)
            ->orderBy('padre')
            ->orderBy('orden')
            ->get();

        // Agrupa por padre para construir jerarquía
        $menuAgrupado = $menus->groupBy('padre');

        return self::construirMenu($menuAgrupado, 0); // 0 = raíz
    }

    protected static function construirMenu($menuAgrupado, $padreId)
    {
        if (!isset($menuAgrupado[$padreId])) return [];

        return $menuAgrupado[$padreId]->map(function ($menu) use ($menuAgrupado) {
            return [
                'id' => $menu->id,
                'nombre' => $menu->nombre,
                'url' => $menu->url,
                'icono' => $menu->icono,
                'submenu' => self::construirMenu($menuAgrupado, $menu->id),
            ];
        });
    }
}
