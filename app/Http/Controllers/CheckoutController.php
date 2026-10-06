<?php
// app/Http/Controllers/CheckoutController.php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\MetodoPago;
use App\Models\Departamento;
use App\Models\ReservaStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $usuarioDato = \App\Models\UsuarioDato::where('id_usuario', $user->id)->first();

        // 🔥 2. Verificar cadena activa: depto -> provincia -> distrito
        $ubicacionValida = false;
        $ubicacionCliente = [
            'departamento_id' => null,
            'provincia_id' => null,
            'distrito_id' => null,
            'direccion_completa' => '',
        ];

        if ($usuarioDato && $usuarioDato->departamento && $usuarioDato->provincia && $usuarioDato->distrito) {
            $depto = \App\Models\Departamento::where('id', $usuarioDato->departamento)->where('activo', true)->first();
            $prov = \App\Models\Provincia::where('id', $usuarioDato->provincia)
                ->where('departamento_id', $usuarioDato->departamento)
                ->where('activo', true)
                ->first();
            $dist = \App\Models\Distrito::where('id', $usuarioDato->distrito)
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
            $distrito = \App\Models\Distrito::find($ubicacionCliente['distrito_id']);
            $envioBase = $distrito?->costo_envio ?? 0;
        }

        $impuestos = 0;

        $metodoPagoService = app(\App\Services\MetodoPagoService::class);
        $metodosEvaluados = $metodoPagoService->evaluar($carrito->items, $impuestos, $envioBase);

        $departamentos = Departamento::where('activo', true)->get();
        $empresa = \App\Models\EmpresaInformacion::first();
        $config = \App\Models\ConfiguracionSistema::first();

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
            'metodosEvaluados' => $metodosEvaluados,   // 🔥 CAMBIO
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
        if (!$this->cliente()) {
            return response()->json(['ok' => false, 'mensaje' => 'No autorizado'], 401);
        }

        $request->validate([
            'tipo_entrega' => 'required|in:delivery,retiro',
            'tipo_pago' => 'required|string',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'provincia_id' => 'nullable|exists:provincias,id',
            'id_distrito' => 'nullable|exists:distritos,id',
            'direccion_entrega' => 'nullable|string|max:350',
            'punto_retiro' => 'nullable|string|max:350',
            'imagen' => 'nullable|image|max:4096',
        ]);

        $carrito = Carrito::where('user_id', Auth::id())
            ->with(['items.producto', 'items.variacion'])
            ->first();

        if (!$carrito || $carrito->items->isEmpty()) {
            return response()->json(['ok' => false, 'mensaje' => 'Carrito vacío'], 400);
        }

        return DB::transaction(function () use ($carrito, $request) {

            // Validar stock
            foreach ($carrito->items as $item) {
                if ($item->variacion_id && $item->variacion) {
                    if ($item->variacion->gestion_inventario
                        && $item->variacion->stock < $item->cantidad
                        && !$item->variacion->backorders) {
                        return response()->json([
                            'ok' => false,
                            'mensaje' => "Stock insuficiente para {$item->variacion->sku}"
                        ], 400);
                    }
                } elseif ($item->producto) {
                    if ($item->producto->gestion_inventario
                        && $item->producto->stock < $item->cantidad
                        && !$item->producto->backorders) {
                        return response()->json([
                            'ok' => false,
                            'mensaje' => "Stock insuficiente para {$item->producto->nombre}"
                        ], 400);
                    }
                }
            }

            // Subtotal
            $subtotal = 0;
            foreach ($carrito->items as $item) {
                $subtotal += $item->cantidad * $item->precio_unitario;
            }

            // 🔥 COSTO DE ENVÍO (misma lógica que el JS)
            $costoEnvio = 0;

            if ($request->tipo_entrega === 'delivery' && $request->id_distrito) {
                $distrito = \App\Models\Distrito::find($request->id_distrito);
                $costoBase = $distrito?->costo_envio ?? 0;

                // Verificar método de pago
                $metodo = MetodoPago::where('slug', $request->tipo_pago)->first();
                $config = $metodo?->configuracion ?? [];

                $aplicaEnvio = true;

                // Regla: contra_entrega solo cobra envío si incluir_en_total.envio == true
                if ($request->tipo_pago === 'contra_entrega') {
                    $cargo = $config['cargo_adicional'] ?? [];
                    $incluir = $cargo['incluir_en_total'] ?? [];
                    $aplicaEnvio = ($incluir['envio'] ?? false) === true;
                }

                $costoEnvio = $aplicaEnvio ? $costoBase : 0;
            }

            // 🔥 RECOJO EN TIENDA: sin envío
            if ($request->tipo_entrega === 'retiro') {
                $costoEnvio = 0;
            }

            $total = $subtotal + $costoEnvio;

            // Subir imagen
            $imagenPath = null;
            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                $nombre = 'voucher_' . time() . '.' . $file->getClientOriginalExtension();
                $carpeta = public_path('vouchers');
                if (!file_exists($carpeta)) mkdir($carpeta, 0755, true);
                $file->move($carpeta, $nombre);
                $imagenPath = $nombre;
            }

            // 🔥 Punto de retiro: siempre desde la empresa
            $puntoRetiro = null;
            if ($request->tipo_entrega === 'retiro') {
                $empresa = \App\Models\EmpresaInformacion::first();
                $puntoRetiro = $empresa?->direccion ?? 'Tienda principal';
            }

            // Crear pedido
            $pedido = Pedido::create([
                'id_usuario' => Auth::id(),
                'estado' => 'Pendiente',
                'tipo_entrega' => $request->tipo_entrega,
                'departamento_id' => $request->tipo_entrega === 'delivery' ? $request->departamento_id : null,
                'provincia_id' => $request->tipo_entrega === 'delivery' ? $request->provincia_id : null,
                'id_distrito' => $request->tipo_entrega === 'delivery' ? $request->id_distrito : null,
                'direccion_entrega' => $request->tipo_entrega === 'delivery' ? $request->direccion_entrega : null,
                'punto_retiro' => $puntoRetiro,
                'costo_envio' => $costoEnvio,
                'total' => $total,
                'tipo_pago' => $request->tipo_pago,
                'imagen' => $imagenPath,
            ]);

            // Detalles
            foreach ($carrito->items as $item) {
                DetallePedido::create([
                    'id_pedido' => $pedido->id,
                    'id_producto' => $item->producto_id,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio_unitario,
                    'total' => $item->cantidad * $item->precio_unitario,
                ]);

                ReservaStock::where('carrito_item_id', $item->id)
                    ->where('liberada', false)
                    ->update(['liberada' => true]);
            }

            $carrito->items()->delete();
            $carrito->tocar();

            return response()->json([
                'ok' => true,
                'pedido_id' => $pedido->id,
                'mensaje' => 'Pedido creado correctamente',
                'redirect' => route('pedido.gracias', $pedido->id),
            ]);
        });
    }
}