<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Traits\AuditableTrait;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdministrarPagos extends Controller
{

    use AuditableTrait;
    /**
     * Vista principal con listado y edición
     */
    public function lista(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->handlePost($request);
        }

        $data = new \stdClass();
        $data->script = 'js/metodosPago.js';
        $data->css = 'css/administracion.css';
        $data->contenido = 'configuracion.metodosPago';

        $data->metodos = MetodoPago::ordenados()->get();
        $data->categorias = Categoria::orderBy('nombre')->get();
        $data->productos = Producto::where('estado', 'publicado')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'sku']);

        return view('layouts.contenido', (array) $data);
    }

    /**
     * Manejar peticiones POST (AJAX)
     */
    private function handlePost(Request $request)
    {
        $opcion = $request->input('opcion');
        $data = new \stdClass();

        switch ($opcion) {
            case 'Obtener':
                $id = $request->input('id');
                $metodo = MetodoPago::find($id);

                if (!$metodo) {
                    $data->respuesta = 'error';
                    $data->mensaje = 'Método no encontrado';
                    break;
                }

                $data->respuesta = 'ok';
                $data->metodo = $metodo;
                break;
            
            case 'Actualizar':
                $id = $request->input('id');
                $metodo = MetodoPago::find($id);

                if (!$metodo) {
                    $data->respuesta = 'error';
                    $data->mensaje = 'Método no encontrado';
                    break;
                }

                $validator = $this->validarMetodo($request, $metodo->slug);

                if ($validator->fails()) {
                    $data->respuesta = 'error';
                    $data->errores = $validator->errors();
                    $data->mensaje = 'Errores de validación'; 
                    break;
                }

                DB::beginTransaction();
                try {
                    $configAnterior = $metodo->configuracion;
                    $configNueva = $this->construirConfiguracion($request, $metodo->slug);
                    $this->eliminarImagenesHuerfanas($configAnterior, $configNueva);

                    $metodo->activo = $request->boolean('activo');
                    $metodo->configuracion = $configNueva;
                    $metodo->save();

                    $this->registrarAuditoria(
                        'Actualizar',
                        'metodos_pago',
                        $metodo->id,
                        "Método: {$metodo->nombre}",
                        ['configuracion' => json_encode($configAnterior)],     
                        ['configuracion' => json_encode($metodo->configuracion)], 
                        "Configuración actualizada"
                    );

                    DB::commit();

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Método de pago actualizado correctamente';
                    $data->metodo = $metodo;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $data->respuesta = 'error';
                    $data->mensaje = 'Error: ' . $e->getMessage();
                }
                break;

            case 'ToggleActivo':
                $id = $request->input('id');
                $metodo = MetodoPago::find($id);

                if (!$metodo) {
                    $data->respuesta = 'error';
                    $data->mensaje = 'Método no encontrado';
                    break;
                }

                $metodo->activo = !$metodo->activo;
                $metodo->save();

                $this->registrarAuditoria(
                    'Actualizar',
                    'metodos_pago',
                    $metodo->id,
                    "Método: {$metodo->nombre}",
                    ['activo' => !$metodo->activo],
                    ['activo' => $metodo->activo],
                    $metodo->activo ? 'Activado' : 'Desactivado'
                );

                $data->respuesta = 'ok';
                $data->activo = $metodo->activo;
                $data->mensaje = $metodo->activo ? 'Método activado' : 'Método desactivado';
                break;

            case 'ActualizarOrden':
                $orden = $request->input('orden', []);

                DB::beginTransaction();
                try {
                    foreach ($orden as $index => $id) {
                        MetodoPago::where('id', $id)->update(['orden' => $index + 1]);
                    }
                    DB::commit();

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Orden actualizado';
                } catch (\Exception $e) {
                    DB::rollBack();
                    $data->respuesta = 'error';
                    $data->mensaje = 'Error: ' . $e->getMessage();
                }
                break;
            case 'BuscarCategorias':
                $query = $request->input('query', '');
                $categorias = Categoria::where('nombre', 'LIKE', "%{$query}%")
                    ->orderBy('nombre')
                    ->limit(20)
                    ->get(['id', 'nombre', 'icono']);
                
                $data->respuesta = 'ok';
                $data->categorias = $categorias;
                break;

            case 'BuscarProductos':
                $query = $request->input('query', '');
                $productos = Producto::where('estado', 'publicado')
                    ->where(function($q) use ($query) {
                        $q->where('nombre', 'LIKE', "%{$query}%")
                        ->orWhere('sku', 'LIKE', "%{$query}%");
                    })
                    ->orderBy('nombre')
                    ->limit(20)
                    ->get(['id', 'nombre', 'sku']);
                
                $data->respuesta = 'ok';
                $data->productos = $productos;
                break;
            case 'ObtenerCategoriasPorIds':
                $ids = $request->input('ids', []);
                $categorias = Categoria::whereIn('id', $ids)
                    ->get(['id', 'nombre', 'icono']);
                
                $data->respuesta = 'ok';
                $data->categorias = $categorias;
                break;

            case 'ObtenerProductosPorIds':
                $ids = $request->input('ids', []);
                $productos = Producto::whereIn('id', $ids)
                    ->get(['id', 'nombre', 'sku']);
                
                $data->respuesta = 'ok';
                $data->productos = $productos;
                break;
            case 'SubirImagen':
                return $this->subirImagen($request);
                

            default:
                $data->respuesta = 'error';
                $data->mensaje = 'Opción inválida';
                break;
        }

        return response()->json($data);
    }

    /**
     * Validar según el método
     */
    private function validarMetodo(Request $request, string $slug)
    {
        $rules = [
            'titulo' => 'required|string|max:100',
            'activo' => 'nullable|in:0,1,on,true,false',
        ];

        return Validator::make($request->all(), $rules);
    }

    /**
     * Construir el JSON de configuración según el slug
     */
    private function construirConfiguracion(Request $request, string $slug): array
    {
        switch ($slug) {
            case 'contra_entrega':
                return [
                    'titulo' => $request->input('titulo', ''),
                    'descripcion' => $request->input('descripcion_cfg', ''),
                    'instrucciones' => $request->input('instrucciones', ''),
                    'desactivar_si_importe_mayor' => $request->input('desactivar_si_importe_mayor'),
                    'restriccion_categoria_modo' => $request->input('restriccion_categoria_modo', ''),
                    'categorias_desactivar' => $request->input('categorias_desactivar', []),
                    'restriccion_producto_modo' => $request->input('restriccion_producto_modo', ''),
                    'productos_desactivar' => $request->input('productos_desactivar', []),
                    'mensaje_no_disponible' => $request->input('mensaje_no_disponible', ''),
                    'cargo_adicional' => [
                        'tipo' => $request->input('cargo_tipo', 'porcentaje'),
                        'valor' => (float) $request->input('cargo_valor', 0),
                        'impuesto_activo' => $request->boolean('impuesto_activo'),
                        'impuesto_porcentaje' => (float) $request->input('impuesto_porcentaje', 0),
                        'desactivar_si_importe_mayor' => $request->input('cargo_desactivar_si_importe_mayor'),
                        'incluir_en_total' => [
                            'impuestos' => $request->boolean('incluir_impuestos'),
                            'envio' => $request->boolean('incluir_envio'),
                        ],
                    ],
                ];

            case 'transferencia':
                $cuentas = $request->input('cuentas', []);
                $cuentas = array_values(array_filter($cuentas, function($c) {
                    return !empty($c['nombre_cuenta']) 
                        || !empty($c['numero_cuenta']) 
                        || !empty($c['nombre_banco'])
                        || !empty($c['cci']);
                }));
                
                return [
                    'titulo' => $request->input('titulo', ''),
                    'descripcion' => $request->input('descripcion_cfg', ''),
                    'instrucciones' => $request->input('instrucciones', ''),
                    'cuentas' => $cuentas,
                ];

            case 'qr':
                return [
                    'titulo' => $request->input('titulo', ''),
                    'icono_imagen' => $request->input('icono_imagen'),
                    'descripcion' => $request->input('descripcion_cfg', ''),
                    'mensaje_emergente' => $request->input('mensaje_emergente', ''),
                    'importe_limite' => $request->input('importe_limite'),
                    'mensaje_limite' => $request->input('mensaje_limite', ''),
                    'telefono_afiliado' => $request->input('telefono_afiliado', ''),
                    'imagen_qr' => $request->input('imagen_qr'),
                ];

            default:
                return [];
        }
    }

    /**
     * Subir imagen (QR o icono)
     */
    private function subirImagen(Request $request)
    {
        $data = new \stdClass();

        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'tipo' => 'required|in:qr,icono',
        ]);

        try {
            $file = $request->file('imagen');
            $carpeta = public_path('metodos_pago');

            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            $tipo = $request->input('tipo');
            $metodoId = $request->input('metodo_id'); 

            if ($metodoId) {
                $metodo = MetodoPago::find($metodoId);
                if ($metodo) {
                    $campoAnterior = $tipo === 'qr' 
                        ? ($metodo->configuracion['imagen_qr'] ?? null)
                        : ($metodo->configuracion['icono_imagen'] ?? null);

                    if ($campoAnterior && file_exists($carpeta . '/' . $campoAnterior)) {
                        unlink($carpeta . '/' . $campoAnterior);
                    }
                }
            }

            $nombreArchivo = $tipo . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($carpeta, $nombreArchivo);

            $data->respuesta = 'ok';
            $data->url = asset('metodos_pago/' . $nombreArchivo);
            $data->path = $nombreArchivo;
            $data->mensaje = 'Imagen subida correctamente';

        } catch (\Exception $e) {
            $data->respuesta = 'error';
            $data->mensaje = 'Error al subir imagen: ' . $e->getMessage();
        }

        return response()->json($data);
    }

    /**
     * Elimina del disco las imágenes que ya no se usan después de una actualización.
     * Compara la config anterior con la nueva y borra lo que quedó huérfano.
     */
    private function eliminarImagenesHuerfanas(array $configAnterior, array $configNueva): void
    {
        $carpeta = public_path('metodos_pago');
        $camposImagen = ['imagen_qr', 'icono_imagen'];

        foreach ($camposImagen as $campo) {
            $anterior = $configAnterior[$campo] ?? null;
            $nueva = $configNueva[$campo] ?? null;

            if ($anterior && $anterior !== $nueva) {
                $rutaArchivo = $carpeta . '/' . $anterior;
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
            }
        }
    }
}