<?php
// app/Http/Controllers/CheckoutController.php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\MetodoPago;
use App\Models\EmpresaInformacion;
use App\Models\ReservaStock;
use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\ConfiguracionCorreo;
use App\Models\Producto;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Distrito;
use App\Models\UsuarioDato;
use App\Models\ConfiguracionSistema;
use App\Models\User;
use App\Models\ProductoVariacion;
use App\Services\MetodoPagoService;
use App\Helpers\ConfiguracionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    private function cliente()
    {
        if (!Auth::check() || Auth::user()->id_rol !== 2) {
            return null;
        }
        return Auth::user();
    }

    public function index()
    {
        if (!$this->cliente()) {
            return redirect()->route('tienda.home')
                ->with('warning', 'Debes iniciar sesión para continuar');
        }

        $user = Auth::user();

        $carrito = Carrito::where('user_id', Auth::id())
            ->with(['items.producto.subcategoria', 'items.variacion'])
            ->first();

        if (!$carrito || $carrito->items->isEmpty()) {
            return redirect()->route('carrito.vista')
                ->with('warning', 'Tu carrito está vacío');
        }

        $usuarioDato = UsuarioDato::where('id_usuario', $user->id)->first();

        $ubicacionValida = false;
        $ubicacionCliente = [
            'departamento_id' => null,
            'provincia_id' => null,
            'distrito_id' => null,
            'direccion_completa' => '',
        ];

        if ($usuarioDato && $usuarioDato->departamento && $usuarioDato->provincia && $usuarioDato->distrito) {
            $depto = Departamento::where('id', $usuarioDato->departamento)->where('activo', true)->first();
            $prov = Provincia::where('id', $usuarioDato->provincia)
                ->where('departamento_id', $usuarioDato->departamento)
                ->where('activo', true)
                ->first();
            $dist = Distrito::where('id', $usuarioDato->distrito)
                ->where('provincia_id', $usuarioDato->provincia)
                ->where('activo', true)
                ->first();

            if ($depto && $prov && $dist) {
                $ubicacionValida = true;
                $ubicacionCliente = [
                    'departamento_id' => $depto->id,
                    'provincia_id'    => $prov->id,
                    'distrito_id'     => $dist->id,
                    'direccion_completa' => trim(
                        ($usuarioDato->calle ?? '') . ' ' .
                        ($usuarioDato->numero ?? '') . ' ' .
                        ($usuarioDato->dir_otros ?? '')
                    ),
                ];
            }
        }

        $subtotal = $carrito->items->sum(fn($i) => $i->cantidad * $i->precio_unitario);
        $envioBase = 0;
        if ($ubicacionValida && !empty($ubicacionCliente['distrito_id'])) {
            $distrito = Distrito::find($ubicacionCliente['distrito_id']);
            $envioBase = $distrito?->costo_envio ?? 0;
        }

        $impuestos = 0;

        $metodoPagoService = app(\App\Services\MetodoPagoService::class);
        $metodosEvaluados = $metodoPagoService->evaluar($carrito->items, $impuestos, $envioBase);

        $departamentos = Departamento::where('activo', true)->get();
        $empresa = EmpresaInformacion::first();
        $config = ConfiguracionSistema::first();

        return view('layouts.contenido2', [
            'titulo_site' => $config?->titulo_site,
            'descripcion_corta' => $config?->descripcion_corta,
            'authUser' => [
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'foto' => asset('img/user.png'),
            ],
            'contenido2' => 'tienda.checkout',
            'script' => 'js/checkout.js',
            'carrito' => $carrito,
            'metodosEvaluados' => $metodosEvaluados,   
            'departamentos' => $departamentos,
            'ubicacionValida' => $ubicacionValida,
            'ubicacionCliente' => $ubicacionCliente,
            'empresa' => $empresa,
        ]);
    }

    /**
     * Confirmar pedido
     */
    public function confirmar(Request $request)
    {
        if (!Auth::check() || Auth::user()->id_rol !== 2) {
            return response()->json(['ok' => false, 'mensaje' => 'No autorizado'], 401);
        }

        $request->validate([
            'tipo_entrega' => 'required|in:delivery,retiro',
            'tipo_pago' => 'required|string',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'provincia_id' => 'nullable|exists:provincias,id',
            'id_distrito' => 'nullable|exists:distritos,id',
            'direccion_entrega' => 'nullable|string|max:500',
            'imagen' => 'nullable|image|max:4096',
        ]);

        $user = Auth::user();
        $carrito = Carrito::where('user_id', $user->id)
            ->with(['items.producto', 'items.variacion.atributos'])
            ->first();

        if (!$carrito || $carrito->items->isEmpty()) {
            return response()->json(['ok' => false, 'mensaje' => 'Carrito vacío'], 400);
        }

        try {
            DB::beginTransaction();

            $subtotalRegular = 0; 
            $descuentoTotal = 0;
            $itemsAProcesar = [];

            foreach ($carrito->items as $item) {
                $producto = $item->producto;
                $variacion = $item->variacion;

                $precioRegular = $variacion ? $variacion->precio_regular : $producto->precio_regular;
                $precioFinal = $item->precio_unitario;
                
                $subtotalRegular += $item->cantidad * $precioRegular;

                $precioRebajado = null;
                $porcentajeDesc = 0;
                if ($precioRegular > $precioFinal) {
                    $descuentoLinea = ($precioRegular - $precioFinal) * $item->cantidad;
                    $descuentoTotal += $descuentoLinea;
                    $precioRebajado = $precioFinal;
                    $porcentajeDesc = (1 - ($precioFinal / $precioRegular)) * 100;
                }

                $itemsAProcesar[$item->id] = [
                    'precioRegular' => $precioRegular,
                    'precioFinal' => $precioFinal,
                    'precioRebajado' => $precioRebajado,
                    'porcentajeDesc' => $porcentajeDesc,
                ];
            }

            $costoEnvio = 0;
            if ($request->tipo_entrega === 'delivery' && $request->id_distrito) {
                $distrito = Distrito::find($request->id_distrito);
                $costoEnvio = $distrito?->costo_envio ?? 0;
            }

            $metodoPagoService = app(MetodoPagoService::class);
            $metodo = MetodoPago::where('slug', $request->tipo_pago)->first();
            
            if (!$metodo) {
                return response()->json(['ok' => false, 'mensaje' => 'Método de pago inválido'], 400);
            }
            $subtotalConDescuento = $subtotalRegular - $descuentoTotal;
            $cargoCalculado = $metodoPagoService->calcularCargoAdicional($metodo, $subtotalConDescuento, 0, $costoEnvio);
            $cargoBase = $cargoCalculado['cargo_base'];
            $impuestoCargo = $cargoCalculado['impuesto'];
            
            $total = $subtotalRegular + $costoEnvio + $cargoBase + $impuestoCargo - $descuentoTotal;

            $voucherPath = null;
            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                $nombre = 'voucher_' . time() . '_' . Str::random(5) . '.' . $file->getClientOriginalExtension();
                $carpeta = public_path('vouchers');
                if (!file_exists($carpeta)) mkdir($carpeta, 0755, true);
                $file->move($carpeta, $nombre);
                $voucherPath = $nombre;
            }

            $deptoNombre = null; $provNombre = null; $distNombre = null;
            if ($request->tipo_entrega === 'delivery' && $request->id_distrito) {
                $dist = Distrito::with('provincia.departamento')->find($request->id_distrito);
                if ($dist) {
                    $distNombre = $dist->nombre;
                    $provNombre = $dist->provincia->nombre;
                    $deptoNombre = $dist->provincia->departamento->nombre;
                }
            }

            $puntoRetiro = $request->tipo_entrega === 'retiro' ? (EmpresaInformacion::first()?->direccion ?? 'Tienda principal') : null;
            $codigoPedido = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(4));

            $pedido = Pedido::create([
                'codigo' => $codigoPedido,
                'user_id' => $user->id,
                'cliente_nombres' => $user->nombres,
                'cliente_apellidos' => $user->apellidos,
                'cliente_email' => $user->email,
                'cliente_celular' => $user->datos?->celular,
                'cliente_documento_tipo' => $user->datos?->tipoDoc,
                'cliente_documento_numero' => $user->datos?->numeroDoc,
                'tipo_entrega' => $request->tipo_entrega,
                'punto_retiro' => $puntoRetiro,
                'departamento_id' => $request->tipo_entrega === 'delivery' ? $request->departamento_id : null,
                'provincia_id' => $request->tipo_entrega === 'delivery' ? $request->provincia_id : null,
                'distrito_id' => $request->tipo_entrega === 'delivery' ? $request->id_distrito : null,
                'departamento_nombre' => $deptoNombre,
                'provincia_nombre' => $provNombre,
                'distrito_nombre' => $distNombre,
                'direccion_entrega' => $request->tipo_entrega === 'delivery' ? $request->direccion_entrega : null,
                'metodo_pago_id' => $metodo->id,
                'metodo_pago_slug' => $metodo->slug,
                'metodo_pago_nombre' => $metodo->nombre,
                'metodo_pago_config_snapshot' => $metodo->configuracion,
                'voucher_path' => $voucherPath,
                'voucher_subido_en' => $voucherPath ? now() : null,
                'subtotal' => $subtotalRegular,
                'costo_envio' => $costoEnvio,
                'cargo_metodo_pago' => $cargoBase,
                'impuesto_cargo' => $impuestoCargo,
                'descuento_total' => $descuentoTotal,
                'total' => $total,
                'estado' => 'pendiente',
                'estado_pago' => $voucherPath ? 'pendiente_verificacion' : 'no_pagado',
                'estado_envio' => $request->tipo_entrega === 'delivery' ? 'pendiente' : 'no_aplica',
                'ip_cliente' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $productosParaAlerta = [];

            foreach ($carrito->items as $item) {
                $producto = $item->producto;
                $variacion = $item->variacion;
                $calculo = $itemsAProcesar[$item->id];

                $nuevoItem = $pedido->items()->create([
                    'producto_id' => $producto->id,
                    'variacion_id' => $variacion?->id,
                    'producto_padre_id' => $item->producto_padre_id,
                    'nombre_producto' => $producto->nombre,
                    'sku' => $variacion ? $variacion->sku : $producto->sku,
                    'tipo_item' => $variacion ? 'variacion' : 'simple',
                    'cantidad' => $item->cantidad,
                    'precio_unitario_regular' => $calculo['precioRegular'], 
                    'precio_unitario_rebajado' => $calculo['precioRebajado'], 
                    'precio_unitario_final' => $calculo['precioFinal'], 
                    'descuento_porcentaje' => round($calculo['porcentajeDesc'], 2), 
                    'subtotal_linea' => $item->cantidad * $calculo['precioFinal'],
                    'imagen_path' => $producto->imagen_miniatura,
                ]);

                if ($variacion) {
                    foreach ($variacion->atributos as $termino) {
                        $nuevoItem->atributos()->create([
                            'atributo_id' => $termino->atributo_id,
                            'atributo_termino_id' => $termino->id,
                            'atributo_nombre' => $termino->atributo->nombre,
                            'termino_nombre' => $termino->nombre,
                            'valor_extra' => $termino->pivot->valor_extra ?? null,
                        ]);
                    }
                }

                $alerta = $this->verificarStockBajo($item);
                if ($alerta) {
                    $productosParaAlerta[] = $alerta;
                }

                ReservaStock::where('carrito_item_id', $item->id)
                    ->where('liberada', false)
                    ->update(['liberada' => true]);
            }

            $pedido->historial()->create([
                'user_id' => $user->id,
                'tipo_cambio' => 'estado',
                'estado_anterior' => null,
                'estado_nuevo' => 'pendiente',
                'motivo' => 'Pedido creado desde el checkout web',
            ]);

            $carrito->items()->delete();
            $carrito->tocar();

            DB::commit();

            $this->notificarNuevaCompra($pedido);
            $this->enviarCorreoNuevaCompraAdmin($pedido);
            $this->crearNotificacionCompra($pedido, $user);

            if (!empty($productosParaAlerta)) {
                $this->notificarStockBajo($productosParaAlerta);
                $this->enviarCorreoStockBajo($productosParaAlerta); 
            }

            $this->enviarCorreoCompraCliente($pedido, $user);

            return response()->json([
                'ok' => true,
                'pedido_id' => $pedido->id,
                'mensaje' => '¡Pedido ' . $pedido->codigo . ' creado correctamente!',
                'redirect' => route('pedido.gracias', $pedido->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al procesar checkout: ' . $e->getMessage());
            return response()->json(['ok' => false, 'mensaje' => 'Error interno al procesar el pedido.'], 500);
        }
    }

    /**
     * Verifica si el stock llegó al mínimo tras la compra.
     */
    private function verificarStockBajo($item)
    {
        $producto = $item->producto;
        $variacion = $item->variacion;
        $stockActual = 0;
        $stockMinimo = 0;
        $nombre = '';
        $sku = '';
        $estado = null;

        if ($variacion) {
            if (!$producto->gestion_inventario) {
                if ($variacion->gestion_inventario && $variacion->estado_inventario !== 'reservar') {
                    $stockActual = $variacion->stock;
                    $stockMinimo = $producto->stock_minimo ?? 0;
                    $nombre = $producto->nombre . ' - ' . $variacion->nombre;
                    $sku = $variacion->sku;
                }
            }
        } 
        else {
            if ($producto->gestion_inventario && $producto->estado_inventario !== 'reservar') {
                $stockActual = $producto->stock;
                $stockMinimo = $producto->stock_minimo;
                $nombre = $producto->nombre;
                $sku = $producto->sku;
            }
        }

        if ($nombre === '') return null;
        if ($stockActual == 0) {
            $estado = 'agotado';
        } elseif ($stockActual <= $stockMinimo && $stockMinimo > 0) {
            $estado = 'bajo';
        } else {
            return null; 
        }

        return [
            'nombre' => $nombre,
            'sku' => $sku,
            'stock_actual' => $stockActual,
            'stock_minimo' => $stockMinimo,
            'estado' => $estado, 
        ];
    }

    /**
     * Crea notificación para los usuarios que tengan el permiso (Tipo: Compras)
     */
    private function notificarNuevaCompra($pedido)
    {
        $tipoCompras = TipoNotificacion::where('slug', 'compras')->first();
        if (!$tipoCompras) return;

        $usuariosNotificar = User::where('id_rol', '!=', 2)
            ->where(function ($query) use ($tipoCompras) {
                $query->whereHas('rol.permisosNotificaciones', function ($q) use ($tipoCompras) {
                    $q->where('tipo_notificacion_id', $tipoCompras->id)->where('puede_ver', true);
                })
                ->orWhereHas('excepcionesNotificaciones', function ($q) use ($tipoCompras) {
                    $q->where('tipo_notificacion_id', $tipoCompras->id)->where('tipo_excepcion', 'permitir');
                });
            })
            ->whereDoesntHave('excepcionesNotificaciones', function ($q) use ($tipoCompras) {
                $q->where('tipo_notificacion_id', $tipoCompras->id)->where('tipo_excepcion', 'denegar');
            })
            ->get();

        if ($usuariosNotificar->isEmpty()) {
            return;
        }

        $notificacion = Notificacion::create([
            'tipo_notificacion_id' => $tipoCompras->id,
            'titulo' => 'Nuevo Pedido Recibido',
            'mensaje' => "El cliente {$pedido->cliente_nombres} {$pedido->cliente_apellidos} ha realizado un pedido por un total de S/ " . number_format($pedido->total, 2) . ".",
            'mensaje_corto' => "Nuevo pedido {$pedido->codigo}",
            'url' => 'Ventas/pedidos',
            'boton_texto' => 'Ver Pedido',
            'prioridad' => 'alta',
            'visible' => true,
        ]);

        foreach ($usuariosNotificar as $user) {
            $notificacion->usuarios()->syncWithoutDetaching([$user->id]);
        }
    }

    private function notificarStockBajo($productos)
    {
        $tipoInv = TipoNotificacion::where('slug', 'inventario')->first();
        if (!$tipoInv) return;

        foreach ($productos as $prod) {
            $esAgotado = ($prod['estado'] === 'agotado');
            
            $titulo = $esAgotado ? '🚨 Producto Agotado' : 'Alerta de Stock Bajo';
            $mensaje = $esAgotado 
                ? "El producto '{$prod['nombre']}' (SKU: {$prod['sku']}) se ha agotado por completo (Stock: 0)."
                : "El producto '{$prod['nombre']}' (SKU: {$prod['sku']}) ha llegado a su nivel mínimo. Stock actual: {$prod['stock_actual']} (Mínimo: {$prod['stock_minimo']}).";
            
            $prioridad = $esAgotado ? 'alta' : 'media';

            $notificacion = Notificacion::create([
                'tipo_notificacion_id' => $tipoInv->id,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'mensaje_corto' => ($esAgotado ? "Agotado: " : "Stock bajo: ") . $prod['nombre'],
                'url' => 'Catalogo/productos',
                'boton_texto' => 'Revisar Inventario',
                'prioridad' => $prioridad,
                'visible' => true,
            ]);

            $this->asignarNotificacionAUsuarios($notificacion, $tipoInv->id);
        }
    }

    /**
     * Enviar Correo Electrónico usando configuración SMTP dinámica
     */
    private function enviarCorreoCompraCliente($pedido, $user)
    {
        try {
            $configuracion = ConfiguracionCorreo::getActiva();
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
            $html = $this->htmlCorreoCompra($pedido, $user);

            Mail::mailer('dynamic_smtp')->send([], [], function ($message) use ($user, $pedido, $fromEmail, $fromName, $html) {
                $message->to($user->email, $user->nombres . ' ' . $user->apellidos)
                        ->from($fromEmail, $fromName)
                        ->subject('🛍️ ¡Gracias por tu compra! - ' . $pedido->codigo)
                        ->html($html);
            });

        } catch (\Throwable $e) {
            \Log::error('Fallo al enviar correo de compra: ' . $e->getMessage());
            
            $tipoError = TipoNotificacion::where('slug', 'error-sistema')->first();
            
            if ($tipoError) {
                $notificacion = Notificacion::create([
                    'tipo_notificacion_id' => $tipoError->id,
                    'titulo' => 'Error al enviar correo de pedido',
                    'mensaje' => "No se pudo enviar el correo al cliente para el pedido {$pedido->codigo}. Error: " . $e->getMessage(),
                    'prioridad' => 'alta',
                    'visible' => true,
                ]);

                $this->asignarNotificacionAUsuarios($notificacion, $tipoError->id);
            }
        }
    }

    /**
     * HTML del correo de confirmación de compra
     */
    private function htmlCorreoCompra($pedido, $user): string
    {
        $nombre = $user->nombres;
        $appName = ConfiguracionHelper::getCompanyName();
        $year = date('Y');

        $itemsHtml = '';
        foreach ($pedido->items as $item) {
            $atributosHtml = '';
            if ($item->atributos && $item->atributos->isNotEmpty()) {
                $atributosHtml = '<div style="font-size: 12px; color: #777; margin-top: 4px;">';
                foreach ($item->atributos as $attr) {
                    $atributosHtml .= "{$attr->atributo_nombre}: {$attr->termino_nombre} | ";
                }
                $atributosHtml = rtrim($atributosHtml, ' | ') . '</div>';
            }

            $itemsHtml .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>
                    <div style='font-weight: 600;'>{$item->nombre_producto} x {$item->cantidad}</div>
                    {$atributosHtml}
                </td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right; vertical-align: top;'>
                    S/ " . number_format($item->subtotal_linea, 2) . "
                </td>
            </tr>";
        }

        $montosHtml = "
            <tr>
                <td style='padding: 5px 0;'>Subtotal</td>
                <td style='padding: 5px 0; text-align: right;'>S/ " . number_format($pedido->subtotal, 2) . "</td>
            </tr>
        ";

        if ($pedido->costo_envio > 0) {
            $montosHtml .= "
            <tr>
                <td style='padding: 5px 0;'>Envío</td>
                <td style='padding: 5px 0; text-align: right;'>S/ " . number_format($pedido->costo_envio, 2) . "</td>
            </tr>";
        }

        if ($pedido->cargo_metodo_pago > 0) {
            $montosHtml .= "
            <tr>
                <td style='padding: 5px 0;'>Cargo por método de pago</td>
                <td style='padding: 5px 0; text-align: right;'>S/ " . number_format($pedido->cargo_metodo_pago, 2) . "</td>
            </tr>";
        }

        if ($pedido->impuesto_cargo > 0) {
            $montosHtml .= "
            <tr>
                <td style='padding: 5px 0;'>Impuesto del cargo</td>
                <td style='padding: 5px 0; text-align: right;'>S/ " . number_format($pedido->impuesto_cargo, 2) . "</td>
            </tr>";
        }

        $totalFormateado = number_format($pedido->total, 2);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; margin: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
                .header { background: linear-gradient(135deg, #f4ab27, #e69c1f); color: #fff; padding: 32px 24px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; color: #333; }
                .content p { font-size: 15px; line-height: 1.6; margin: 0 0 15px; }
                .table-items { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
                .table-items th { text-align: left; padding: 10px; background: #f9f9f9; }
                .totals-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
                .totals-table td { border-bottom: 1px dashed #eee; padding: 8px 0; }
                .total-box { background: #fdfaf3; border: 2px dashed #f4ab27; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: right; font-size: 18px; font-weight: bold; color: #e69c1f; }
                .footer { background: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #999; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🛍️ ¡Gracias por tu compra!</h1>
                </div>
                <div class="content">
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Tu pedido <strong>{$pedido->codigo}</strong> ha sido registrado exitosamente. Aquí tienes el resumen:</p>
                    
                    <table class="table-items">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th style="text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                        </tbody>
                    </table>

                    <table class="totals-table">
                        {$montosHtml}
                    </table>

                    <div class="total-box">
                        Total Pagado: S/ {$totalFormateado}
                    </div>

                    <p style="margin-top: 20px;"><strong>Método de pago:</strong> {$pedido->metodo_pago_nombre}</p>
                    <p><strong>Tipo de entrega:</strong> {$pedido->tipo_entrega} </p>
                    
                </div>
                <div class="footer">
                    &copy; {$year} {$appName}. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Envía una notificación al usuario de que su compra fue registrada.
     */
    private function crearNotificacionCompra($pedido, $user)
    {
        $tipoCompras = TipoNotificacion::where('slug', 'compras')->first();
        
        if ($tipoCompras) {
            Notificacion::create([
                'tipo_notificacion_id' => $tipoCompras->id,
                'titulo' => '¡Compra registrada con éxito!',
                'mensaje' => "Tu pedido {$pedido->codigo} ha sido registrado por un total de S/ " . number_format($pedido->total, 2) . ". Pronto nos contactaremos contigo.",
                'mensaje_corto' => 'Pedido registrado',
                'url' => route('perfil.mis-compras'),
                'boton_texto' => 'Ver mi pedido',
                'prioridad' => 'alta',
                'usuario_id' => $user->id,
                'visible' => true,
            ]);
        }
    }

    /**
     * Enviar Correo de Stock Bajo a TODOS los usuarios que tengan permiso
     */
    private function enviarCorreoStockBajo($productos)
    {
        try {
            $tipoInv = TipoNotificacion::where('slug', 'inventario')->first();
            if (!$tipoInv) return;

            $usuariosNotificar = User::where('id_rol', '!=', 2)
                ->where(function ($query) use ($tipoInv) {
                    $query->whereHas('rol.permisosNotificaciones', function ($q) use ($tipoInv) {
                        $q->where('tipo_notificacion_id', $tipoInv->id)->where('puede_ver', true);
                    })
                    ->orWhereHas('excepcionesNotificaciones', function ($q) use ($tipoInv) {
                        $q->where('tipo_notificacion_id', $tipoInv->id)->where('tipo_excepcion', 'permitir');
                    });
                })
                ->whereDoesntHave('excepcionesNotificaciones', function ($q) use ($tipoInv) {
                    $q->where('tipo_notificacion_id', $tipoInv->id)->where('tipo_excepcion', 'denegar');
                })
                ->get();

            if ($usuariosNotificar->isEmpty()) return;

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

            $html = "<h2>⚠️ Alerta de Inventario</h2>";
            $html .= "<p>Se han detectado los siguientes productos con problemas de stock:</p><ul>";
            
            foreach ($productos as $prod) {
                $esAgotado = ($prod['estado'] === 'agotado');
                $color = $esAgotado ? '#dc3545' : '#ffc107'; 
                $tag = $esAgotado ? 'AGOTADO' : 'STOCK BAJO';
                
                $html .= "<li style='margin-bottom: 10px;'>"
                      . "<span style='background: {$color}; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-right: 5px;'>{$tag}</span>"
                      . "<b>{$prod['nombre']}</b> (SKU: {$prod['sku']}) - Stock actual: <strong>{$prod['stock_actual']}</strong> (Mínimo: {$prod['stock_minimo']})."
                      . "</li>";
            }
            
            $html .= "</ul><p>Por favor, ingresa al sistema para gestionar el reabastecimiento.</p>";

            foreach ($usuariosNotificar as $user) {
                Mail::mailer('dynamic_smtp')->send([], [], function ($message) use ($user, $fromEmail, $fromName, $html) {
                    $message->to($user->email, $user->nombres . ' ' . $user->apellidos)
                            ->from($fromEmail, $fromName)
                            ->subject('⚠️ Alerta de Inventario - Acción Requerida')
                            ->html($html);
                });
            }

        } catch (\Throwable $e) {
            \Log::error('Error al enviar correo de stock bajo: ' . $e->getMessage());
        }
    }

    /**
     * Enviar Correo de Nuevo Pedido a los usuarios administradores/encargados
     */
    private function enviarCorreoNuevaCompraAdmin($pedido)
    {
        try {
            $tipoCompras = TipoNotificacion::where('slug', 'compras')->first();
            if (!$tipoCompras) return;
            $usuariosNotificar = User::where('id_rol', '!=', 2)
                ->where(function ($query) use ($tipoCompras) {
                    $query->whereHas('rol.permisosNotificaciones', function ($q) use ($tipoCompras) {
                        $q->where('tipo_notificacion_id', $tipoCompras->id)->where('puede_ver', true);
                    })
                    ->orWhereHas('excepcionesNotificaciones', function ($q) use ($tipoCompras) {
                        $q->where('tipo_notificacion_id', $tipoCompras->id)->where('tipo_excepcion', 'permitir');
                    });
                })
                ->whereDoesntHave('excepcionesNotificaciones', function ($q) use ($tipoCompras) {
                    $q->where('tipo_notificacion_id', $tipoCompras->id)->where('tipo_excepcion', 'denegar');
                })
                ->get();

            if ($usuariosNotificar->isEmpty()) return;
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
            $html = "<h2>🛍️ Nuevo Pedido Recibido - {$pedido->codigo}</h2>";
            $html .= "<p><strong>Cliente:</strong> {$pedido->cliente_nombres} {$pedido->cliente_apellidos}</p>";
            $html .= "<p><strong>Email:</strong> {$pedido->cliente_email}</p>";
            $html .= "<p><strong>Total:</strong> S/ " . number_format($pedido->total, 2) . "</p>";
            $html .= "<p><strong>Método de Pago:</strong> {$pedido->metodo_pago_nombre}</p>";
            $html .= "<p><strong>Tipo de Entrega:</strong> " . ucfirst($pedido->tipo_entrega) . "</p>";
            
            if ($pedido->voucher_path) {
                $html .= "<p><strong>Comprobante:</strong> El cliente ha subido un voucher. Requiere revisión.</p>";
            }

            $html .= "<br><p>Por favor, ingresa al sistema para gestionar el pedido y actualizar su estado.</p>";
            
            foreach ($usuariosNotificar as $user) {
                Mail::mailer('dynamic_smtp')->send([], [], function ($message) use ($user, $fromEmail, $fromName, $html, $pedido) {
                    $message->to($user->email, $user->nombres . ' ' . $user->apellidos)
                            ->from($fromEmail, $fromName)
                            ->subject('🛍️ Nuevo Pedido: ' . $pedido->codigo)
                            ->html($html);
                });
            }

        } catch (\Throwable $e) {
            \Log::error('Error al enviar correo de nueva compra a admin: ' . $e->getMessage());
        }
    }

    /**
     * Asigna una notificación solo a usuarios con permiso (excluyendo clientes)
     */
    private function asignarNotificacionAUsuarios($notificacion, $tipoNotificacionId)
    {
        $usuariosNotificar = User::where('id_rol', '!=', 2)
            ->where(function ($query) use ($tipoNotificacionId) {
                $query->whereHas('rol.permisosNotificaciones', function ($q) use ($tipoNotificacionId) {
                    $q->where('tipo_notificacion_id', $tipoNotificacionId)->where('puede_ver', true);
                })
                ->orWhereHas('excepcionesNotificaciones', function ($q) use ($tipoNotificacionId) {
                    $q->where('tipo_notificacion_id', $tipoNotificacionId)->where('tipo_excepcion', 'permitir');
                });
            })
            ->whereDoesntHave('excepcionesNotificaciones', function ($q) use ($tipoNotificacionId) {
                $q->where('tipo_notificacion_id', $tipoNotificacionId)->where('tipo_excepcion', 'denegar');
            })
            ->get();

        foreach ($usuariosNotificar as $user) {
            $notificacion->usuarios()->syncWithoutDetaching([$user->id]);
        }
    }
}