<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Provincia;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Ubicaciones extends Controller
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

    public function departamentos(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $departamentos = Departamento::all();
                    $currentUrl = $request->path();
                    $data->respuesta = 'ok';
                    $data->permisosVista = [
                        'editar'   => MenuService::tienePermiso($currentUrl, 'editar'),
                        'eliminar' => MenuService::tienePermiso($currentUrl, 'eliminar'),
                    ];
                    $data->data = $departamentos;
                    break;

                case 'Crear':
                    $departamento = Departamento::create([
                        'nombre' => $request->nombre
                    ]);
                    $this->registrarAuditoria('crear', 'departamentos', $departamento->id, "Departamento creado");
                    return response()->json([
                        'respuesta' => 'success',
                        'mensaje'   => 'Departamento creado correctamente'
                    ]);

                case 'Editar':
                    $departamento = Departamento::find($request->id);
                    $departamento->update([
                        'nombre' => $request->nombre
                    ]);
                    $this->registrarAuditoria('editar', 'departamentos', $departamento->id, "Departamento editado");
                    return response()->json([
                        'respuesta' => 'success',
                        'mensaje'   => 'Departamento actualizado correctamente'
                    ]);

                case 'Eliminar':
                    $departamento = Departamento::find($request->id);
                    $departamento->delete();
                    $this->registrarAuditoria('eliminar', 'departamentos', $request->id, "Departamento eliminado");
                    return response()->json([
                        'respuesta' => 'success',
                        'mensaje'   => 'Departamento eliminado correctamente'
                    ]);

                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->script = 'js/departamentos.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'ubicaciones.departamentos';
            return view('layouts.contenido', (array) $data);
        }
    }

    public function provincias(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $provincias = Provincia::with('departamento')->get();
                    $currentUrl = $request->path();
                    $data->permisosVista = [
                        'editar'   => MenuService::tienePermiso($currentUrl, 'editar'),
                        'eliminar' => MenuService::tienePermiso($currentUrl, 'eliminar'),
                    ];
                    $data->respuesta = 'ok';
                    $data->data = $provincias;
                    break;

                case 'Crear':
                    $provincia = Provincia::create([
                        'nombre' => $request->nombre,
                        'departamento_id' => $request->departamento_id,
                    ]);

                    $this->registrarAuditoria('crear', 'provincias', $provincia->id, "Creación de provincia {$provincia->nombre}");

                    $data->respuesta = 'success';
                    $data->mensaje = 'Provincia registrada correctamente';
                    break;

                case 'Editar':
                    $provincia = Provincia::findOrFail($request->id);
                    $provincia->update([
                        'nombre' => $request->nombre,
                        'departamento_id' => $request->departamento_id,
                    ]);

                    $this->registrarAuditoria('editar', 'provincias', $provincia->id, "Edición de provincia {$provincia->nombre}");

                    $data->respuesta = 'success';
                    $data->mensaje = 'Provincia actualizada correctamente';
                    break;

                case 'Eliminar':
                    $provincia = Provincia::findOrFail($request->id);
                    $provincia->delete();

                    $this->registrarAuditoria('eliminar', 'provincias', $request->id, "Eliminación de provincia {$provincia->nombre}");

                    $data->respuesta = 'success';
                    $data->mensaje = 'Provincia eliminada correctamente';
                    break;

                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->departamentos = Departamento::all();
            $data->script = 'js/provincias.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'ubicaciones.provincias';
            return view('layouts.contenido', (array) $data);
        }
    }

    public function distritos(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $distritos = Distrito::with('provincia.departamento')->get();

                    $data->permisosVista = [
                        'editar'   => MenuService::tienePermiso($request->path(), 'editar'),
                        'eliminar' => MenuService::tienePermiso($request->path(), 'eliminar')
                    ];
                    $data->respuesta = 'ok';
                    $data->data = $distritos;
                    break;

                case 'Crear':
                    try {
                        $distrito = Distrito::create([
                            'nombre'       => $request->input('nombre'),
                            'costo_envio'  => $request->input('costo_envio'),
                            'provincia_id' => $request->input('provincia_id')
                        ]);

                        $this->registrarAuditoria("crear", "distritos", $distrito->id, "Creación del distrito {$distrito->nombre}");

                        $data->respuesta = 'success';
                        $data->mensaje   = 'Distrito registrado correctamente.';
                    } catch (\Exception $e) {
                        $data->respuesta = 'error';
                        $data->mensaje   = 'Error al crear el distrito.';
                    }
                    break;

                case 'Editar':
                    try {
                        $distrito = Distrito::findOrFail($request->input('id'));
                        $distrito->update([
                            'nombre'       => $request->input('nombre'),
                            'costo_envio'  => $request->input('costo_envio'),
                            'provincia_id' => $request->input('provincia_id')
                        ]);

                        $this->registrarAuditoria("editar", "distritos", $distrito->id, "Edición del distrito {$distrito->nombre}");

                        $data->respuesta = 'success';
                        $data->mensaje   = 'Distrito actualizado correctamente.';
                    } catch (\Exception $e) {
                        $data->respuesta = 'error';
                        $data->mensaje   = 'Error al editar el distrito.';
                    }
                    break;

                case 'Eliminar':
                    try {
                        $distrito = Distrito::findOrFail($request->input('id'));
                        $nombre   = $distrito->nombre;
                        $distrito->delete();

                        $this->registrarAuditoria("eliminar", "distritos", $request->input('id'), "Eliminación del distrito {$nombre}");

                        $data->respuesta = 'success';
                        $data->mensaje   = 'Distrito eliminado correctamente.';
                    } catch (\Exception $e) {
                        $data->respuesta = 'error';
                        $data->mensaje   = 'Error al eliminar el distrito.';
                    }
                    break;

                default:
                    $data->respuesta = 'error';
                    $data->mensaje   = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->departamentos = Departamento::with('provincias')->get();
            $data->script = 'js/distritos.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'ubicaciones.distritos';
            return view('layouts.contenido', (array) $data);
        }
    }

}
