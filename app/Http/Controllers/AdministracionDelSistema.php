<?php

namespace App\Http\Controllers;
use App\Models\Model_AdministracionDelSistema;
use Illuminate\Http\Request;
use App\Models\Rol;
use App\Models\Menu;
use App\Models\Permiso;

class AdministracionDelSistema extends Controller
{
    public function administrarMenu(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $idPadre = $request->input('id_padre');
                    $idRol   = $request->input('id_rol');

                    $query = Menu::query()->orderBy('orden');

                    if ($idPadre) {
                        // Mostrar solo hijos del padre seleccionado
                        $query->where('padre', $idPadre);
                    } else {
                        // Mostrar padres si no hay filtro de padre
                        $query->whereNull('padre')->orWhere('padre', 0);
                    }

                    $menus = $query->get();

                    // Si hay rol, filtramos menús con permisos de ese rol
                    if ($idRol) {
                        $menus = $menus->filter(function ($menu) use ($idRol) {
                            return Permiso::where('id_menus', $menu->id)
                                        ->where('id_rol', $idRol)
                                        ->exists();
                        })->values();
                    }

                    $data->respuesta = 'ok';
                    $data->menus = $menus;
                    break;


                case 'Permisos':
                    $idMenu = $request->input('id_menu');
                    $roles = Rol::all();

                    $permisos = Permiso::where('id_menus', $idMenu)->get()->keyBy('id_rol');

                    $data->respuesta = 'ok';
                    $data->roles = $roles;
                    $data->permisos = $permisos;
                    break;

                case 'ActualizarPermiso':
                    $idMenu = $request->input('id_menu');
                    $idRol = $request->input('id_rol');
                    $campo = $request->input('campo');
                    $valor = $request->input('valor') ? 1 : 0;

                    $permiso = Permiso::firstOrCreate([
                        'id_menus' => $idMenu,
                        'id_rol'   => $idRol
                    ]);

                    $permiso->$campo = $valor;
                    $permiso->save();

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Permiso actualizado';
                    break;

                case 'Crear':
                    $tipo   = $request->input('tipo'); 
                    $nombre = $request->input('nombre');
                    $icono  = $request->input('icono'); 
                    $url    = null;
                    $padre  = 0;
                    $orden  = 1;

                    // Función para convertir en CamelCase eliminando espacios
                    function toCamelCase($string) {
                        $words = explode(' ', $string);
                        $camel = '';
                        foreach ($words as $w) {
                            $camel .= ucfirst(strtolower($w));
                        }
                        return $camel;
                    }

                    if ($tipo === 'padre') {
                        // último orden de padres
                        $ultimo = Menu::whereNull('padre')
                                    ->orWhere('padre', 0)
                                    ->max('orden');
                        $orden = $ultimo ? $ultimo + 1 : 1;

                        $url = $request->input('url') ?: '#'; 
                        $padre = 0;
                    } else {
                        // hijo
                        $padreSeleccionado = $request->input('padre_id');
                        $padreMenu = Menu::find($padreSeleccionado);

                        if (!$padreMenu) {
                            $data->respuesta = 'error';
                            $data->mensaje = 'Padre no encontrado';
                            break;
                        }

                        // generar URL del hijo usando nombre del padre en CamelCase
                        $nombrePadreCamel = toCamelCase($padreMenu->nombre);
                        $url = $nombrePadreCamel . '/' . strtolower(str_replace(' ', '-', $nombre));

                        // cambiar URL del padre a "#"
                        $padreMenu->url = '#';
                        $padreMenu->save();

                        $ultimoHijo = Menu::where('padre', $padreSeleccionado)->max('orden');
                        $orden = $ultimoHijo ? $ultimoHijo + 1 : 1;

                        $padre = $padreSeleccionado;
                    }

                    $menu = new Menu();
                    $menu->nombre = $nombre;
                    $menu->icono = $icono; 
                    $menu->url = $url;
                    $menu->padre = $padre;
                    $menu->orden = $orden;
                    $menu->save();

                    $data->respuesta = 'ok';
                    $data->mensaje = "Menú creado correctamente";
                    $data->menu = $menu;
                    break;

                case 'Eliminar':
                    $idMenu = $request->input('id_menu');
                    $menu = Menu::find($idMenu);

                    if (!$menu) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Menú no encontrado';
                        break;
                    }

                    // función recursiva para eliminar hijos y permisos
                    function eliminarMenuRecursivo($menuId) {
                        // eliminar permisos asociados
                        Permiso::where('id_menus', $menuId)->delete();

                        // buscar hijos
                        $hijos = Menu::where('padre', $menuId)->get();
                        foreach ($hijos as $hijo) {
                            eliminarMenuRecursivo($hijo->id);
                            $hijo->delete();
                        }
                    }

                    // primero eliminar hijos y permisos
                    eliminarMenuRecursivo($menu->id);

                    // eliminar el propio menú y sus permisos
                    Permiso::where('id_menus', $menu->id)->delete();
                    $menu->delete();

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Menú eliminado correctamente';
                    break;
                    
                case 'Editar':
                    $idMenu = $request->input('id_menu');
                    $menu = Menu::find($idMenu);

                    if (!$menu) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Menú no encontrado';
                        break;
                    }

                    // si es padre
                    if ($menu->padre == 0 || is_null($menu->padre)) {
                        $data->menu = [
                            'id' => $menu->id,
                            'nombre' => $menu->nombre,
                            'url' => $menu->url,
                            'icono' => $menu->icono,
                            'tipo' => 'padre'
                        ];
                    } else {
                        // si es hijo
                        $data->menu = [
                            'id' => $menu->id,
                            'padre_id' => $menu->padre,
                            'nombre' => $menu->nombre,
                            'tipo' => 'hijo'
                        ];
                    }

                    $data->respuesta = 'ok';
                    break;

                case 'Actualizar':
                    $idMenu = $request->input('id');
                    $menu = Menu::find($idMenu);

                    if (!$menu) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Menú no encontrado';
                        break;
                    }

                    $tipo   = $request->input('tipo'); 
                    $nombre = $request->input('nombre');
                    $icono  = $request->input('icono'); 

                    if ($tipo === 'padre') {
                        // Actualizar como padre
                        $menu->nombre = $nombre;
                        $menu->url    = $request->input('url') ?: '#';
                        $menu->icono  = $icono;
                        $menu->padre  = 0;

                    } else {
                        // Actualizar como hijo
                        $padreSeleccionado = $request->input('padre_id');
                        $padreMenu = Menu::find($padreSeleccionado);

                        if (!$padreMenu) {
                            $data->respuesta = 'error';
                            $data->mensaje = 'Padre no encontrado';
                            break;
                        }

                        $menu->nombre = $nombre;
                        $menu->padre  = $padreSeleccionado;
                        $menu->icono  = $icono;
                    }

                    $menu->save();

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Menú actualizado correctamente';
                    $data->menu = $menu;
                    break;
                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->script = 'js/administrarMenu.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'administracionDelSistema.administrarMenu';
            $data->padres = Menu::whereNull('padre')->orWhere('padre', 0)->get();
            $data->roles = Rol::all();
            return view('layouts.contenido', (array) $data);
        }
    }

}