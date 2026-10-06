<?php

namespace App\Http\Controllers;

use App\Services\CarritoService;
use App\Models\Carrito;
use App\Models\Producto;
use App\Models\ProductoRelacionado;
use App\Http\Controllers\TiendaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarritoController extends Controller
{
    protected CarritoService $service;

    public function __construct(CarritoService $service)
    {
        $this->service = $service;
    }

    public function vista()
    {
        if (!Auth::check() || Auth::user()->id_rol !== 2) {
            return redirect()->route('tienda.home')
                ->with('warning', 'Debes iniciar sesión para ver tu carrito');
        }

        $base = [
            'titulo_site' => \App\Models\ConfiguracionSistema::first()?->titulo_site,
            'descripcion_corta' => \App\Models\ConfiguracionSistema::first()?->descripcion_corta,
            'authUser' => [
                'nombres' => Auth::user()->nombres,
                'apellidos' => Auth::user()->apellidos,
                'email' => Auth::user()->email,
                'foto' => asset('img/user.png'),
            ],
        ];

        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.carrito',
            'script' => 'js/carrito-vista.js',
        ]));
    }

    /**
     * Middleware: solo clientes logueados
     */
    private function verificarCliente()
    {
        if (!Auth::check() || Auth::user()->id_rol !== 2) {
            return response()->json([
                'ok' => false,
                'requiere_login' => true,
                'mensaje' => 'Debes iniciar sesión para usar el carrito',
            ], 401);
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->verificarCliente()) return $r;

        $carrito = Carrito::firstOrCreate(['user_id' => Auth::id()]);

        $carrito->load([
            'items.producto.imagenes',
            'items.producto',
            'items.variacion.imagenes',
            'items.variacion.atributos',          
            'items.productoPadre',
        ]);

        return response()->json([
            'ok' => true,
            'items' => $carrito->items,
            'total' => $carrito->total,
            'total_items' => $carrito->total_items,
        ]);
    }

    public function crossSellsHtml()
    {
        if ($r = $this->verificarCliente()) return $r;

        $carrito = Carrito::firstOrCreate(['user_id' => Auth::id()]);

        $idsEnCarrito = $carrito->items()
            ->pluck('producto_id')
            ->unique()
            ->filter()
            ->values();

        if ($idsEnCarrito->isEmpty()) {
            return response()->json(['ok' => true, 'html' => '', 'count' => 0]);
        }

        $relacionadosIds = ProductoRelacionado::whereIn('producto_id', $idsEnCarrito)
            ->where('tipo', 'crosssell')
            ->pluck('producto_relacionado_id')
            ->unique()
            ->diff($idsEnCarrito->all()) 
            ->values();

        if ($relacionadosIds->isEmpty()) {
            return response()->json(['ok' => true, 'html' => '', 'count' => 0]);
        }

        $crossSells = Producto::with([
            'imagenes',
            'variaciones' => function ($q) {
                $q->where('activo', true)->with(['atributos.atributo', 'imagenes']);
            },
            'valoraciones' => function ($q) {
                $q->where('aprobado', true)->with('usuario');
            },
        ])
        ->whereIn('id', $relacionadosIds)
        ->where('estado', 'publicado')
        ->get();

        $tiendaCtrl = app(TiendaController::class);
        $crossSells = $tiendaCtrl->procesarVariacionesTarjeta($crossSells);

        $html = '';
        foreach ($crossSells as $producto) {
            $html .= view('tienda.partials.product-card-enhanced', [
                'producto' => $producto,
            ])->render();
        }

        return response()->json([
            'ok' => true,
            'html' => $html,
            'count' => $crossSells->count(),
        ]);
    }

    public function agregar(Request $request)
    {
        if ($r = $this->verificarCliente()) return $r;

        $data = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'variacion_id' => 'nullable|exists:producto_variaciones,id',
            'producto_padre_id' => 'nullable|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $result = $this->service->agregar(Auth::id(), $data);

        return response()->json($result, $result['ok'] ? 200 : 400);
    }

    public function actualizar(Request $request, $itemId)
    {
        if ($r = $this->verificarCliente()) return $r;

        $request->validate(['cantidad' => 'required|integer|min:1']);
        $result = $this->service->actualizarCantidad(Auth::id(), (int) $itemId, (int) $request->cantidad);

        return response()->json($result, $result['ok'] ? 200 : 400);
    }

    public function eliminar($itemId)
    {
        if ($r = $this->verificarCliente()) return $r;

        $carrito = Carrito::where('user_id', Auth::id())->first();
        if (!$carrito) {
            return response()->json(['ok' => false, 'mensaje' => 'Carrito no encontrado']);
        }

        $item = \App\Models\CarritoItem::where('carrito_id', $carrito->id)->find($itemId);
        if (!$item) {
            return response()->json(['ok' => false, 'mensaje' => 'Item no encontrado']);
        }

        $itemData = [
            'producto_id'      => $item->producto_id,
            'variacion_id'     => $item->variacion_id,
            'producto_padre_id'=> $item->producto_padre_id,
            'cantidad'         => $item->cantidad,
        ];

        $result = $this->service->eliminar(Auth::id(), (int) $itemId);

        if ($result['ok']) {
            $result['item_eliminado'] = $itemData;
        }

        return response()->json($result);
    }

    public function vaciar()
    {
        if ($r = $this->verificarCliente()) return $r;

        $result = $this->service->vaciar(Auth::id());
        return response()->json($result);
    }

    public function count()
    {
        if (!Auth::check() || Auth::user()->id_rol !== 2) {
            return response()->json(['count' => 0, 'logueado' => false]);
        }

        $carrito = Carrito::where('user_id', Auth::id())->first();
        return response()->json([
            'count' => $carrito ? $carrito->total_items : 0,
            'logueado' => true,
        ]);
    }
}