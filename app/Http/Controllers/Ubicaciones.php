<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Provincia;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function sitio(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->procesarSolicitud($request);
        } else {
            $data = new \stdClass();
            $data->departamentos = Departamento::where('activo', true)->get();
            $data->script = 'js/sitio.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'ubicaciones.sitio';
            return view('layouts.contenido', (array) $data);
        }
    }

    private function procesarSolicitud(Request $request)
    {
        $opcion = $request->input('opcion');
        $tipo = $request->input('tipo'); // 'departamento', 'provincia', 'distrito'
        $data = new \stdClass();

        try {
            switch ($opcion) {
                case 'Listar':
                    return $this->listarDatos($request, $tipo);

                case 'ListarFiltros':
                    return $this->listarFiltros($request);

                case 'Crear':
                    return $this->crearRegistro($request, $tipo);

                case 'Editar':
                    return $this->editarRegistro($request, $tipo);

                case 'Eliminar':
                    return $this->eliminarRegistro($request, $tipo);

                case 'CambiarEstado':
                    return $this->cambiarEstado($request, $tipo);

                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
            }
        } catch (\Exception $e) {
            $data->respuesta = 'error';
            $data->mensaje = 'Error: ' . $e->getMessage();
        }

        return response()->json($data);
    }

    private function listarDatos(Request $request, $tipo)
    {
        $currentUrl = $request->path();
        $permisosVista = [
            'crear' => MenuService::tienePermiso($currentUrl, 'crear'),
            'editar'   => MenuService::tienePermiso($currentUrl, 'editar'),
            'eliminar' => MenuService::tienePermiso($currentUrl, 'eliminar'),
            'configurar' => MenuService::tienePermiso($currentUrl, 'configurar'),
        ];

        $query = null;
        
        switch ($tipo) {
            case 'departamento':
                $query = Departamento::query()
                ->withCount('provincias');
                break;
                
            case 'provincia':
                $query = Provincia::with('departamento')
                    ->withCount('distritos') 
                    ->when($request->has('departamento_id') && $request->departamento_id, function($q) use ($request) {
                        $q->where('departamento_id', $request->departamento_id);
                    });
                break;
                
            case 'distrito':
                $query = Distrito::with(['provincia', 'provincia.departamento'])
                    ->when($request->has('provincia_id') && $request->provincia_id, function($q) use ($request) {
                        $q->where('provincia_id', $request->provincia_id);
                    })
                    ->when($request->has('departamento_id') && $request->departamento_id && !$request->provincia_id, function($q) use ($request) {
                        $q->whereHas('provincia', function($subq) use ($request) {
                            $subq->where('departamento_id', $request->departamento_id);
                        });
                    });
                break;
        }

        $datos = $query->get();

        return response()->json([
            'respuesta' => 'ok',
            'permisosVista' => $permisosVista,
            'data' => $datos
        ]);
    }

    private function listarFiltros(Request $request)
    {
        $filtro = $request->input('filtro');
        $data = new \stdClass();

        switch ($filtro) {
            case 'departamentos':
                $data->respuesta = 'ok';
                $data->departamentos = Departamento::orderBy('nombre')
                    ->get();
                break;

            case 'provincias_por_departamento':
                $departamentoId = $request->input('departamento_id');
                $data->respuesta = 'ok';
                $data->provincias = Provincia::where('departamento_id', $departamentoId)
                    ->orderBy('nombre')
                    ->get();
                break;

            default:
                $data->respuesta = 'error';
                $data->mensaje = 'Filtro no válido';
        }

        return response()->json($data);
    }

    private function crearRegistro(Request $request, $tipo)
    {
        DB::beginTransaction();
        try {
            $registro = null;
            $tabla = '';

            switch ($tipo) {
                case 'departamento':
                    $registro = Departamento::create([
                        'nombre' => $request->nombre,
                        'activo' => $request->activo ?? true
                    ]);
                    $tabla = 'departamentos';
                    break;

                case 'provincia':
                    $registro = Provincia::create([
                        'nombre' => $request->nombre,
                        'departamento_id' => $request->departamento_id,
                        'activo' => $request->activo ?? true
                    ]);
                    $tabla = 'provincias';
                    break;

                case 'distrito':
                    $registro = Distrito::create([
                        'nombre' => $request->nombre,
                        'costo_envio' => $request->costo_envio,
                        'provincia_id' => $request->provincia_id,
                        'activo' => $request->activo ?? true
                    ]);
                    $tabla = 'distritos';
                    break;
            }

            $this->registrarAuditoria('crear', $tabla, $registro->id, 
                ucfirst($tipo) . " creado: " . $request->nombre);

            DB::commit();

            return response()->json([
                'respuesta' => 'success',
                'mensaje' => ucfirst($tipo) . ' creado correctamente',
                'id' => $registro->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'respuesta' => 'error',
                'mensaje' => 'Error al crear: ' . $e->getMessage()
            ], 500);
        }
    }

    private function editarRegistro(Request $request, $tipo)
    {
        DB::beginTransaction();
        try {
            $registro = null;
            $tabla = '';

            switch ($tipo) {
                case 'departamento':
                    $registro = Departamento::findOrFail($request->id);
                    $registro->update([
                        'nombre' => $request->nombre,
                        'activo' => $request->activo
                    ]);
                    $tabla = 'departamentos';
                    break;

                case 'provincia':
                    $registro = Provincia::findOrFail($request->id);
                    $registro->update([
                        'nombre' => $request->nombre,
                        'departamento_id' => $request->departamento_id,
                        'activo' => $request->activo
                    ]);
                    $tabla = 'provincias';
                    break;

                case 'distrito':
                    $registro = Distrito::findOrFail($request->id);
                    $registro->update([
                        'nombre' => $request->nombre,
                        'costo_envio' => $request->costo_envio,
                        'provincia_id' => $request->provincia_id,
                        'activo' => $request->activo
                    ]);
                    $tabla = 'distritos';
                    break;
            }

            $this->registrarAuditoria('editar', $tabla, $registro->id, 
                ucfirst($tipo) . " editado: " . $request->nombre);

            DB::commit();

            return response()->json([
                'respuesta' => 'success',
                'mensaje' => ucfirst($tipo) . ' actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'respuesta' => 'error',
                'mensaje' => 'Error al editar: ' . $e->getMessage()
            ], 500);
        }
    }

    private function eliminarRegistro(Request $request, $tipo)
    {
        DB::beginTransaction();
        try {
            $registro = null;
            $tabla = '';
            $nombre = '';

            switch ($tipo) {
                case 'departamento':
                    $registro = Departamento::findOrFail($request->id);
                    $nombre = $registro->nombre;
                    $registro->delete(); 
                    $tabla = 'departamentos';
                    break;

                case 'provincia':
                    $registro = Provincia::findOrFail($request->id);
                    $nombre = $registro->nombre;
                    $registro->delete();
                    $tabla = 'provincias';
                    break;

                case 'distrito':
                    $registro = Distrito::findOrFail($request->id);
                    $nombre = $registro->nombre;
                    $registro->delete();
                    $tabla = 'distritos';
                    break;
            }

            $this->registrarAuditoria('eliminar', $tabla, $request->id, 
                ucfirst($tipo) . " eliminado: " . $nombre);

            DB::commit();

            return response()->json([
                'respuesta' => 'success',
                'mensaje' => ucfirst($tipo) . ' eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'respuesta' => 'error',
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    private function cambiarEstado(Request $request, $tipo)
    {
        try {
            $registro = null;
            $tabla = '';

            switch ($tipo) {
                case 'departamento':
                    $registro = Departamento::findOrFail($request->id);
                    $tabla = 'departamentos';
                    break;

                case 'provincia':
                    $registro = Provincia::findOrFail($request->id);
                    $tabla = 'provincias';
                    break;

                case 'distrito':
                    $registro = Distrito::findOrFail($request->id);
                    $tabla = 'distritos';
                    break;
            }

            $nuevoEstado = !$registro->activo;
            $registro->update(['activo' => $nuevoEstado]);

            $estadoTexto = $nuevoEstado ? 'activado' : 'desactivado';
            $this->registrarAuditoria('cambiar_estado', $tabla, $registro->id, 
                ucfirst($tipo) . " {$estadoTexto}: " . $registro->nombre);

            return response()->json([
                'respuesta' => 'success',
                'mensaje' => ucfirst($tipo) . ' ' . $estadoTexto . ' correctamente',
                'nuevo_estado' => $nuevoEstado
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'respuesta' => 'error',
                'mensaje' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }
}