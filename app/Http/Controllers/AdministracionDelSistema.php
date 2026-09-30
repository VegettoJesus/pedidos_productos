<?php

namespace App\Http\Controllers;

use App\Traits\AuditableTrait;
use App\Models\Departamento;
use App\Models\Distrito;
use Illuminate\Http\Request;
use App\Models\Rol;
use App\Models\Menu;
use App\Data\Icons;
use App\Models\Permiso;
use App\Models\Provincia;
use App\Models\User;
use App\Models\UsuarioDato;
use App\Services\MenuService;
use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\PermisoNotificacion;
use App\Models\ExcepcionNotificacionUsuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdministracionDelSistema extends Controller
{
    use AuditableTrait;

    public function administrarMenu(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $idPadre = $request->input('id_padre');
                    $idRol   = $request->input('id_rol');
                    $currentUrl = $request->path(); 

                    $puedeEditar = MenuService::tienePermiso($currentUrl, 'editar');
                    $puedeEliminar = MenuService::tienePermiso($currentUrl, 'eliminar');
                    $puedeConfigurar = MenuService::tienePermiso($currentUrl, 'configurar');

                    $data->permisosVista = [
                        'editar'     => $puedeEditar,
                        'eliminar'   => $puedeEliminar,
                        'configurar' => $puedeConfigurar,
                    ];
                    
                    $query = Menu::query()->orderBy('orden');

                    if ($idPadre) {
                        $query->where('padre', $idPadre);
                    } else {
                        $query->whereNull('padre')->orWhere('padre', 0);
                    }

                    $menus = $query->get();

                    if ($idRol) { 
                        $menus = $menus->filter(function ($menu) use ($idRol) { 
                            return Permiso::where('id_menus', $menu->id)
                                ->where('id_rol', $idRol)
                                ->whereJsonContains('permisos->ver', true) // Solo menús con permiso "ver"
                                ->exists(); 
                        })->values(); 
                    }

                    $data->respuesta = 'ok';
                    $data->menus = $menus;
                    break;

                case 'Permisos':
                    $idMenu = $request->input('id_menu');
                    $roles = Rol::where('id', '!=', 2)  
                    ->orderBy('name')
                    ->get();

                    // Obtener solo el campo 'permisos' sin decodificar, porque ya es array
                    $permisos = Permiso::where('id_menus', $idMenu)
                        ->get()
                        ->mapWithKeys(function($item) {
                            return [$item->id_rol => $item->permisos ?? []];
                        });

                    $data->respuesta = 'ok';
                    $data->roles = $roles;
                    $data->permisos = $permisos;
                    break;

                case 'ActualizarPermiso':
                    $idMenu = $request->input('id_menu');
                    $idRol = $request->input('id_rol');
                    $campo = $request->input('campo');
                    $valor = $request->input('valor') ? true : false;

                    if ($idRol == 2) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'No puedes modificar permisos del rol cliente.';
                        break;
                    }

                    try {
                        $menu = Menu::find($idMenu);
                        $rol = Rol::find($idRol);
                        
                        $nombreMenu = $menu ? $menu->nombre : "ID: {$idMenu}";
                        $nombreRol = $rol ? $rol->name : "ID: {$idRol}";
                        
                        $permiso = Permiso::firstOrCreate([
                            'id_menus' => $idMenu,
                            'id_rol'   => $idRol
                        ]);
                        
                        $permisosAnteriores = $permiso->permisos ?? [];
                        $valorAnterior = $permisosAnteriores[$campo] ?? false;
                        
                        if ($valorAnterior !== $valor) {
                            $permisosArray = $permiso->permisos ?? []; 
                            $permisosArray[$campo] = $valor;           
                            $permiso->permisos = $permisosArray;
                            $permiso->save();
                            
                            $nombreCampo = $this->traducirNombrePermiso($campo);
                            $estado = $valor ? 'concedido' : 'revocado';
                            
                            $this->registrarAuditoria(
                                'Actualizar',
                                'permisos',
                                $permiso->id,
                                "Permiso: {$nombreMenu} / {$nombreRol}",
                                null,
                                null,
                                "Permiso '{$nombreCampo}' {$estado}"
                            );
                            
                            MenuService::limpiarTodaLaCache();
                        }

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Permiso actualizado correctamente';
                        
                    } catch (\Exception $e) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al actualizar permiso: ' . $e->getMessage();
                    }
                    break;

                case 'Crear':
                    $tipo   = $request->input('tipo'); 
                    $nombre = $request->input('nombre');
                    $icono  = $request->input('icono'); 
                    $url    = null;
                    $padre  = 0;
                    $orden  = 1;

                    function toCamelCase($string) {
                        $words = explode(' ', $string);
                        $camel = '';
                        foreach ($words as $w) {
                            $camel .= ucfirst(strtolower($w));
                        }
                        return $camel;
                    }

                    DB::beginTransaction();
                    try {
                        if ($tipo === 'padre') {
                            $ultimo = Menu::whereNull('padre')
                                        ->orWhere('padre', 0)
                                        ->max('orden');
                            $orden = $ultimo ? $ultimo + 1 : 1;

                            $url = $request->input('url') ?: '#'; 
                            $padre = 0;
                            
                            $menu = Menu::create([
                                'nombre' => $nombre,
                                'icono' => $icono,
                                'url' => $url,
                                'padre' => $padre,
                                'orden' => $orden
                            ]);
                            
                            $this->registrarAuditoria(
                                'Crear',
                                'menus',
                                $menu->id,
                                $menu->nombre,
                                null,
                                null,
                                "Tipo: Padre | Orden: {$orden} | URL: {$url}"
                            );
                            
                        } else {
                            $padreSeleccionado = $request->input('padre_id');
                            $padreMenu = Menu::findOrFail($padreSeleccionado);

                            $nombrePadreCamel = toCamelCase($padreMenu->nombre);
                            $url = $nombrePadreCamel . '/' . strtolower(str_replace(' ', '-', $nombre));

                            $ultimoHijo = Menu::where('padre', $padreSeleccionado)->max('orden');
                            $orden = $ultimoHijo ? $ultimoHijo + 1 : 1;

                            $nombrePadreOriginal = $padreMenu->nombre;
                            $urlPadreOriginal = $padreMenu->url;
                            $padreTeniaUrl = ($urlPadreOriginal !== '#');
                            
                            $menu = Menu::create([
                                'nombre' => $nombre,
                                'icono' => $icono,
                                'url' => $url,
                                'padre' => $padreSeleccionado,
                                'orden' => $orden
                            ]);
                            
                            if ($padreTeniaUrl) {
                                $padreMenu->update(['url' => '#']);
                            }
                            
                            $detalleExtra = "Tipo: Hijo | Padre: {$nombrePadreOriginal} | Orden: {$orden} | URL: {$url}";
                            
                            if ($padreTeniaUrl) {
                                $detalleExtra .= " | Se actualizó URL del padre '{$nombrePadreOriginal}' de '{$urlPadreOriginal}' a '#' (ahora es contenedor)";
                            }
                            
                            $this->registrarAuditoria(
                                'Crear',
                                'menus',
                                $menu->id,
                                $menu->nombre,
                                null,
                                null,
                                $detalleExtra
                            );
                        }
                        
                        DB::commit();
                        MenuService::limpiarTodaLaCache();
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = "Menú '{$nombre}' creado correctamente";
                        $data->menu = $menu;
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al crear menú: ' . $e->getMessage();
                    }
                    break;

                case 'Eliminar':
                    $idMenu = $request->input('id_menu');
                    $menu = Menu::find($idMenu);

                    if (!$menu) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Menú no encontrado';
                        break;
                    }

                    DB::beginTransaction();
                    try {
                        $nombreMenu = $menu->nombre;
                        $idMenuEliminado = $menu->id;
                        $tipoMenu = $menu->padre == 0 ? 'Padre' : 'Hijo';
                        $cantidadPermisos = Permiso::where('id_menus', $menu->id)->count();
                        $cantidadHijos = 0;
                        $hijosNombres = [];
                        
                        function contarHijosRecursivo($menuId, &$contador, &$nombres) {
                            $hijos = Menu::where('padre', $menuId)->get();
                            foreach ($hijos as $hijo) {
                                $contador++;
                                $nombres[] = $hijo->nombre;
                                contarHijosRecursivo($hijo->id, $contador, $nombres);
                            }
                        }
                        
                        contarHijosRecursivo($menu->id, $cantidadHijos, $hijosNombres);
                        
                        function eliminarMenuRecursivo($menuId) {
                            Permiso::where('id_menus', $menuId)->delete();
                            $hijos = Menu::where('padre', $menuId)->get();
                            foreach ($hijos as $hijo) {
                                eliminarMenuRecursivo($hijo->id);
                                $hijo->delete();
                            }
                        }

                        eliminarMenuRecursivo($menu->id);
                        
                        Permiso::where('id_menus', $menu->id)->delete();
                        $menu->delete();

                        $detalleExtra = "Tipo: {$tipoMenu}";
                        if ($cantidadPermisos > 0) {
                            $detalleExtra .= " | Permisos eliminados: {$cantidadPermisos}";
                        }
                        if ($cantidadHijos > 0) {
                            $detalleExtra .= " | Submenús eliminados: {$cantidadHijos} (" . implode(', ', $hijosNombres) . ")";
                        }
                        
                        $this->registrarAuditoria(
                            'Eliminar',
                            'menus',
                            $idMenuEliminado,
                            $nombreMenu,
                            null,
                            null,
                            $detalleExtra
                        );
                        
                        DB::commit();
                        MenuService::limpiarTodaLaCache();
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Menú eliminado correctamente';
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al eliminar menú: ' . $e->getMessage();
                    }
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

                    DB::beginTransaction();
                    try {
                        $valoresAnteriores = [
                            'nombre' => $menu->nombre,
                            'icono' => $menu->icono,
                            'url' => $menu->url,
                            'padre' => $menu->padre
                        ];
                        
                        $tipo = $request->input('tipo'); 
                        $nombre = $request->input('nombre');
                        $icono = $request->input('icono'); 
                        $detalleExtra = "";

                        if ($tipo === 'padre') {
                            $nuevaUrl = $request->input('url') ?: '#';
                            
                            $menu->nombre = $nombre;
                            $menu->url = $nuevaUrl;
                            $menu->icono = $icono;
                            $menu->padre = 0;
                            
                            $detalleExtra = "Tipo: Padre";
                            if ($valoresAnteriores['padre'] != 0) {
                                $detalleExtra .= " | Cambiado de Hijo a Padre";
                            }
                            if ($valoresAnteriores['url'] != $nuevaUrl) {
                                $detalleExtra .= " | URL: '{$valoresAnteriores['url']}' → '{$nuevaUrl}'";
                            }

                        } else {
                            $padreSeleccionado = $request->input('padre_id');
                            $padreMenu = Menu::find($padreSeleccionado);

                            if (!$padreMenu) {
                                throw new \Exception('Padre no encontrado');
                            }

                            $menu->nombre = $nombre;
                            $menu->padre = $padreSeleccionado;
                            $menu->icono = $icono;
                            
                            $detalleExtra = "Tipo: Hijo | Padre: {$padreMenu->nombre}";
                            if ($valoresAnteriores['padre'] != $padreSeleccionado) {
                                $padreAnterior = Menu::find($valoresAnteriores['padre']);
                                $nombrePadreAnterior = $padreAnterior ? $padreAnterior->nombre : 'Ninguno';
                                $detalleExtra .= " | Padre anterior: {$nombrePadreAnterior}";
                            }
                        }

                        $menu->save();
                        
                        $valoresNuevos = [
                            'nombre' => $menu->nombre,
                            'icono' => $menu->icono,
                            'url' => $menu->url,
                            'padre' => $menu->padre
                        ];
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'menus',
                            $menu->id,
                            $menu->nombre,
                            $valoresAnteriores,
                            $valoresNuevos,
                            $detalleExtra
                        );
                        
                        DB::commit();
                        MenuService::limpiarTodaLaCache();
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Menú actualizado correctamente';
                        $data->menu = $menu;
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al actualizar menú: ' . $e->getMessage();
                    }
                    break;
                case 'ActualizarURL':
                    $idMenu = $request->input('id_menu');
                    $nuevaUrl = $request->input('url');
                    $menu = Menu::find($idMenu);

                    if (!$menu) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Menú no encontrado';
                        break;
                    }

                    DB::beginTransaction();
                    try {
                        $urlAnterior = $menu->url;
                        $nombreMenu = $menu->nombre;
                        
                        $menu->url = $nuevaUrl;
                        $menu->save();
                        $detalleExtra = "URL cambiada: '{$urlAnterior}' → '{$nuevaUrl}'";
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'menus',
                            $menu->id,
                            $menu->nombre,
                            ['url' => $urlAnterior],
                            ['url' => $nuevaUrl],
                            $detalleExtra
                        );
                        
                        DB::commit();
                        MenuService::limpiarTodaLaCache();
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = 'URL actualizada correctamente';
                        $data->menu = $menu;
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al actualizar URL: ' . $e->getMessage();
                    }
                    break;
                case 'ActualizarOrden':
                    $menusOrdenados = $request->input('menus', []);
                    $idPadre = $request->input('id_padre', 0);
                    
                    if (empty($menusOrdenados)) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'No hay menús para ordenar';
                        break;
                    }
                    
                    DB::beginTransaction();
                    try {
                        $cambiosRealizados = [];
                        
                        foreach ($menusOrdenados as $index => $menuData) {
                            $menu = Menu::find($menuData['id']);
                            if ($menu) {
                                $ordenAnterior = $menu->orden;
                                $nuevoOrden = $index + 1;
                                
                                if ($ordenAnterior != $nuevoOrden) {
                                    $menu->orden = $nuevoOrden;
                                    $menu->padre = $idPadre;
                                    $menu->save();
                                    
                                    $cambiosRealizados[] = "{$menu->nombre}: orden {$ordenAnterior} → {$nuevoOrden}";
                                }
                            }
                        }
                        
                        if (!empty($cambiosRealizados)) {
                            $nombrePadre = $idPadre ? (Menu::find($idPadre)->nombre ?? "ID: {$idPadre}") : 'Raíz';
                            $detalleExtra = "Padre: {$nombrePadre} | " . implode('; ', $cambiosRealizados);
                            
                            $this->registrarAuditoria(
                                'Actualizar',
                                'menus',
                                null,  
                                'Orden de menús',
                                null,
                                null,
                                $detalleExtra
                            );
                        }
                        
                        DB::commit();
                        MenuService::limpiarTodaLaCache();
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Orden actualizado correctamente';
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al actualizar el orden: ' . $e->getMessage();
                    }
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
            $data->roles = Rol::where('id', '!=', 2)
            ->orderBy('name')
            ->get();
            $data->iconos = Icons::all();
            return view('layouts.contenido', (array) $data);
        }
    }

    public function usuarios(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $estado = $request->input('filtro_estado');
                    $rol    = $request->input('filtro_roles');

                    $query = User::with('rol');

                    if ($estado !== null && $estado !== '') {
                        $query->where('estado', $estado);
                    }

                    if ($rol !== null && $rol !== '') {
                        $query->where('id_rol', $rol);
                    }

                    $usuarios = $query->get();
                    $currentUrl = $request->path();
                    $data->permisosVista = [
                        'crear' => MenuService::tienePermiso($currentUrl, 'crear'),
                        'editar' => MenuService::tienePermiso($currentUrl, 'editar'),
                        'eliminar' => MenuService::tienePermiso($currentUrl, 'eliminar'),
                        'roles' => MenuService::tienePermiso($currentUrl, 'roles'),
                    ];

                    $data->respuesta = 'ok';
                    $data->usuarios = $usuarios;
                    break;

                case 'Eliminar':
                    $id = $request->input('id');
                    $user = User::with('rol', 'datos')->find($id);

                    if ($user) {
                        DB::beginTransaction();
                        try {
                            $nombreCompleto = $user->nombres . ' ' . $user->apellidos;
                            $email = $user->email;
                            $rolNombre = $user->rol ? $user->rol->name : 'Sin rol';
                            
                            $user->estado = 0;
                            $user->deleted_at = now();
                            $user->save();
                            
                            $detalleExtra = "Email: {$email} | Rol: {$rolNombre}";
                            
                            $this->registrarAuditoria(
                                'Eliminar',
                                'users',
                                $user->id,
                                $nombreCompleto,
                                null,
                                null,
                                $detalleExtra
                            );
                            
                            DB::commit();
                            $data->respuesta = 'ok';
                            $data->mensaje = 'Usuario desactivado correctamente';
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $data->respuesta = 'error';
                            $data->mensaje = 'Error al desactivar usuario: ' . $e->getMessage();
                        }
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Usuario no encontrado';
                    }
                    break;

                case 'EliminarMultiple':
                    $ids = $request->input('ids', []);
                    if (!empty($ids)) {
                        DB::beginTransaction();
                        try {
                            $usuarios = User::with('rol')->whereIn('id', $ids)->get();
                            $usuariosProcesados = [];
                            
                            foreach ($usuarios as $user) {
                                $nombreCompleto = $user->nombres . ' ' . $user->apellidos;
                                $usuariosProcesados[] = "{$nombreCompleto} ({$user->email})";
                                
                                $user->estado = 0;
                                $user->deleted_at = now();
                                $user->save();
                                
                                $this->registrarAuditoria(
                                    'Eliminar',
                                    'users',
                                    $user->id,
                                    $nombreCompleto,
                                    null,
                                    null,
                                    "Eliminación múltiple"
                                );
                            }
                            
                            DB::commit();
                            
                            $detalleExtra = "Usuarios desactivados: " . implode('; ', $usuariosProcesados);
                            $this->registrarAuditoria(
                                'EliminarMultiple',
                                'users',
                                null,
                                'Usuarios múltiples',
                                null,
                                null,
                                $detalleExtra
                            );
                            
                            $data->respuesta = 'ok';
                            $data->mensaje = count($usuariosProcesados) . ' usuario(s) desactivado(s) correctamente';
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $data->respuesta = 'error';
                            $data->mensaje = 'Error al desactivar usuarios: ' . $e->getMessage();
                        }
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'No se enviaron usuarios';
                    }
                    break;
                case 'Registrar':
                    $email = $request->input('email');
                    $tipoDoc = $request->input('tipoDoc');
                    $numeroDoc = $request->input('numeroDoc');
                    $id_user = $request->input('id_user');

                    if (User::where('email', $email)->when($id_user != "0", function($query) use ($id_user){
                        return $query->where('id', '!=', $id_user);
                    })->exists()) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'El correo electrónico ingresado ya está registrado.';
                        return response()->json($data);
                    }

                    if (UsuarioDato::where('tipoDoc', $tipoDoc)
                            ->where('numeroDoc', $numeroDoc)
                            ->whereHas('user', function($q) use ($request, $id_user) {
                                $q->where('id_rol', $request->input('id_rol'))
                                ->when($id_user != "0", function($query) use ($id_user) {
                                    return $query->where('id', '!=', $id_user);
                                });
                            })
                            ->exists()) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Ya existe un usuario con el mismo tipo de documento, número de documento y rol.';
                        return response()->json($data);
                    }

                    DB::beginTransaction();
                    try {
                        $esCreacion = ($id_user == "0");
                        $nombreCompleto = $request->input('nombres') . ' ' . $request->input('apellidos');
                        
                        // Obtener el estado
                        $estado = $request->input('estado', 1);
                        
                        $userData = $request->only([
                            'nombres', 'apellidos', 'email', 'id_rol'
                        ]);
                        
                        $userData['estado'] = $estado;
                        if ($estado == 1) {
                            $userData['deleted_at'] = null;
                        } else {
                            $userData['deleted_at'] = now();
                        }
                        
                        $usuarioDatoData = [
                            'tipoDoc' => $request->input('tipoDoc'),
                            'numeroDoc' => $request->input('numeroDoc'),
                            'calle' => $request->input('calle'),
                            'numero' => $request->input('numero'),
                            'dir_otros' => $request->input('dir_otros'),
                            'cod_postal' => $request->input('cod_postal'),
                            'celular' => $request->input('celular'),
                            'fecha_nacimiento' => $request->input('fecha_nacimiento'),
                            'nacionalidad' => $request->input('nacionalidad'),
                            'distrito' => $request->input('distrito_id'),
                            'provincia' => $request->input('provincia_id'),
                            'departamento' => $request->input('departamento_id'),
                        ];

                        $valoresAnteriores = [];
                        $valoresNuevos = [];
                        $valoresAnterioresDato = [];
                        $valoresNuevosDato = [];
                        
                        if ($esCreacion) {
                            $user = new User();
                            $user->timestamps = false;
                            $user->fill($userData);
                            $user->password = bcrypt($request->input('password'));
                            $user->created_at = now();
                            $user->save();
                            
                            // Crear UsuarioDato
                            $usuarioDato = new UsuarioDato();
                            $usuarioDato->id_usuario = $user->id;
                            $usuarioDato->fill($usuarioDatoData);
                            
                            // Manejar imagen
                            if ($request->hasFile('imagen')) {
                                $file = $request->file('imagen');
                                $carpeta = public_path('perfil_usuario');
                                if (!file_exists($carpeta)) mkdir($carpeta, 0755, true);
                                $extension = $file->getClientOriginalExtension();
                                $nombreArchivo = $numeroDoc . '.' . $extension;
                                $rutaArchivo = $carpeta . '/' . $nombreArchivo;
                                if (file_exists($rutaArchivo)) unlink($rutaArchivo);
                                $file->move($carpeta, $nombreArchivo);
                                $usuarioDato->imagen = $nombreArchivo;
                            }
                            $usuarioDato->save();
                            
                            // Auditoría de creación
                            $detalleExtra = "Email: {$email} | Documento: {$tipoDoc} {$numeroDoc} | Celular: {$request->input('celular')} | Estado: " . ($estado == 1 ? 'Activo' : 'Inactivo');
                            
                            $this->registrarAuditoria(
                                'Crear',
                                'users',
                                $user->id,
                                $nombreCompleto,
                                null,
                                null,
                                $detalleExtra
                            );
                            
                        } else {
                            $user = User::find($id_user);
                            $usuarioDato = UsuarioDato::where('id_usuario', $id_user)->first();
                            
                            $valoresAnteriores = $user->toArray();
                            unset($valoresAnteriores['id'], $valoresAnteriores['created_at'], $valoresAnteriores['updated_at'], $valoresAnteriores['password'], $valoresAnteriores['remember_token']);
                            
                            if ($usuarioDato) {
                                $valoresAnterioresDato = $usuarioDato->toArray();
                                unset($valoresAnterioresDato['id'], $valoresAnterioresDato['id_usuario']);
                                $valoresAnterioresDato = $this->traducirIdsANombres($valoresAnterioresDato, [
                                    'distrito' => Distrito::class,
                                    'provincia' => Provincia::class,
                                    'departamento' => Departamento::class,
                                ]);

                                $valoresAnteriores['datos'] = $valoresAnterioresDato;
                            }
                            
                            $user->timestamps = false;
                            $user->nombres = $request->input('nombres');
                            $user->apellidos = $request->input('apellidos');
                            $user->email = $email;
                            $user->id_rol = $request->input('id_rol');
                            $user->estado = $estado;
                            
                            if ($estado == 1) {
                                $user->deleted_at = null;
                            } else {
                                $user->deleted_at = now();
                            }
                            
                            if ($request->input('password')) {
                                $user->password = bcrypt($request->input('password'));
                            }
                            $user->updated_at = now();
                            $user->save();
                            
                            // Actualizar o crear UsuarioDato
                            $usuarioDato = UsuarioDato::updateOrCreate(
                                ['id_usuario' => $user->id],
                                $usuarioDatoData
                            );
                            
                            // Manejar imagen
                            if ($request->hasFile('imagen')) {
                                $file = $request->file('imagen');
                                $carpeta = public_path('perfil_usuario');
                                if (!file_exists($carpeta)) mkdir($carpeta, 0755, true);
                                $extension = $file->getClientOriginalExtension();
                                $nombreArchivo = $numeroDoc . '.' . $extension;
                                $rutaArchivo = $carpeta . '/' . $nombreArchivo;
                                if (file_exists($rutaArchivo)) unlink($rutaArchivo);
                                $file->move($carpeta, $nombreArchivo);
                                $usuarioDato->imagen = $nombreArchivo;
                                $usuarioDato->save();
                            }
                            
                            // Guardar valores nuevos
                            $valoresNuevos = $user->toArray();
                            unset($valoresNuevos['id'], $valoresNuevos['created_at'], $valoresNuevos['updated_at'], $valoresNuevos['password'], $valoresNuevos['remember_token']);
                            
                            $valoresNuevosDato = $usuarioDato->toArray();
                            unset($valoresNuevosDato['id'], $valoresNuevosDato['id_usuario']);
                            $valoresNuevosDato = $this->traducirIdsANombres($valoresNuevosDato, [
                                'distrito' => Distrito::class,
                                'provincia' => Provincia::class,
                                'departamento' => Departamento::class,
                            ]);
                            $valoresNuevos['datos'] = $valoresNuevosDato;
                            
                            $this->registrarAuditoria(
                                'Actualizar',
                                'users',
                                $user->id,
                                $nombreCompleto,
                                $valoresAnteriores,
                                $valoresNuevos,
                                null
                            );
                        }
                        
                        DB::commit();

                        if ($esCreacion) {
                            $this->crearNotificacionParaUsuario(
                                $user,
                                '¡Bienvenido al sistema!',
                                "Hola {$user->nombres}, tu cuenta ha sido creada exitosamente. " .
                                "Ya puedes comenzar a usar el panel de administración.",
                                [
                                    'tipo_evento' => 'bienvenida',
                                    'email' => $user->email,
                                ],
                                url('/main'),
                                'Ir al panel'
                            );
                        } else {
                            $camposCambiados = $this->detectarCambiosUsuario($valoresAnteriores, $valoresNuevos, $valoresAnterioresDato ?? [], $valoresNuevosDato ?? []);
                            
                            $resumenCambios = !empty($camposCambiados)
                                ? 'Campos actualizados: ' . implode(', ', $camposCambiados) . '.'
                                : 'Se actualizaron los datos de tu cuenta.';

                            $this->crearNotificacionParaUsuario(
                                $user,
                                'Tu cuenta fue actualizada',
                                "Hola {$user->nombres}, un administrador ha actualizado tu cuenta. {$resumenCambios}",
                                [
                                    'tipo_evento' => 'actualizacion_admin',
                                    'campos_actualizados' => $camposCambiados,
                                ],
                                null,
                                null
                            );
                        }
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = $esCreacion ? 'Usuario registrado correctamente' : 'Usuario actualizado correctamente';
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error: ' . $e->getMessage();
                    }
                    break;
                case 'Obtener':
                    $id_user = $request->input('id_user');

                    $user = User::with('datos')->find($id_user);

                    if (!$user) {
                        return response()->json(['respuesta' => 'error', 'mensaje' => 'Usuario no encontrado']);
                    }

                    return response()->json([
                        'respuesta' => 'ok',
                        'usuario' => $user,
                        'usuario_datos' => $user->datos
                    ]);
                    break;
                case 'ListarRol':
                    $roles = Rol::all();
                    $currentUrl = $request->path();
                    $data->permisosVista = [
                        'crear' => MenuService::tienePermiso($currentUrl, 'crear'),
                        'editar' => MenuService::tienePermiso($currentUrl, 'editar'),
                        'eliminar' => MenuService::tienePermiso($currentUrl, 'eliminar'),
                    ];
                    $data->respuesta = 'ok';
                    $data->roles = $roles;
                    break;

                case 'CrearRol':
                    $name = $request->input('name');
                    
                    if (Rol::where('name', $name)->exists()) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'El rol ya existe.';
                    } else {
                        DB::beginTransaction();
                        try {
                            $rol = new Rol();
                            $rol->name = $name;
                            $rol->save();
                            
                            $this->registrarAuditoria(
                                'Crear',
                                'roles',
                                $rol->id,
                                $rol->name,
                                null,
                                null,
                                null
                            );
                            
                            DB::commit();
                            $data->respuesta = 'ok';
                            $data->mensaje = 'Rol creado correctamente';
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $data->respuesta = 'error';
                            $data->mensaje = 'Error al crear rol: ' . $e->getMessage();
                        }
                    }
                    break;

                case 'ObtenerRol':
                    $id = $request->input('id');
                    $rol = Rol::find($id);
                    if ($rol) {
                        $data->respuesta = 'ok';
                        $data->rol = $rol;
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Rol no encontrado';
                    }
                    break;

                case 'ActualizarRol':
                    $id = $request->input('id');
                    $name = $request->input('name');

                    $rol = Rol::find($id);
                    if (!$rol) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Rol no encontrado';
                        break;
                    }

                    if (Rol::where('name', $name)->where('id', '!=', $id)->exists()) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Ya existe otro rol con el mismo nombre';
                        break;
                    }

                    DB::beginTransaction();
                    try {
                        $valoresAnteriores = ['name' => $rol->name];
                        $rol->name = $name;
                        $rol->save();
                        $valoresNuevos = ['name' => $rol->name];
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'roles',
                            $rol->id,
                            $rol->name,
                            $valoresAnteriores,
                            $valoresNuevos,
                            null
                        );
                        
                        DB::commit();
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Rol actualizado correctamente';
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al actualizar rol: ' . $e->getMessage();
                    }
                    break;

                case 'EliminarRol':
                    $id = $request->input('id');
                    $rol = Rol::with(['usuarios.datos', 'permisos.menu'])->find($id);

                    if ($rol) {
                        DB::beginTransaction();
                        try {
                            $nombreRol = $rol->name;
                            $usuariosDetalle = [];
                            $permisosDetalle = [];

                            // Recorremos los permisos del rol
                            if ($rol->permisos->isNotEmpty()) {
                                foreach ($rol->permisos as $permiso) {
                                    $menuNombre = $permiso->menu->nombre ?? "Menú ID {$permiso->id_menus}";
                                    $acciones = implode(', ', $permiso->permisos ?? []);
                                    $permisosDetalle[] = "{$menuNombre} ({$acciones})";
                                    $permiso->delete();
                                }
                            }

                            // Recorremos los usuarios del rol
                            if ($rol->usuarios->isNotEmpty()) {
                                foreach ($rol->usuarios as $usuario) {
                                    $usuariosDetalle[] = "{$usuario->nombres} {$usuario->apellidos} ({$usuario->email})";
                                    
                                    if ($usuario->datos) {
                                        $usuario->datos->delete();
                                    }
                                    
                                    // Registrar auditoría para cada usuario afectado
                                    $this->registrarAuditoria(
                                        'Actualizar',
                                        'users',
                                        $usuario->id,
                                        $usuario->nombres . ' ' . $usuario->apellidos,
                                        null,
                                        null,
                                        "Rol eliminado: {$nombreRol} - Usuario quedó sin rol asignado"
                                    );
                                    
                                    $usuario->id_rol = null;
                                    $usuario->save();
                                }
                            }

                            $rol->delete();

                            // Detalle para auditoría del rol
                            $detalle = "Rol eliminado";
                            if (!empty($usuariosDetalle)) {
                                $detalle .= " | Usuarios afectados: " . implode('; ', $usuariosDetalle);
                            }
                            if (!empty($permisosDetalle)) {
                                $detalle .= " | Permisos eliminados: " . implode('; ', $permisosDetalle);
                            }

                            $this->registrarAuditoria(
                                'Eliminar',
                                'roles',
                                $id,
                                $nombreRol,
                                null,
                                null,
                                $detalle
                            );

                            DB::commit();
                            $data->respuesta = 'ok';
                            $data->mensaje = 'Rol eliminado correctamente';
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $data->respuesta = 'error';
                            $data->mensaje = 'Error al eliminar rol: ' . $e->getMessage();
                        }
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Rol no encontrado';
                    }
                    break;
                case 'obtenerInfo':
                    $id_user = $request->input('id_user');
                    $user = User::with([
                        'datos.departamento',
                        'datos.provincia',
                        'datos.distrito',
                        'auditorias',
                        'rol',
                        'rol.permisos',
                        'rol.permisos.menu',
                    ])->find($id_user);

                    if (!$user) {
                        return response()->json(['respuesta' => 'error', 'mensaje' => 'Usuario no encontrado']);
                    }

                    $userArray = $user->toArray();

                    if (empty($userArray['datos'])) {
                        $userArray['datos'] = [];
                    }

                    if (!empty($userArray['datos']['imagen']) && file_exists(public_path('perfil_usuario/'.$userArray['datos']['imagen']))) {
                        $userArray['datos']['imagen_url'] = asset('perfil_usuario/'.$userArray['datos']['imagen']);
                    } else {
                        $userArray['datos']['imagen_url'] = asset('img/user.png');
                    }
                    return response()->json([
                        'respuesta' => 'ok',
                        'usuario'   => $userArray
                    ]);
                    break;
                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->script = 'js/usuarios.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'administracionDelSistema.usuarios';
            $data->roles = Rol::all();
            $data->departamentos = Departamento::all();
            return view('layouts.contenido', (array) $data);
        }
    }

    public function permisosNotificaciones(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $roles = $roles = Rol::where('id', '!=', 2)
                    ->orderBy('name')->get();
                    $tipos = TipoNotificacion::where('activo', true)
                        ->orderBy('nombre')
                        ->get();

                    // Construir matriz de permisos
                    $permisosRoles = [];
                    $permisosDb = PermisoNotificacion::all();

                    foreach ($roles as $rol) {
                        $permisosRoles[$rol->id] = [];
                        foreach ($tipos as $tipo) {
                            $permiso = $permisosDb
                                ->where('rol_id', $rol->id)
                                ->where('tipo_notificacion_id', $tipo->id)
                                ->first();
                            
                            $permisosRoles[$rol->id][$tipo->id] = $permiso ? $permiso->puede_ver : false;
                        }
                    }

                    $data->respuesta = 'ok';
                    $data->roles = $roles;
                    $data->tiposNotificacion = $tipos;
                    $data->permisosRoles = $permisosRoles;
                    break;

                case 'ActualizarPermisoRol':
                    $rolId = $request->input('rol_id');
                    $tipoId = $request->input('tipo_notificacion_id');
                    $puedeVer = $request->input('puede_ver', false);

                    $rol = Rol::find($rolId);
                    $tipo = TipoNotificacion::find($tipoId);

                    if (!$rol || !$tipo) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Rol o tipo de notificación no encontrado';
                        break;
                    }

                    // No permitir quitar permisos al admin
                    if ($rol->name === 'admin' && !$puedeVer) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'No puedes quitar permisos al rol admin.';
                        break;
                    }

                    DB::beginTransaction();
                    try {
                        $permisoAnterior = PermisoNotificacion::where('rol_id', $rolId)
                            ->where('tipo_notificacion_id', $tipoId)
                            ->first();

                        $valorAnterior = $permisoAnterior ? $permisoAnterior->puede_ver : null;

                        $permiso = PermisoNotificacion::updateOrCreate(
                            [
                                'rol_id' => $rolId,
                                'tipo_notificacion_id' => $tipoId
                            ],
                            [
                                'puede_ver' => $puedeVer
                            ]
                        );

                        if ($valorAnterior !== $puedeVer) {
                            $estado = $puedeVer ? 'concedido' : 'revocado';

                            $this->registrarAuditoria(
                                'Actualizar',
                                'permisos_notificaciones',
                                $permiso->id,
                                "Permiso: {$tipo->nombre} / {$rol->name}",
                                ['puede_ver' => $valorAnterior],
                                ['puede_ver' => $puedeVer],
                                "Permiso {$estado} para el rol {$rol->name} sobre {$tipo->nombre}"
                            );
                        }

                        DB::commit();

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Permiso actualizado correctamente';

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error: ' . $e->getMessage();
                    }
                    break;

                case 'BuscarUsuarios':
                    $query = $request->input('query', '');

                    if (strlen($query) < 2) {
                        $data->respuesta = 'ok';
                        $data->usuarios = [];
                        break;
                    }

                    $usuarios = User::with('rol')
                        ->where(function($q) use ($query) {
                            $q->where('nombres', 'LIKE', "%{$query}%")
                            ->orWhere('apellidos', 'LIKE', "%{$query}%")
                            ->orWhere('email', 'LIKE', "%{$query}%");
                        })
                        ->where('id_rol', '!=', 2)
                        ->whereHas('rol', function($q) {
                            $q->where('name', '!=', 'admin');
                        })
                        ->where('estado', true)
                        ->limit(10)
                        ->get(['id', 'nombres', 'apellidos', 'email', 'id_rol']);

                    $data->respuesta = 'ok';
                    $data->usuarios = $usuarios;
                    break;

                case 'ObtenerExcepcionesUsuario':
                    $usuarioId = $request->input('usuario_id');

                    $usuario = User::with('rol')->find($usuarioId);
                    if (!$usuario) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Usuario no encontrado';
                        break;
                    }

                    if ($usuario->id_rol == 2) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Los clientes tienen su propio flujo de notificaciones.';
                        break;
                    }

                    // Matriz de permisos efectivos
                    $matriz = PermisoNotificacion::matrizPermisosUsuario($usuario);

                    $data->respuesta = 'ok';
                    $data->usuario = [
                        'id' => $usuario->id,
                        'nombres' => $usuario->nombres,
                        'apellidos' => $usuario->apellidos,
                        'email' => $usuario->email,
                        'rol_id' => $usuario->id_rol,
                        'rol_nombre' => $usuario->rol->name ?? 'Sin rol',
                    ];
                    $data->matriz = $matriz;
                    break;

                case 'GuardarExcepcionUsuario':
                    $usuarioId = $request->input('usuario_id');
                    $tipoId = $request->input('tipo_notificacion_id');
                    $tipoExcepcion = $request->input('tipo_excepcion'); 
                    $motivo = $request->input('motivo');

                    if (!$usuarioId || !$tipoId || !in_array($tipoExcepcion, ['permitir', 'denegar'])) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Datos inválidos';
                        break;
                    }

                    $usuario = User::with('rol')->find($usuarioId);
                    if (!$usuario) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Usuario no encontrado';
                        break;
                    }

                    // No permitir excepciones a admins
                    if ($usuario->rol && $usuario->rol->name === 'admin') {
                        $data->respuesta = 'error';
                        $data->mensaje = 'No se pueden crear excepciones para administradores.';
                        break;
                    }

                    if ($usuario->id_rol == 2) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Los clientes tienen su propio flujo de notificaciones.';
                        break;
                    }

                    DB::beginTransaction();
                    try {
                        $excepcionAnterior = ExcepcionNotificacionUsuario::obtener($usuarioId, $tipoId);
                        $tipoAnterior = $excepcionAnterior ? $excepcionAnterior->tipo_excepcion : null;

                        $excepcion = ExcepcionNotificacionUsuario::updateOrCreate(
                            [
                                'usuario_id' => $usuarioId,
                                'tipo_notificacion_id' => $tipoId
                            ],
                            [
                                'tipo_excepcion' => $tipoExcepcion,
                                'motivo' => $motivo,
                                'creado_por' => auth()->id(),
                            ]
                        );

                        $tipo = TipoNotificacion::find($tipoId);
                        $accion = $tipoAnterior ? 'actualizada' : 'creada';

                        $this->registrarAuditoria(
                            $tipoAnterior ? 'Actualizar' : 'Crear',
                            'excepciones_notificacion_usuario',
                            $excepcion->id,
                            "Excepción: {$usuario->nombres} {$usuario->apellidos}",
                            ['tipo_excepcion' => $tipoAnterior],
                            ['tipo_excepcion' => $tipoExcepcion],
                            "Excepción {$accion} ({$tipoExcepcion}) para {$tipo->nombre}"
                        );

                        DB::commit();

                        $data->respuesta = 'ok';
                        $data->mensaje = "Excepción {$accion} correctamente";
                        $data->excepcion = $excepcion;

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error: ' . $e->getMessage();
                    }
                    break;

                case 'EliminarExcepcionUsuario':
                    $excepcionId = $request->input('excepcion_id');

                    $excepcion = ExcepcionNotificacionUsuario::with(['usuario', 'tipoNotificacion'])
                        ->find($excepcionId);

                    if (!$excepcion) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Excepción no encontrada';
                        break;
                    }

                    DB::beginTransaction();
                    try {
                        $nombreUsuario = $excepcion->usuario->nombres . ' ' . $excepcion->usuario->apellidos;
                        $nombreTipo = $excepcion->tipoNotificacion->nombre;

                        $excepcion->delete();

                        $this->registrarAuditoria(
                            'Eliminar',
                            'excepciones_notificacion_usuario',
                            $excepcionId,
                            "Excepción: {$nombreUsuario}",
                            null,
                            null,
                            "Excepción eliminada para {$nombreTipo}. El usuario vuelve al permiso del rol."
                        );

                        DB::commit();

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Excepción eliminada';

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error: ' . $e->getMessage();
                    }
                    break;

                case 'ObtenerPermisosEfectivosUsuario':
                    $usuarioId = $request->input('usuario_id');

                    $usuario = User::with('rol')->find($usuarioId);
                    if (!$usuario) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Usuario no encontrado';
                        break;
                    }

                    if ($usuario->id_rol == 2) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Los clientes tienen su propio flujo de notificaciones.';
                        break;
                    }

                    $tipos = TipoNotificacion::where('activo', true)->orderBy('nombre')->get();
                    $resultado = [];

                    foreach ($tipos as $tipo) {
                        $permisoRol = PermisoNotificacion::puedeVerRol($usuario->id_rol, $tipo->id);
                        $excepcion = ExcepcionNotificacionUsuario::obtener($usuario->id, $tipo->id);
                        $permisoFinal = PermisoNotificacion::puedeVerUsuario($usuario, $tipo->id);

                        $resultado[] = [
                            'tipo_id' => $tipo->id,
                            'tipo_nombre' => $tipo->nombre,
                            'tipo_icono' => $tipo->icono,
                            'tipo_color' => $tipo->color,
                            'permiso_rol' => $permisoRol,
                            'excepcion' => $excepcion ? [
                                'id' => $excepcion->id,
                                'tipo_excepcion' => $excepcion->tipo_excepcion,
                                'motivo' => $excepcion->motivo,
                            ] : null,
                            'permiso_final' => $permisoFinal,
                            'origen' => $excepcion ? 'excepcion' : 'rol',
                        ];
                    }

                    $data->respuesta = 'ok';
                    $data->usuario = [
                        'id' => $usuario->id,
                        'nombres' => $usuario->nombres,
                        'apellidos' => $usuario->apellidos,
                        'email' => $usuario->email,
                        'rol_nombre' => $usuario->rol->name ?? 'Sin rol',
                    ];
                    $data->permisos = $resultado;
                    break;

                case 'ListarExcepciones':
                    $excepciones = ExcepcionNotificacionUsuario::with([
                        'usuario:id,nombres,apellidos,email',
                        'tipoNotificacion:id,nombre,icono,color'
                    ])
                    ->whereHas('usuario', function($q) {
                        $q->where('id_rol', '!=', 2);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function($excepcion) {
                        return [
                            'id' => $excepcion->id,
                            'usuario_id' => $excepcion->usuario_id,
                            'usuario_nombre' => $excepcion->usuario->nombres . ' ' . $excepcion->usuario->apellidos,
                            'usuario_email' => $excepcion->usuario->email,
                            'tipo_id' => $excepcion->tipo_notificacion_id,
                            'tipo_nombre' => $excepcion->tipoNotificacion->nombre,
                            'tipo_icono' => $excepcion->tipoNotificacion->icono,
                            'tipo_color' => $excepcion->tipoNotificacion->color,
                            'tipo_excepcion' => $excepcion->tipo_excepcion,
                            'motivo' => $excepcion->motivo,
                            'created_at' => $excepcion->created_at->format('d/m/Y H:i'),
                        ];
                    });

                    $data->respuesta = 'ok';
                    $data->excepciones = $excepciones;
                    break;

                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->script = 'js/permisosNotificaciones.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'administracionDelSistema.permisosNotificaciones';
            
            $data->roles = Rol::where('id', '!=', 2)
            ->orderBy('name')->get();
            $data->tiposNotificacion = TipoNotificacion::where('activo', true)
                ->orderBy('nombre')
                ->get();
            
            $permisosRoles = [];
            $permisosDb = PermisoNotificacion::all();
            
            foreach ($data->roles as $rol) {
                $permisosRoles[$rol->id] = [];
                foreach ($data->tiposNotificacion as $tipo) {
                    $permiso = $permisosDb
                        ->where('rol_id', $rol->id)
                        ->where('tipo_notificacion_id', $tipo->id)
                        ->first();
                    
                    $permisosRoles[$rol->id][$tipo->id] = $permiso ? $permiso->puede_ver : false;
                }
            }
            
            $data->permisosRoles = $permisosRoles;
            $data->excepciones = ExcepcionNotificacionUsuario::with([
                'usuario:id,nombres,apellidos,email',
                'tipoNotificacion:id,nombre,icono,color'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

            return view('layouts.contenido', (array) $data);
        }
    }

    public function getProvincias($departamento_id)
    {
        $provincias = Provincia::where('departamento_id', $departamento_id)->get();
        return response()->json($provincias);
    }

    public function getDistritos($provincia_id)
    {
        $distritos = Distrito::where('provincia_id', $provincia_id)->get();
        return response()->json($distritos);
    }

    /**
     * Detectar qué campos cambiaron entre el usuario anterior y el nuevo
     */
    private function detectarCambiosUsuario(array $antes, array $despues, array $antesDato = [], array $despuesDato = []): array
    {
        $cambios = [];
        
        $camposUser = ['nombres', 'apellidos', 'email', 'id_rol', 'estado'];
        foreach ($camposUser as $campo) {
            if (isset($antes[$campo]) && isset($despues[$campo]) && $antes[$campo] != $despues[$campo]) {
                $cambios[] = $campo;
            }
        }
        
        $camposDato = ['tipoDoc', 'numeroDoc', 'celular', 'fecha_nacimiento', 'nacionalidad', 'distrito', 'provincia', 'departamento'];
        foreach ($camposDato as $campo) {
            if (isset($antesDato[$campo]) && isset($despuesDato[$campo]) && $antesDato[$campo] != $despuesDato[$campo]) {
                $cambios[] = $campo;
            }
        }
        
        return $cambios;
    }

    /**
     * Crear una notificación personal para un usuario
     * 
     * @param User $user        Usuario destinatario
     * @param string $titulo    Título de la notificación
     * @param string $mensaje   Mensaje completo
     * @param array $dataExtra  Datos adicionales (opcional)
     * @param string $url       URL del botón (opcional)
     * @param string $botonTexto Texto del botón (opcional)
     */
    private function crearNotificacionParaUsuario(
        User $user,
        string $titulo,
        string $mensaje,
        array $dataExtra = [],
        ?string $url = null,
        ?string $botonTexto = null
    ) {
        // Obtener el tipo "Usuario"
        $tipo = TipoNotificacion::where('slug', 'usuario')->first();
        
        if (!$tipo) {
            \Log::warning('No existe el tipo de notificación "usuario"');
            return null;
        }

        $notificacion = \App\Models\Notificacion::create([
            'tipo_notificacion_id' => $tipo->id,
            'creado_por'           => null, 
            'titulo'               => $titulo,
            'mensaje'              => $mensaje,
            'mensaje_corto'        => mb_substr($mensaje, 0, 120),
            'data_extra'           => array_merge($dataExtra, [
                'fecha' => now()->toDateTimeString(),
            ]),
            'url'                  => $url,
            'boton_texto'          => $botonTexto,
            'prioridad'            => 'baja',
            'fecha_inicio'         => now(),
            'fecha_fin'            => null,
            'usuario_id'           => $user->id,
            'rol_id'               => null,      
            'visible'              => true,
            'eliminada'            => false,
        ]);

        // Forzar asignación en la tabla pivote (por si el evento created falla)
        $notificacion->usuarios()->syncWithoutDetaching([
            $user->id => [
                'leida' => false,
                'leida_en' => null,
                'fecha_visualizacion' => null,
            ]
        ]);

        return $notificacion;
    }

    /**
     * Traduce el nombre del permiso a un formato legible
     */
    private function traducirNombrePermiso($campo)
    {
        $traducciones = [
            'ver' => 'Ver',
            'crear' => 'Crear',
            'editar' => 'Editar',
            'eliminar' => 'Eliminar',
            'roles' => 'Roles',
            'configurar' => 'Configurar',
            'subcategoria' => 'Subcategoría',
        ];
        
        return $traducciones[$campo] ?? ucfirst($campo);
    }
}