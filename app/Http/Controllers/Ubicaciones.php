<?php

namespace App\Http\Controllers;

use App\Traits\AuditableTrait;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Provincia;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Ubicaciones extends Controller
{
    use AuditableTrait;

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
                    $this->registrarAuditoria(
                        'Crear',
                        $tabla,
                        $registro->id,
                        $registro->nombre,
                        null,
                        null,
                        "Tipo: Departamento | Activo: " . ($registro->activo ? 'Sí' : 'No')
                    );
                    break;

                case 'provincia':
                    $departamento = Departamento::find($request->departamento_id);
                    $nombreDepartamento = $departamento ? $departamento->nombre : 'ID: ' . $request->departamento_id;
                    $registro = Provincia::create([
                        'nombre' => $request->nombre,
                        'departamento_id' => $request->departamento_id,
                        'activo' => $request->activo ?? true
                    ]);
                    $tabla = 'provincias';
                    $this->registrarAuditoria(
                        'Crear',
                        $tabla,
                        $registro->id,
                        $registro->nombre,
                        null,
                        null,
                        "Departamento: {$nombreDepartamento} | Activo: " . ($registro->activo ? 'Sí' : 'No')
                    );
                    break;

                case 'distrito':
                    $provincia = Provincia::with('departamento')->find($request->provincia_id);
                    $nombreProvincia = $provincia ? $provincia->nombre : 'ID: ' . $request->provincia_id;
                    $nombreDepartamento = $provincia && $provincia->departamento ? $provincia->departamento->nombre : 'N/A';
                    
                    $registro = Distrito::create([
                        'nombre' => $request->nombre,
                        'costo_envio' => $request->costo_envio,
                        'provincia_id' => $request->provincia_id,
                        'activo' => $request->activo ?? true
                    ]);
                    $tabla = 'distritos';
                    
                    $costoFormateado = $registro->costo_envio ? 'S/ ' . number_format($registro->costo_envio, 2) : 'No definido';
                    
                    $this->registrarAuditoria(
                        'Crear',
                        $tabla,
                        $registro->id,
                        $registro->nombre,
                        null,
                        null,
                        "Provincia: {$nombreProvincia} | Departamento: {$nombreDepartamento} | Costo envío: {$costoFormateado} | Activo: " . ($registro->activo ? 'Sí' : 'No')
                    );
                    break;
            }

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
                    $valoresAnteriores = [
                        'nombre' => $registro->nombre,
                        'activo' => $registro->activo
                    ];
                    
                    $registro->update([
                        'nombre' => $request->nombre,
                        'activo' => $request->activo
                    ]);
                    $tabla = 'departamentos';
                    
                    $valoresNuevos = [
                        'nombre' => $registro->nombre,
                        'activo' => $registro->activo
                    ];
                    
                    $this->registrarAuditoria(
                        'Actualizar',
                        $tabla,
                        $registro->id,
                        $registro->nombre,
                        $valoresAnteriores,
                        $valoresNuevos,
                        "Tipo: Departamento"
                    );
                    break;

                case 'provincia':
                    $registro = Provincia::findOrFail($request->id);
                    $departamento = Departamento::find($request->departamento_id);
                    $nombreDepartamento = $departamento ? $departamento->nombre : 'ID: ' . $request->departamento_id;
                    
                    $valoresAnteriores = [
                        'nombre' => $registro->nombre,
                        'departamento_id' => $registro->departamento_id,
                        'activo' => $registro->activo
                    ];
                    
                    $registro->update([
                        'nombre' => $request->nombre,
                        'departamento_id' => $request->departamento_id,
                        'activo' => $request->activo
                    ]);
                    $tabla = 'provincias';
                    
                    $valoresNuevos = [
                        'nombre' => $registro->nombre,
                        'departamento_id' => $registro->departamento_id,
                        'activo' => $registro->activo
                    ];
                    
                    $this->registrarAuditoria(
                        'Actualizar',
                        $tabla,
                        $registro->id,
                        $registro->nombre,
                        $valoresAnteriores,
                        $valoresNuevos,
                        "Departamento: {$nombreDepartamento}"
                    );
                    break;

                case 'distrito':
                    $registro = Distrito::findOrFail($request->id);
                    $provincia = Provincia::with('departamento')->find($request->provincia_id);
                    $nombreProvincia = $provincia ? $provincia->nombre : 'ID: ' . $request->provincia_id;
                    $nombreDepartamento = $provincia && $provincia->departamento ? $provincia->departamento->nombre : 'N/A';
                    
                    $valoresAnteriores = [
                        'nombre' => $registro->nombre,
                        'costo_envio' => $registro->costo_envio,
                        'provincia_id' => $registro->provincia_id,
                        'activo' => $registro->activo
                    ];
                    
                    $registro->update([
                        'nombre' => $request->nombre,
                        'costo_envio' => $request->costo_envio,
                        'provincia_id' => $request->provincia_id,
                        'activo' => $request->activo
                    ]);
                    $tabla = 'distritos';
                    
                    $valoresNuevos = [
                        'nombre' => $registro->nombre,
                        'costo_envio' => $registro->costo_envio,
                        'provincia_id' => $registro->provincia_id,
                        'activo' => $registro->activo
                    ];
                    
                    $costoInfo = $registro->costo_envio ? 'S/ ' . number_format($registro->costo_envio, 2) : 'No definido';
                    
                    $this->registrarAuditoria(
                        'Actualizar',
                        $tabla,
                        $registro->id,
                        $registro->nombre,
                        $valoresAnteriores,
                        $valoresNuevos,
                        "Provincia: {$nombreProvincia} | Departamento: {$nombreDepartamento} | Costo envío: {$costoInfo}"
                    );
                    break;
            }

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
            $detalleExtra = '';

            switch ($tipo) {
                case 'departamento':
                    $registro = Departamento::findOrFail($request->id);
                    $nombre = $registro->nombre;
                    $cantidadProvincias = $registro->provincias()->count();
                    $detalleExtra = "Provincias asociadas: {$cantidadProvincias}";
                    $registro->delete(); 
                    $tabla = 'departamentos';
                    break;

                case 'provincia':
                    $registro = Provincia::findOrFail($request->id);
                    $nombre = $registro->nombre;
                    $departamento = $registro->departamento;
                    $nombreDepartamento = $departamento ? $departamento->nombre : 'N/A';
                    $cantidadDistritos = $registro->distritos()->count();
                    $detalleExtra = "Departamento: {$nombreDepartamento} | Distritos asociados: {$cantidadDistritos}";
                    $registro->delete();
                    $tabla = 'provincias';
                    break;

                case 'distrito':
                    $registro = Distrito::findOrFail($request->id);
                    $nombre = $registro->nombre;
                    $provincia = $registro->provincia;
                    $nombreProvincia = $provincia ? $provincia->nombre : 'N/A';
                    $detalleExtra = "Provincia: {$nombreProvincia}";
                    $registro->delete();
                    $tabla = 'distritos';
                    break;
            }

            $this->registrarAuditoria(
                'Eliminar',
                $tabla,
                $request->id,
                $nombre,
                null,
                null,
                $detalleExtra
            );

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
        DB::beginTransaction();
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

            $estadoAnterior = $registro->activo;
            $nuevoEstado = !$estadoAnterior;
            
            $valoresAnteriores = ['activo' => $estadoAnterior];
            $valoresNuevos = ['activo' => $nuevoEstado];
            
            $registro->update(['activo' => $nuevoEstado]);

            $estadoTexto = $nuevoEstado ? 'activado' : 'desactivado';
            
            $this->registrarAuditoria(
                'Actualizar',
                $tabla,
                $registro->id,
                $registro->nombre,
                $valoresAnteriores,
                $valoresNuevos,
                "Cambio de estado: {$estadoTexto}"
            );

            DB::commit();

            return response()->json([
                'respuesta' => 'success',
                'mensaje' => ucfirst($tipo) . ' ' . $estadoTexto . ' correctamente',
                'nuevo_estado' => $nuevoEstado
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'respuesta' => 'error',
                'mensaje' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }
}