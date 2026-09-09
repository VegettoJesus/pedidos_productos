<?php
// app/Http/Controllers/PerfilController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsuarioDato;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Distrito;
use App\Models\ProductoValoracion;
use App\Models\ConfiguracionSistema;
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
            $user = Auth::user();
            if ($user->rol && $user->rol->name === 'client') {
                $authUser = [
                    'nombres'   => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email'     => $user->email,
                    'foto'      => asset('img/user.png'),
                ];
            }
        }
        return [
            'titulo_site' => $config ? $config->titulo_site : null,
            'descripcion_corta' => $config ? $config->descripcion_corta : null,
            'authUser' => $authUser,
        ];
    }

    public function index()
    {
        $user = Auth::user()->load('datos', 'rol');
        
        if ($user->id_rol == 2) {
            return redirect()->route('tienda.home')->with('warning', 'No tienes permisos para acceder a esta página.');
        }
        
        $base = $this->getBaseConfig();
        return view('layouts.contenido', array_merge($base, [
            'contenido' => 'perfil.configuracion',
            'css' => 'css/administracion.css',
            'usuario' => $user,
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'script' => 'js/perfil.js',
        ]));
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
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
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
}