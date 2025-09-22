<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Auditoria;
use App\Models\Subcategoria;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Catalogo extends Controller
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

    public function categorias(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $categorias = Categoria::with('subcategorias')->get();
                    $currentUrl = $request->path();
                    $data->permisosVista = [
                        'editar' => MenuService::tienePermiso($currentUrl, 'editar'),
                        'eliminar' => MenuService::tienePermiso($currentUrl, 'eliminar'),
                        'subcategoria' => MenuService::tienePermiso($currentUrl, 'subcategoria'),
                    ];
                    $data->respuesta = 'ok';
                    $data->categorias = $categorias;
                    break;

                case 'Crear':
                    $categoria = Categoria::create([
                        'nombre' => $request->input('nombre'),
                        'icono'  => $request->input('icono')
                    ]);
                    $this->registrarAuditoria('Crear', 'categorias', $categoria->id, "Categoría: {$categoria->nombre}");
                    $data->respuesta = 'ok';
                    $data->mensaje = 'Categoría creada correctamente';
                    break;

                case 'Editar':
                    $categoria = Categoria::find($request->input('id'));
                    if ($categoria) {
                        $data->respuesta = 'ok';
                        $data->categoria = $categoria;
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Categoría no encontrada';
                    }
                    break;

                case 'Actualizar':
                    $categoria = Categoria::find($request->input('id'));
                    if ($categoria) {
                        $categoria->update([
                            'nombre' => $request->input('nombre'),
                            'icono'  => $request->input('icono')
                        ]);
                        $this->registrarAuditoria('Actualizar', 'categorias', $categoria->id, "Categoría: {$categoria->nombre}");
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Categoría actualizada correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Categoría no encontrada';
                    }
                    break;

                case 'Eliminar':
                    $categoria = Categoria::find($request->input('id'));
                    if ($categoria) {
                        $this->registrarAuditoria('Eliminar', 'categorias', $categoria->id, "Categoría: {$categoria->nombre}");
                        $categoria->delete();
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Categoría eliminada correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Categoría no encontrada';
                    }
                    break;
                case 'Crear_Subcategoria':
                    $subcategoria = Subcategoria::create([
                        'nombre' => $request->input('nombre'),
                        'icono'  => $request->input('icono'),
                        'id_categoria' => $request->input('id_categoria'),
                    ]);
                    $this->registrarAuditoria('Crear', 'subcategorias', $subcategoria->id, "Subcategoría: {$subcategoria->nombre}");
                    $data->respuesta = 'ok';
                    $data->mensaje = 'Subcategoría creada correctamente';
                    break;
                case 'Editar_Subcategoria':
                    $subcategoria = Subcategoria::find($request->input('id'));
                    if ($subcategoria) {
                        $data->respuesta = 'ok';
                        $data->subcategoria = $subcategoria;
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Subcategoría no encontrada';
                    }
                    break;
                case 'Actualizar_Subcategoria':
                    $subcategoria = Subcategoria::find($request->input('id'));
                    if ($subcategoria) {
                        $subcategoria->update([
                            'nombre' => $request->input('nombre'),
                            'icono'  => $request->input('icono'),
                            'id_categoria' => $request->input('id_categoria'),
                        ]);
                        $this->registrarAuditoria('Actualizar', 'subcategorias', $subcategoria->id, "Subcategoría: {$subcategoria->nombre}");
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Subcategoría actualizada correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Subcategoría no encontrada';
                    }
                    break;

                case 'Eliminar_Subcategoria':
                    $subcategoria = Subcategoria::find($request->input('id'));
                    if ($subcategoria) {
                        $this->registrarAuditoria('Eliminar', 'subcategorias', $subcategoria->id, "Subcategoría: {$subcategoria->nombre}");
                        $subcategoria->delete();
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Subcategoría eliminada correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Subcategoría no encontrada';
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
            $data->script = 'js/categorias.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'catalogo.categorias';
            return view('layouts.contenido', (array) $data);
        }
    }
}
