<?php
// app/Http/Controllers/PerfilController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsuarioDato;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Distrito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PerfilController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('datos', 'rol');
        
        $data = new \stdClass();
        $data->script = 'js/perfil.js';
        $data->css = 'css/administracion.css';
        $data->contenido = 'perfil.configuracion';
        $data->usuario = $user;
        $data->departamentos = Departamento::orderBy('nombre')->get();
        
        return view('layouts.contenido', (array) $data);
    }

    public function obtenerDatos()
    {
        $user = Auth::user()->load('datos', 'rol');
        
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

        $user = Auth::user();
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
}