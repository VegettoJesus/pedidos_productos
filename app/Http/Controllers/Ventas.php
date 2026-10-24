<?php

namespace App\Http\Controllers;

use App\Traits\AuditableTrait;
use App\Models\Pedido;
use App\Models\PedidoEstadoHistorial;
use App\Models\PedidoEvidencia;
use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\User;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Helpers\ConfiguracionHelper;
use App\Models\ConfiguracionCorreo;
use App\Models\Distrito;
use App\Models\Provincia;
use App\Models\Departamento;
use Illuminate\Validation\ValidationException;
use App\Models\PedidoReembolso;
use App\Models\PedidoItem;
use Illuminate\Support\Str;

class Ventas extends Controller
{
    use AuditableTrait;

    public function pedidos(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->procesarSolicitud($request);
        }

        $data = new \stdClass();
        $data->script = 'js/ventas-pedidos.js';
        $data->css = 'css/administracion.css';
        $data->contenido = 'ventas.pedidos';
        $data->estados = [
            'pendiente', 'pago_en_revision', 'pagado', 'en_preparacion',
            'enviado', 'entregado', 'cancelado', 'reembolsado', 'rechazado'
        ];
        return view('layouts.contenido', (array) $data);
    }

    private function procesarSolicitud(Request $request)
    {
        $opcion = $request->input('opcion');
        $data = new \stdClass();

        try {
            switch ($opcion) {
                case 'Listar':
                    return $this->listarPedidos($request);
                case 'Detalle':
                    return $this->detallePedido($request);
                case 'CambiarEstado':
                    return $this->cambiarEstado($request);
                case 'ActualizarDatos':
                    return $this->actualizarDatos($request);
                case 'SubirEvidencia':
                    return $this->subirEvidencia($request);
                case 'EliminarEvidencia':
                    return $this->eliminarEvidencia($request);
                case 'AgregarNota':
                    return $this->agregarNota($request);
                case 'Reembolsar':
                    return $this->reembolsar($request);
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

    private function reembolsar(Request $request)
    {
        DB::beginTransaction();
        try {
            $pedido = Pedido::with('items.producto', 'items.variacion.productoPadre')->findOrFail($request->pedido_id);

            if ($pedido->estado === 'reembolsado') {
                return response()->json(['respuesta' => 'error', 'mensaje' => 'Este pedido ya fue reembolsado en su totalidad.']);
            }

            $items = $request->items;
            if (empty($items) || !is_array($items)) {
                return response()->json(['respuesta' => 'error', 'mensaje' => 'No seleccionaste ningún item para reembolsar.']);
            }

            $montoProductos = 0;
            $detalleItems = [];

            foreach ($items as $row) {
                $item = PedidoItem::where('pedido_id', $pedido->id)->find($row['item_id']);
                if (!$item) continue;

                if ($row['cantidad'] > $item->cantidad) {
                    return response()->json(['respuesta' => 'error', 'mensaje' => "Cantidad inválida para el producto {$item->nombre_producto}"]);
                }

                $montoLinea = round($row['monto'], 2);
                $montoProductos += $montoLinea;

                $cantidadReembolsar = (int) $row['cantidad'];
                
                if ($item->variacion) {
                    $padre = $item->variacion->productoPadre;

                    if (($padre && $padre->gestion_inventario || $item->variacion->gestion_inventario) && !$item->variacion->backorders) {
                        $item->variacion->increment('stock', $cantidadReembolsar);
                    }
                } elseif ($item->producto) {
                    if ($item->producto->gestion_inventario && !$item->producto->backorders) {
                        $item->producto->increment('stock', $cantidadReembolsar);
                    }
                }

                $item->update(['estado_item' => 'reembolsado']);

                $detalleItems[] = [
                    'item_id' => $item->id,
                    'nombre' => $item->nombre_producto,
                    'cantidad' => $cantidadReembolsar,
                    'monto' => $montoLinea,
                ];
            }

            $todosReembolsados = $pedido->items()->where('estado_item', '!=', 'reembolsado')->count() === 0;
            $montoEnvio = (float) ($request->monto_envio ?? 0);
            $montoTotal = round($montoProductos + $montoEnvio, 2);

            $reembolso = PedidoReembolso::create([
                'pedido_id' => $pedido->id,
                'user_id' => Auth::id(),
                'monto_productos' => $montoProductos,
                'monto_envio' => $montoEnvio,
                'monto_total' => $montoTotal,
                'items_reembolsados' => $detalleItems,
                'motivo' => $request->motivo,
                'tipo' => $todosReembolsados ? 'total' : 'parcial',
            ]);

            $pedido->total = max(0, $pedido->total - $montoTotal);
            $pedido->estado_pago = $todosReembolsados ? 'reembolsado' : 'parcial';

            if ($todosReembolsados) {
                $pedido->estado = 'reembolsado';
            }

            $pedido->save();

            PedidoEstadoHistorial::create([
                'pedido_id' => $pedido->id,
                'user_id' => Auth::id(),
                'tipo_cambio' => 'reembolso',
                'estado_anterior' => $pedido->estado,
                'estado_nuevo' => $todosReembolsados ? 'reembolsado' : 'reembolso parcial',
                'motivo' => $request->motivo ?? 'Reembolso registrado',
                'metadata' => [
                    'reembolso_id' => $reembolso->id,
                    'monto_total' => $montoTotal,
                    'items' => $detalleItems,
                ],
            ]);

            $estadoNotificar = $todosReembolsados ? 'reembolsado' : 'reembolso parcial';
            
            $this->notificarClienteCambioEstado($pedido, $estadoNotificar);
            $this->enviarCorreoCambioEstadoCliente($pedido, $estadoNotificar);
            $this->registrarAuditoria(
                'Reembolso',
                'pedidos',
                $pedido->id,
                $pedido->codigo,
                null,
                ['monto_total' => $montoTotal, 'tipo' => $reembolso->tipo],
                "Reembolso aplicado: S/ {$montoTotal}"
            );

            DB::commit();

            return response()->json([
                'respuesta' => 'success',
                'mensaje' => "Reembolso de S/ {$montoTotal} registrado.",
                'reembolso' => $reembolso,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
        }
    }

    private function listarPedidos(Request $request)
    {
        $currentUrl = $request->path();
        $permisosVista = [
            'ver' => MenuService::tienePermiso($currentUrl, 'ver'),
            'editar' => MenuService::tienePermiso($currentUrl, 'editar'),
            'eliminar' => MenuService::tienePermiso($currentUrl, 'eliminar'),
        ];

        $query = Pedido::with(['user', 'metodoPago'])
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->when($request->estado_pago, fn($q) => $q->where('estado_pago', $request->estado_pago))
            ->when($request->fecha_desde, fn($q) => $q->whereDate('created_at', '>=', $request->fecha_desde))
            ->when($request->fecha_hasta, fn($q) => $q->whereDate('created_at', '<=', $request->fecha_hasta))
            ->when($request->buscar, function($q) use ($request) {
                $q->where(function($sub) use ($request) {
                    $sub->where('codigo', 'like', "%{$request->buscar}%")
                        ->orWhere('cliente_nombres', 'like', "%{$request->buscar}%")
                        ->orWhere('cliente_apellidos', 'like', "%{$request->buscar}%")
                        ->orWhere('cliente_email', 'like', "%{$request->buscar}%");
                });
            })
            ->orderBy('created_at', 'desc');

        $pedidos = $query->paginate(15);

        return response()->json([
            'respuesta' => 'ok',
            'permisosVista' => $permisosVista,
            'data' => $pedidos->items(), 
            'pagination' => [
                'current_page' => $pedidos->currentPage(),
                'last_page' => $pedidos->lastPage(),
                'total' => $pedidos->total(),
                'per_page' => $pedidos->perPage(),
                'has_more' => $pedidos->hasMorePages(),
            ]
        ]);
    }

    private function detallePedido(Request $request)
    {
        $pedido = Pedido::with([
            'user.datos',
            'items.producto',
            'items.atributos',
            'historial.user',
            'evidencias.subidoPor',
            'metodoPago',
            'departamento',
            'provincia',
            'distrito',
            'reembolsos.user',
        ])->findOrFail($request->id);

        return response()->json([
            'respuesta' => 'ok',
            'pedido'    => $pedido,   
            'permisos'  => [
                'editar' => MenuService::tienePermiso('Ventas/pedidos', 'editar'),
            ]
        ]);
    }

    private function cambiarEstado(Request $request)
    {
        DB::beginTransaction();
        try {
            $pedido = Pedido::with('items.producto', 'items.variacion.productoPadre')->findOrFail($request->id);
            $estadoAnterior = $pedido->estado;
            $estadoNuevo = $request->estado;

            if (!in_array($estadoNuevo, ['pendiente','pago_en_revision','pagado','en_preparacion','enviado','entregado','cancelado','reembolsado','rechazado'])) {
                return response()->json(['respuesta' => 'error', 'mensaje' => 'Estado inválido']);
            }

            $dataUpdate = ['estado' => $estadoNuevo];
            $tipoCambio = 'estado'; 

            if (in_array($estadoNuevo, ['pago_en_revision', 'pagado', 'rechazado'])) {
                $tipoCambio = 'estado_pago';
            }

            if (in_array($estadoNuevo, ['en_preparacion', 'enviado', 'entregado'])) {
                $tipoCambio = 'estado_envio';
            }

            if (in_array($estadoNuevo, ['reembolsado'])) {
                $tipoCambio = 'reembolso';
            }

            if ($estadoNuevo === 'pagado') {
                $dataUpdate['estado_pago'] = 'pagado';
                $dataUpdate['pagado_en'] = now();
            }

            if ($estadoNuevo === 'enviado') {
                $dataUpdate['estado_envio'] = 'en_transito';
                $dataUpdate['enviado_en'] = now();
            }

            if ($estadoNuevo === 'entregado') {
                $dataUpdate['estado_envio'] = 'entregado';
                $dataUpdate['entregado_en'] = now();
            }

            if ($estadoNuevo === 'cancelado') {
                $dataUpdate['fecha_cancelacion'] = now();
                $dataUpdate['motivo_cancelacion'] = $request->motivo;
                $dataUpdate['cancelado_por'] = Auth::id();
                $this->devolverStock($pedido);
            }

            $pedido->update($dataUpdate);

            PedidoEstadoHistorial::create([
                'pedido_id' => $pedido->id,
                'user_id' => Auth::id(),
                'tipo_cambio' => $tipoCambio, 
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'motivo' => $request->motivo ?? 'Cambio de estado por sistema',
            ]);

            $this->notificarClienteCambioEstado($pedido, $estadoNuevo);
            $this->enviarCorreoCambioEstadoCliente($pedido, $estadoNuevo); 
            $this->registrarAuditoria(
                'Actualizar',
                'pedidos',
                $pedido->id,
                $pedido->codigo,
                ['estado' => $estadoAnterior],
                ['estado' => $estadoNuevo],
                "Cambio de estado de pedido"
            );

            DB::commit();

            return response()->json([
                'respuesta' => 'success',
                'mensaje' => 'Estado actualizado correctamente',
                'nuevo_estado' => $estadoNuevo
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
        }
    }

    private function actualizarDatos(Request $request)
    {
        DB::beginTransaction();
        try {
            $pedido = Pedido::findOrFail($request->id);

            $valoresAnteriores = $pedido->only([
                'cliente_nombres','cliente_apellidos','cliente_email','cliente_celular',
                'cliente_documento_tipo','cliente_documento_numero',
                'departamento_id','provincia_id','distrito_id',
                'departamento_nombre','provincia_nombre','distrito_nombre',
                'direccion_entrega','referencia_entrega','nota_admin', 'costo_envio', 'total'
            ]);

            $datosActualizar = $request->only([
                'cliente_nombres','cliente_apellidos','cliente_email','cliente_celular',
                'cliente_documento_tipo','cliente_documento_numero',
                'departamento_id','provincia_id','distrito_id',
                'direccion_entrega','referencia_entrega','nota_admin'
            ]);

            if ($request->departamento_id) {
                $depto = Departamento::find($request->departamento_id);
                $datosActualizar['departamento_nombre'] = $depto ? $depto->nombre : null;
            }

            if ($request->provincia_id) {
                $prov = Provincia::find($request->provincia_id);
                $datosActualizar['provincia_nombre'] = $prov ? $prov->nombre : null;
            }

            if ($request->distrito_id) {
                $dist = Distrito::find($request->distrito_id);
                $datosActualizar['distrito_nombre'] = $dist ? $dist->nombre : null;
            }

            if ($request->has('nuevo_costo_envio')) {
                $nuevoCosto = (float) $request->nuevo_costo_envio;
                $costoAnterior = (float) $pedido->costo_envio;
                
                if ($nuevoCosto !== $costoAnterior) {
                    $diferencia = $nuevoCosto - $costoAnterior;
                    $datosActualizar['costo_envio'] = $nuevoCosto;
                    $datosActualizar['total'] = $pedido->total + $diferencia;
                }
            }

            $pedido->update($datosActualizar);
            $valoresNuevos = $pedido->only(array_keys($valoresAnteriores));

            if ($valoresAnteriores != $valoresNuevos) {
                PedidoEstadoHistorial::create([
                    'pedido_id' => $pedido->id,
                    'user_id' => Auth::id(),
                    'tipo_cambio' => 'item',
                    'estado_anterior' => 'Datos modificados', 
                    'estado_nuevo' => 'datos actualizados',   
                    'motivo' => $request->motivo ?? 'Actualización de datos por sistema',
                    'metadata' => [
                        'anteriores' => $valoresAnteriores,
                        'nuevos' => $valoresNuevos
                    ]
                ]);

                $this->registrarAuditoria(
                    'Actualizar',
                    'pedidos',
                    $pedido->id,
                    $pedido->codigo,
                    $valoresAnteriores,
                    $valoresNuevos,
                    "Actualización de datos"
                );
            }

            DB::commit();
            return response()->json(['respuesta' => 'success', 'mensaje' => 'Datos actualizados correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
        }
    }

    private function subirEvidencia(Request $request)
    {
        
        try {
            $request->validate([
                'pedido_id'      => 'required|exists:pedidos,id',
                'tipo_evidencia' => 'required|in:empaquetado,guia_envio,en_transito,entrega_cliente,dni_receptor,firma_receptor,incidencia,devolucion,otro',
                'archivo'        => 'required|file|mimes:jpg,jpeg,png,webp,pdf',
                'descripcion'    => 'nullable|string|max:1000',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'respuesta' => 'error',
                'mensaje'   => $e->validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();
        $rutaCompleta = null;
        try {
            $pedido = Pedido::findOrFail($request->pedido_id);
            $file = $request->file('archivo');
            $nombreOriginal = $file->getClientOriginalName();
            $mimeType       = $file->getMimeType();
            $tamanoBytes    = $file->getSize();
            $extension      = $file->getClientOriginalExtension();

            $nombreArchivo = time() . '_' . Str::random(8) . '.' . $extension;
            $carpeta       = 'evidencias_pedidos/' . $pedido->codigo;
            $rutaCarpeta   = public_path($carpeta);

            if (!file_exists($rutaCarpeta)) {
                if (!mkdir($rutaCarpeta, 0755, true) && !is_dir($rutaCarpeta)) {
                    throw new \RuntimeException("No se pudo crear la carpeta: {$carpeta}");
                }
            }
            if (!is_writable($rutaCarpeta)) {
                throw new \RuntimeException("Sin permisos de escritura en: {$carpeta}");
            }

            $file->move($rutaCarpeta, $nombreArchivo);
            $rutaCompleta = $rutaCarpeta . '/' . $nombreArchivo;
            $rutaPublica  = $carpeta . '/' . $nombreArchivo;

            $evidencia = PedidoEvidencia::create([
                'pedido_id'            => $pedido->id,
                'tipo_evidencia'       => $request->tipo_evidencia,
                'archivo_path'         => $rutaPublica,
                'nombre_original'      => $nombreOriginal,   
                'mime_type'            => $mimeType,         
                'tamano_bytes'         => $tamanoBytes,      
                'estado_envio_momento' => $pedido->estado_envio ?? 'pendiente',
                'subido_por'           => Auth::id(),
                'capturada_en'         => now(),
                'descripcion'          => $request->descripcion,
                'visible_cliente'      => filter_var($request->visible_cliente, FILTER_VALIDATE_BOOLEAN),
            ]);

            PedidoEstadoHistorial::create([
                'pedido_id'       => $pedido->id,
                'user_id'         => Auth::id(),
                'tipo_cambio'     => 'evidencia',
                'estado_anterior' => null,
                'estado_nuevo'    => 'evidencia subida',
                'motivo'          => 'Evidencia subida: ' . $request->tipo_evidencia,
                'metadata'        => ['evidencia_id' => $evidencia->id],
            ]);

            DB::commit();

            return response()->json([
                'respuesta'   => 'success',
                'mensaje'     => 'Evidencia subida correctamente',
                'evidencia'   => $evidencia,
                'url_publica' => asset($rutaPublica),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($rutaCompleta && file_exists($rutaCompleta)) {
                @unlink($rutaCompleta);
            }
            return response()->json([
                'respuesta' => 'error',
                'mensaje'   => 'No se pudo guardar la evidencia: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function eliminarEvidencia(Request $request)
    {
        try {
            $evidencia = PedidoEvidencia::findOrFail($request->id);

            $rutaCompleta = public_path($evidencia->archivo_path);
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }

            $evidencia->delete();

            return response()->json(['respuesta' => 'success', 'mensaje' => 'Evidencia eliminada']);
        } catch (\Exception $e) {
            return response()->json(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
        }
    }

    private function agregarNota(Request $request)
    {
        PedidoEstadoHistorial::create([
            'pedido_id' => $request->pedido_id,
            'user_id' => Auth::id(),
            'tipo_cambio' => 'nota', 
            'estado_anterior' => null,
            'estado_nuevo' => 'nota',
            'motivo' => $request->nota,
        ]);

        return response()->json(['respuesta' => 'success', 'mensaje' => 'Nota agregada']);
    }

    private function notificarClienteCambioEstado($pedido, $estadoNuevo)
    {
        $tipoCompras = TipoNotificacion::where('slug', 'compras')->first();
        if (!$tipoCompras) return;

        $mensajes = [
            'pagado' => 'Tu pago fue confirmado',
            'en_preparacion' => 'Estamos preparando tu pedido',
            'enviado' => 'Tu pedido está en camino',
            'entregado' => 'Tu pedido fue entregado',
            'cancelado' => 'Tu pedido fue cancelado',
            'rechazado' => 'Tu pago fue rechazado',
            'reembolsado' => 'Tu pedido ha sido reembolsado en su totalidad',
            'reembolso parcial' => 'Se ha procesado un reembolso parcial de tu pedido', 
        ];

        Notificacion::create([
            'tipo_notificacion_id' => $tipoCompras->id,
            'titulo' => 'Pedido ' . $pedido->codigo,
            'mensaje' => $mensajes[$estadoNuevo] ?? "El estado de tu pedido cambió a: {$estadoNuevo}",
            'mensaje_corto' => $mensajes[$estadoNuevo] ?? 'Actualización de pedido',
            'url' => route('pedido.detalle', $pedido->id),
            'boton_texto' => 'Ver mi pedido',
            'prioridad' => 'alta',
            'usuario_id' => $pedido->user_id,
            'visible' => true,
        ]);
    }

    private function devolverStock($pedido)
    {
        foreach ($pedido->items as $item) {
            if ($item->estado_item === 'reembolsado' && $pedido->estado !== 'cancelado') {
                continue;
            }

            $producto = $item->producto;
            $variacion = $item->variacion;

            if ($variacion) {
                $padre = $variacion->productoPadre; 
                $gestionaInventario = ($padre && $padre->gestion_inventario) || $variacion->gestion_inventario;
                
                if ($gestionaInventario && !$variacion->backorders) {
                    $variacion->increment('stock', $item->cantidad);
                }
            } 
            elseif ($producto) {
                if ($producto->gestion_inventario && !$producto->backorders) {
                    $producto->increment('stock', $item->cantidad);
                }
            }
        }
    }

    private function enviarCorreoCambioEstadoCliente($pedido, $estado)
    {
        try {
            $configuracion = ConfiguracionCorreo::getActiva();
            if (!$configuracion) return;

            $config = [
                'transport'  => 'smtp',
                'host'       => $configuracion->servidor_correo,
                'port'       => $configuracion->puerto,
                'encryption' => $configuracion->seguridad === 'ninguna' ? null : $configuracion->seguridad,
                'username'   => $configuracion->nombre_acceso,
                'password'   => $configuracion->contraseña,
                'timeout'    => 30,
            ];
            config(['mail.mailers.dynamic_smtp' => $config]);

            $fromEmail = $config['username'];
            $fromName  = ConfiguracionHelper::getCompanyName();

            $mensajes = [
                'pendiente' => 'Tu pedido está pendiente de confirmación.',
                'pago_en_revision' => 'Tu comprobante de pago está en revisión.',
                'pagado' => '¡Buenas noticias! Tu pago ha sido confirmado.',
                'en_preparacion' => 'Estamos preparando tu pedido.',
                'enviado' => 'Tu pedido ha sido enviado y está en camino.',
                'entregado' => 'Tu pedido ha sido entregado. ¡Gracias por tu compra!',
                'cancelado' => 'Tu pedido ha sido cancelado.',
                'reembolsado' => 'Tu pedido ha sido reembolsado en su totalidad.',
                'reembolso parcial' => 'Se ha procesado un reembolso parcial de tu pedido.', 
                'rechazado' => 'Tu pago ha sido rechazado.',
            ];

            $tituloEstado = $mensajes[$estado] ?? 'El estado de tu pedido ha cambiado.';

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
                <div style='background: #f4ab27; padding: 20px; text-align: center; color: #fff;'>
                    <h2>Actualización de Pedido</h2>
                </div>
                <div style='padding: 30px;'>
                    <p>Hola <strong>{$pedido->cliente_nombres}</strong>,</p>
                    <p>{$tituloEstado}</p>
                    <p>El estado de tu pedido <strong>{$pedido->codigo}</strong> ahora es: <strong style='text-transform: uppercase; color: #f4ab27;'>" . str_replace('_', ' ', $estado) . "</strong></p>
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='" . route('pedido.detalle', $pedido->id) . "' style='background: #f4ab27; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold;'>Ver mi pedido</a>
                    </p>
                </div>
                <div style='background: #f8f8f8; padding: 15px; text-align: center; font-size: 12px; color: #999;'>
                    &copy; " . date('Y') . " {$fromName}. Todos los derechos reservados.
                </div>
            </div>
            ";

            Mail::mailer('dynamic_smtp')->send([], [], function ($message) use ($pedido, $fromEmail, $fromName, $html, $estado) {
                $message->to($pedido->cliente_email, $pedido->cliente_nombres . ' ' . $pedido->cliente_apellidos)
                        ->from($fromEmail, $fromName)
                        ->subject('Actualización de tu pedido - ' . ucfirst(str_replace('_', ' ', $estado)))
                        ->html($html);
            });

        } catch (\Throwable $e) {
            \Log::error('Error al enviar correo de cambio de estado: ' . $e->getMessage());
        }
    }
}