<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsuarioDato;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Distrito;
use App\Models\ProductoValoracion;
use App\Models\ConfiguracionSistema;
use App\Models\TipoNotificacion;
use App\Models\Notificacion;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PerfilController extends Controller
{
    private function getBaseConfig()
    {
        $config = ConfiguracionSistema::first();
        $authUser = null;

        if (Auth::check()) {
            $user = Auth::user()->loadMissing('datos');
            if ($user->rol && $user->rol->id === 2) {
                $fotoUrl = asset('img/user.png');
                if ($user->datos && $user->datos->imagen
                    && file_exists(public_path('perfil_usuario/' . $user->datos->imagen))) {
                    $fotoUrl = asset('perfil_usuario/' . $user->datos->imagen);
                }

                $authUser = [
                    'nombres'   => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email'     => $user->email,
                    'foto'      => $fotoUrl,
                ];
            }
        }

        return [
            'titulo_site'       => $config ? $config->titulo_site : null,
            'descripcion_corta' => $config ? $config->descripcion_corta : null,
            'authUser'          => $authUser,
        ];
    }

    public function index()
    {
        $user = Auth::user()->load('datos', 'rol');
        $esCliente = $user->id_rol == 2;

        if ($esCliente) {
            session(['contexto_perfil' => 'cliente']);

            return view('layouts.contenido2', array_merge($this->getBaseConfig(), [
                'contenido2'      => 'perfil.configuracion-cliente',
                'css'             => 'css/tienda.css',
                'usuario'         => $user,
                'departamentos'   => Departamento::orderBy('nombre')->get(),
                'script'          => 'js/perfil-cliente.js',
                'esPaginaCliente' => true,   
            ]));
        }

        session(['contexto_perfil' => 'panel']);

        return view('layouts.contenido', array_merge($this->getBaseConfig(), [
            'contenido'       => 'perfil.configuracion',
            'css'             => 'css/administracion.css',
            'usuario'         => $user,
            'departamentos'   => Departamento::orderBy('nombre')->get(),
            'script'          => 'js/perfil.js',
            'esPaginaCliente' => false,
        ]));
    }

    // =============================================
    // MÉTODOS EXCLUSIVOS PARA CLIENTES (rol 2)
    // =============================================

    public function obtenerDatosCliente()
    {
        $user = Auth::user();
        if (!$user || $user->id_rol != 2) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $user->load('datos');

        $imagenUrl = asset('img/user.png');
        if ($user->datos && $user->datos->imagen &&
            file_exists(public_path('perfil_usuario/' . $user->datos->imagen))) {
            $imagenUrl = asset('perfil_usuario/' . $user->datos->imagen);
        }

        return response()->json([
            'success' => true,
            'usuario' => [
                'id'         => $user->id,
                'nombres'    => $user->nombres,
                'apellidos'  => $user->apellidos,
                'email'      => $user->email,
                'id_rol'     => $user->id_rol,
                'imagen_url' => $imagenUrl,
                'datos'      => $user->datos ? [
                    'tipoDoc'          => $user->datos->tipoDoc,
                    'numeroDoc'        => $user->datos->numeroDoc,
                    'celular'          => $user->datos->celular,
                    'fecha_nacimiento' => $user->datos->fecha_nacimiento,
                    'nacionalidad'     => $user->datos->nacionalidad,
                    'departamento'     => $user->datos->departamento,
                    'provincia'        => $user->datos->provincia,
                    'distrito'         => $user->datos->distrito,
                    'cod_postal'       => $user->datos->cod_postal,
                    'calle'            => $user->datos->calle,
                    'numero'           => $user->datos->numero,
                    'dir_otros'        => $user->datos->dir_otros,
                    'imagen_url'       => $imagenUrl,
                ] : null,
            ],
        ]);
    }

    public function actualizarCliente(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->id_rol != 2) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombres'          => 'required|string|max:150',
            'apellidos'        => 'required|string|max:150',
            'email'            => 'required|email|unique:users,email,' . $user->id,
            'password'         => 'nullable|min:6|confirmed',
            'tipoDoc'          => 'nullable|string|max:50',
            'numeroDoc'        => 'nullable|string|max:50',
            'celular'          => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'nacionalidad'     => 'nullable|string|max:100',
            'departamento_id'  => 'nullable|integer',
            'provincia_id'     => 'nullable|integer',
            'distrito_id'      => 'nullable|integer',
            'calle'            => 'nullable|string|max:255',
            'numero'           => 'nullable|string|max:20',
            'dir_otros'        => 'nullable|string|max:255',
            'cod_postal'       => 'nullable|string|max:20',
            'imagen'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $cambios = [];
        if ($user->nombres !== $request->nombres) $cambios[] = 'nombres';
        if ($user->apellidos !== $request->apellidos) $cambios[] = 'apellidos';
        if ($user->email !== $request->email) $cambios[] = 'email';
        if ($request->filled('password')) $cambios[] = 'contraseña';

        $user->nombres   = $request->nombres;
        $user->apellidos = $request->apellidos;
        $user->email     = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $datos = UsuarioDato::firstOrNew(['id_usuario' => $user->id]);
        $datos->tipoDoc          = $request->tipoDoc;
        $datos->numeroDoc        = $request->numeroDoc;
        $datos->celular          = $request->celular;
        $datos->fecha_nacimiento = $request->fecha_nacimiento;
        $datos->nacionalidad     = $request->nacionalidad;
        $datos->departamento     = $request->departamento_id;
        $datos->provincia        = $request->provincia_id;
        $datos->distrito         = $request->distrito_id;
        $datos->cod_postal       = $request->cod_postal;
        $datos->calle            = $request->calle;
        $datos->numero           = $request->numero;
        $datos->dir_otros        = $request->dir_otros;
        $datos->save();

        if ($request->hasFile('imagen')) {
            $carpeta = public_path('perfil_usuario');
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0755, true);
            }
            if ($datos->imagen && file_exists($carpeta . '/' . $datos->imagen)) {
                @unlink($carpeta . '/' . $datos->imagen);
            }
            $ext = $request->file('imagen')->getClientOriginalExtension();
            $nombreArchivo = time() . '_' . $user->id . '.' . $ext;
            $request->file('imagen')->move($carpeta, $nombreArchivo);
            $datos->imagen = $nombreArchivo;
            $datos->save();
            $cambios[] = 'foto de perfil';
        }

        // Notificación opcional
        if (class_exists(\App\Models\Notificacion::class)) {
            $this->crearNotificacionActualizacionPerfil($user, $cambios);
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'imagen_url' => ($datos->imagen && file_exists(public_path('perfil_usuario/' . $datos->imagen)))
                ? asset('perfil_usuario/' . $datos->imagen)
                : asset('img/user.png'),
        ]);
    }

    public function actualizarImagenCliente(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->id_rol != 2) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $carpeta = public_path('perfil_usuario');
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $datos = UsuarioDato::firstOrNew(['id_usuario' => $user->id]);

        // borrar la anterior si existe
        if ($datos->imagen && file_exists($carpeta . '/' . $datos->imagen)) {
            @unlink($carpeta . '/' . $datos->imagen);
        }

        $ext = $request->file('imagen')->getClientOriginalExtension();
        $nombreArchivo = time() . '_' . $user->id . '.' . $ext;
        $request->file('imagen')->move($carpeta, $nombreArchivo);

        $datos->imagen = $nombreArchivo;
        $datos->save();

        $url = asset('perfil_usuario/' . $nombreArchivo);

        return response()->json([
            'success'    => true,
            'message'    => 'Foto actualizada correctamente.',
            'imagen_url' => $url . '?t=' . time(),
            'imagen'     => $nombreArchivo,
        ]);
    }

    public function obtenerDatos()
    {
        $user = Auth::user()->load('datos', 'rol');
        
        if ($user->id_rol == 2) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        
        if ($user->datos && $user->datos->imagen && 
            file_exists(public_path('perfil_usuario/'.$user->datos->imagen))) {
            $user->datos->imagen_url = asset('perfil_usuario/'.$user->datos->imagen);
        } else {
            $user->datos->imagen_url = asset('img/user.png');
        }
        
        return response()->json([
            'success' => true,
            'usuario' => $user
        ]);
    }

    public function actualizar(Request $request)
    {
        $user = Auth::user();
        
        if ($user->id_rol == 2) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'nombres' => 'required|string|max:150',
            'apellidos' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'password' => 'nullable|min:6|regex:/^(?=.*[A-Z])(?=.*[a-zA-Z])(?=.*\d)/',
            'tipoDoc' => 'required|string',
            'numeroDoc' => 'required|string',
            'celular' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'nacionalidad' => 'nullable|string',
            'departamento_id' => 'required',
            'provincia_id' => 'required',
            'distrito_id' => 'required',
            'direccion' => 'required|string',
            'calle' => 'nullable|string',
            'numero' => 'nullable|string',
            'dir_otros' => 'nullable|string',
            'cod_postal' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'password.regex' => 'La contraseña debe tener al menos una mayúscula, letras y números',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $cambios = [];
        if ($user->nombres !== $request->nombres) $cambios[] = 'nombres';
        if ($user->apellidos !== $request->apellidos) $cambios[] = 'apellidos';
        if ($user->email !== $request->email) $cambios[] = 'email';
        if ($request->filled('password')) $cambios[] = 'contraseña';

        $user->nombres = $request->nombres;
        $user->apellidos = $request->apellidos;
        $user->email = $request->email;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();
        
        $usuarioDato = UsuarioDato::updateOrCreate(
            ['id_usuario' => $user->id],
            [
                'tipoDoc' => $request->tipoDoc,
                'numeroDoc' => $request->numeroDoc,
                'calle' => $request->direccion,
                'numero' => $request->num_calle,
                'dir_otros' => $request->dir_otros,
                'celular' => $request->celular,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'nacionalidad' => $request->nacionalidad,
                'departamento' => $request->departamento_id,
                'provincia' => $request->provincia_id,
                'distrito' => $request->distrito_id,
                'cod_postal' => $request->cod_postal,
            ]
        );

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $carpeta = public_path('perfil_usuario');
            
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0755, true);
            }
            
            if ($usuarioDato->imagen && file_exists($carpeta . '/' . $usuarioDato->imagen)) {
                unlink($carpeta . '/' . $usuarioDato->imagen);
            }
            
            $extension = $file->getClientOriginalExtension();
            $nombreArchivo = time() . '_' . $request->numeroDoc . '.' . $extension;
            $file->move($carpeta, $nombreArchivo);
            $usuarioDato->imagen = $nombreArchivo;
            $usuarioDato->save();

            $cambios[] = 'foto de perfil';
        }

        $this->crearNotificacionActualizacionPerfil($user, $cambios);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ]);
    }

    /**
     * Crear notificación personal cuando el usuario actualiza su perfil
     */
    private function crearNotificacionActualizacionPerfil($user, $cambios = [])
    {
        $tipo = TipoNotificacion::where('slug', 'usuario')->first();
        
        if (!$tipo) {
            return; 
        }

        $resumenCambios = !empty($cambios)
            ? 'Campos actualizados: ' . implode(', ', $cambios) . '.'
            : 'Se actualizaron los datos de tu perfil.';

        Notificacion::create([
            'tipo_notificacion_id' => $tipo->id,
            'creado_por'           => null, 
            'titulo'               => 'Perfil actualizado',
            'mensaje'              => "Hola {$user->nombres}, tu perfil se actualizó correctamente. {$resumenCambios}",
            'mensaje_corto'        => 'Tu perfil fue actualizado correctamente.',
            'data_extra'           => [
                'campos_actualizados' => $cambios,
                'fecha'               => now()->toDateTimeString(),
            ],
            'url'                  => route('perfil.configuracion'),
            'boton_texto'          => 'Ver mi perfil',
            'prioridad'            => 'baja',
            'fecha_inicio'         => now(),
            'fecha_fin'            => null,
            'usuario_id'           => $user->id,
            'rol_id'               => null,      
            'visible'              => true,
            'eliminada'            => false,
        ]);
    }

    public function misValoraciones(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Debes iniciar sesión para ver tus valoraciones.',
                    'show_modal' => true
                ], 401);
            }
            
            return view('layouts.contenido2', [
                'contenido2' => 'tienda.mis-valoraciones',
                'valoraciones' => collect([]),
                'show_auth_modal' => true,
                'auth_modal_message' => 'Debes iniciar sesión para ver tus valoraciones.'
            ]);
        }
        
        if ($user->id_rol != 2) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No tienes permisos de cliente.',
                    'show_modal' => true
                ], 403);
            }
            
            return view('layouts.contenido2', [
                'contenido2' => 'tienda.mis-valoraciones',
                'valoraciones' => collect([]),
                'show_auth_modal' => true,
                'auth_modal_message' => 'No tienes permisos de cliente. Por favor, inicia sesión con una cuenta de cliente.'
            ]);
        }
        
        $valoraciones = ProductoValoracion::with(['producto' => function($q) {
                $q->with(['imagenes', 'valoraciones']);
            }])
            ->where('user_id', $user->id)
            ->where('aprobado', true)
            ->orderBy('updated_at', 'desc')
            ->paginate(12);
        
        foreach ($valoraciones as $valoracion) {
            $producto = $valoracion->producto;
            if ($producto) {
                $valoracion->producto_precio = $producto->precio_formateado;
                $valoracion->producto_imagen = $producto->imagen_miniatura 
                    ? asset($producto->imagen_miniatura) 
                    : asset('img/default-product.png');
                $valoracion->producto_url = route('producto.detalle', $producto->id);
            }
        }
        
        if ($request->ajax() || $request->route()->getName() === 'perfil.mis-valoraciones.data') {
            return response()->json([
                'success' => true,
                'valoraciones' => $valoraciones->items(),
                'pagination' => [
                    'current_page' => $valoraciones->currentPage(),
                    'last_page' => $valoraciones->lastPage(),
                    'per_page' => $valoraciones->perPage(),
                    'total' => $valoraciones->total(),
                ]
            ]);
        }
        
        return view('layouts.contenido2', [
            'contenido2' => 'tienda.mis-valoraciones',
            'valoraciones' => $valoraciones,
            'show_auth_modal' => false
        ]);
    }

    public function misCompras()
    {
        if (!Auth::check() || Auth::user()->id_rol !== 2) {
            return redirect()->route('tienda.home')
                ->with('info', 'Inicia sesión para ver tus compras.');
        }

        $user = Auth::user();
        $pedidos = Pedido::with(['items', 'metodoPago'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $base = [
            'titulo_site' => ConfiguracionSistema::first()?->titulo_site,
            'authUser' => [
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'foto' => asset('img/user.png'),
            ],
        ];

        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'perfil.mis-compras',
            'pedidos' => $pedidos
        ]));
    }
}