<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Departamento;
use App\Models\Distrito;
use Illuminate\Http\Request;
use App\Models\Rol;
use App\Models\Menu;
use App\Models\Permiso;
use App\Models\Provincia;
use App\Models\User;
use App\Models\UsuarioDato;
use App\Services\MenuService;
use Illuminate\Support\Facades\Auth;

class AdministracionDelSistema extends Controller
{
    private function registrarAuditoria($accion, $tabla, $registroId = null, $descripcion = null)
    {
        Auditoria::create([
            'user_id'       => Auth::id(),
            'accion'        => $accion,
            'tabla_afectada'=> $tabla,
            'registro_id'   => $registroId,
            'descripcion'   => $descripcion,
            'ip'            => request()->ip(),
            'navegador'     => request()->header('User-Agent')
        ]);
    }

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

                    // Si quieres, los empaquetas en un array
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

                    if ($idRol) { $menus = $menus->filter(function ($menu) use ($idRol) { 
                        return Permiso::where('id_menus', $menu->id) ->where('id_rol', $idRol) ->exists(); 
                    })->values(); }

                    $data->respuesta = 'ok';
                    $data->menus = $menus;
                    break;

                case 'Permisos':
                    $idMenu = $request->input('id_menu');
                    $roles = Rol::all();

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
                    $campo = $request->input('campo'); // Puede ser ver, crear, exportar...
                    $valor = $request->input('valor') ? true : false;

                    $permiso = Permiso::firstOrCreate([
                        'id_menus' => $idMenu,
                        'id_rol'   => $idRol
                    ]);

                    $permisosArray = $permiso->permisos ?? []; 
                    $permisosArray[$campo] = $valor;           
                    $permiso->permisos = $permisosArray;
                    $permiso->save();

                    $this->registrarAuditoria(
                        'Actualizar',
                        'permisos',
                        $permiso->id,
                        "Se actualizó el permiso '{$campo}' en menú {$idMenu} para el rol {$idRol}"
                    );

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

                    $this->registrarAuditoria(
                        'Crear',
                        'menus',
                        $menu->id,
                        "Se creó el menú '{$menu->nombre}'"
                    );

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

                    $this->registrarAuditoria(
                        'Eliminar',
                        'menus',
                        $menu->id,
                        "Se eliminó el menú '{$menu->nombre}'"
                    );

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

                    $this->registrarAuditoria(
                        'Actualizar',
                        'menus',
                        $menu->id,
                        "Se actualizó el menú '{$menu->nombre}'"
                    );

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Menú actualizado correctamente';
                    $data->menu = $menu;
                    break;
                case 'ActualizarURL':
                    $idMenu = $request->input('id_menu');
                    $url    = $request->input('url');

                    $menu = Menu::find($idMenu);

                    if (!$menu) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Menú no encontrado';
                        break;
                    }

                    $menu->url = $url;
                    $menu->save();

                    $this->registrarAuditoria(
                        'Actualizar',
                        'menus',
                        $menu->id,
                        "Se actualizó la URL del menú '{$menu->nombre}' a '{$url}'"
                    );

                    $data->respuesta = 'ok';
                    $data->mensaje = 'URL actualizada correctamente';
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
                    $user = User::find($id);

                    if ($user) {
                        $user->estado = 0;
                        $user->deleted_at = now();
                        $user->save();
                        $this->registrarAuditoria(
                            'Eliminar',
                            'users',
                            $user->id,
                            "Se desactivó al usuario  {$user->nombres} {$user->apellidos}"
                        );
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Usuario desactivado correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Usuario no encontrado';
                    }
                    break;

                case 'EliminarMultiple':
                    $ids = $request->input('ids', []);
                    if (!empty($ids)) {
                        $usuarios = User::whereIn('id', $ids)->get();

                        foreach ($usuarios as $user) {
                            $user->estado = 0;
                            $user->deleted_at = now();
                            $user->save();

                            $this->registrarAuditoria(
                                'Eliminar',
                                'users',
                                $user->id,
                                "Se desactivó al usuario {$user->nombres} {$user->apellidos}"
                            );
                        }

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Usuarios desactivados correctamente';
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
                        $data->mensaje = 'El correo electrónico ingresado ya está registrado. Por favor use otro correo.';
                        return response()->json($data);
                    }

                    // Validar si el DNI / documento ya existe en usuarios_datos
                    if (UsuarioDato::where('tipoDoc', $tipoDoc)->where('numeroDoc', $numeroDoc)
                        ->when($id_user != "0", function($query) use ($id_user){
                            return $query->where('id_usuario', '!=', $id_user);
                        })->exists()) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'El número de documento ingresado ya tiene una cuenta asociada.';
                        return response()->json($data);
                    }

                    if ($id_user == "0") {
                        $user = new User();
                        $user->timestamps = false; 
                        $user->nombres = $request->input('nombres');
                        $user->apellidos = $request->input('apellidos');
                        $user->email = $email;
                        $user->password = bcrypt($request->input('password'));
                        $user->id_rol = $request->input('id_rol');
                        $user->estado = $request->input('estado', 1);
                        $user->created_at = now();
                        $user->save();
                    } else {
                        $user = User::find($id_user);
                        $user->timestamps = false; 
                        $user->nombres = $request->input('nombres');
                        $user->apellidos = $request->input('apellidos');
                        $user->email = $email;
                        if ($request->input('password')) {
                            $user->password = bcrypt($request->input('password'));
                        }
                        $user->id_rol = $request->input('id_rol');
                        $user->estado = $request->input('estado', 1);
                        $user->updated_at = now(); 
                        $user->save();
                    }

                    $usuarioDato = UsuarioDato::updateOrCreate(
                        ['id_usuario' => $user->id],
                        [
                            'tipoDoc' => $tipoDoc,
                            'numeroDoc' => $numeroDoc,
                            'direccion' => $request->input('direccion_completo'),
                            'celular' => $request->input('celular'),
                            'fecha_nacimiento' => $request->input('fecha_nacimiento'),
                            'nacionalidad' => $request->input('nacionalidad'),
                            'distrito' => $request->input('distrito_id'),
                            'provincia' => $request->input('provincia_id'),
                            'departamento' => $request->input('departamento_id'),
                            'cod_postal' => $request->input('cod_postal')
                        ]
                    );

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
                    $this->registrarAuditoria(
                        $id_user == "0" ? 'Crear' : 'Actualizar',
                        'users',
                        $user->id,
                        $id_user == "0"
                            ? "Se creó el usuario {$user->nombres} {$user->apellidos}"
                            : "Se actualizó el usuario {$user->nombres} {$user->apellidos}"
                    );
                    $data->respuesta = 'ok';
                    $data->mensaje = $id_user == "0" ? 'Usuario registrado correctamente' : 'Usuario actualizado correctamente';
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
                        $rol = new Rol();
                        $rol->name = $name;
                        $rol->save();
                        $this->registrarAuditoria(
                            'Crear',
                            'roles',
                            $rol->id,
                            "Se creó el rol {$rol->name}"
                        );
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Rol creado correctamente';
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

                    $rol->name = $name;
                    $rol->save();
                    $this->registrarAuditoria(
                        'Actualizar',
                        'roles',
                        $rol->id,
                        "Se actualizó el rol {$rol->name}"
                    );
                    $data->respuesta = 'ok';
                    $data->mensaje = 'Rol actualizado correctamente';
                    break;

                case 'EliminarRol':
                    $id = $request->input('id');
                    $rol = Rol::with(['usuarios.datos', 'permisos.menu'])->find($id);

                    if ($rol) {
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
                                // Recuperar permisos asociados al mismo rol
                                $accionesUsuario = [];
                                foreach ($rol->permisos as $permiso) {
                                    $accionesUsuario = array_merge($accionesUsuario, $permiso->permisos ?? []);
                                }
                                $accionesUsuario = implode(', ', array_unique($accionesUsuario));

                                $usuariosDetalle[] = "{$usuario->nombres} {$usuario->apellidos}" .
                                    (!empty($accionesUsuario) ? " con permisos: {$accionesUsuario}" : "");

                                if ($usuario->datos) {
                                    $usuario->datos->delete();
                                }
                                $usuario->delete();
                            }
                        }

                        $rol->delete();

                        // Mensaje para auditoría
                        $detalle = "Se eliminó el rol {$rol->name}";
                        if (!empty($usuariosDetalle)) {
                            $detalle .= " junto con los usuarios: " . implode('; ', $usuariosDetalle);
                        }
                        if (!empty($permisosDetalle)) {
                            $detalle .= " y los permisos: " . implode('; ', $permisosDetalle);
                        }

                        $this->registrarAuditoria(
                            'Eliminar',
                            'roles',
                            $rol->id,
                            $detalle
                        );

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Rol, usuarios y permisos asociados eliminados correctamente';
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

                    $data->respuesta = $user ? 'ok' : 'error';
                    if ($user && $user->datos && $user->datos->imagen && 
                        file_exists(public_path('perfil_usuario/'.$user->datos->imagen))) {
                        $user->datos->imagen_url = asset('perfil_usuario/'.$user->datos->imagen);
                    } else {
                        $user->datos->imagen_url = asset('img/user.png');
                    }
                    $data->usuario = $user;
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
}