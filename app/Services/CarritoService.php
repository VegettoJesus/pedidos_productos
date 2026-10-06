<?php

namespace App\Services;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Producto;
use App\Models\ProductoVariacion;
use App\Models\ReservaStock;
use Illuminate\Support\Facades\DB;

class CarritoService
{
    /**
     * Agregar producto simple, variación o hijo de agrupado
     */
    public function agregar(int $userId, array $data): array
    {
        $productoId = $data['producto_id'] ?? null;
        $variacionId = $data['variacion_id'] ?? null;
        $cantidad = max(1, (int) ($data['cantidad'] ?? 1));
        $padreId = $data['producto_padre_id'] ?? null;

        if (!$productoId) {
            return ['ok' => false, 'mensaje' => 'Producto no especificado'];
        }

        return DB::transaction(function () use ($userId, $productoId, $variacionId, $cantidad, $padreId) {

            $producto = Producto::find($productoId);
            if (!$producto || $producto->estado !== 'publicado') {
                return ['ok' => false, 'mensaje' => 'Producto no disponible'];
            }

            // 🔥 Validar vendido_individualmente
            $vendidoIndividual = $producto->vendido_individualmente;
            if ($variacionId) {
                $variacion = ProductoVariacion::where('producto_padre_id', $productoId)
                    ->where('id', $variacionId)
                    ->where('activo', true)
                    ->first();
                if (!$variacion) {
                    return ['ok' => false, 'mensaje' => 'Variación no disponible'];
                }
                $precio = $variacion->precio_rebajado > 0
                    ? $variacion->precio_rebajado
                    : $variacion->precio_regular;
                $stockDisponible = $variacion->gestion_inventario
                    ? $variacion->stock
                    : PHP_INT_MAX;
                $backorders = $variacion->backorders;
            } else {
                $precio = $producto->precio_rebajado > 0
                    ? $producto->precio_rebajado
                    : $producto->precio_regular;
                $stockDisponible = $producto->gestion_inventario
                    ? $producto->stock
                    : PHP_INT_MAX;
                $backorders = $producto->backorders;
            }

            if ($vendidoIndividual) {
                $cantidad = 1;
            }

            // Validar stock
            if ($stockDisponible < $cantidad && !$backorders) {
                return ['ok' => false, 'mensaje' => 'Stock insuficiente'];
            }

            $carrito = Carrito::obtenerOCrear($userId);

            // Buscar item existente
            $item = CarritoItem::where('carrito_id', $carrito->id)
                ->where('producto_id', $productoId)
                ->where('variacion_id', $variacionId)
                ->where('producto_padre_id', $padreId)
                ->first();

            if ($item) {
                $nuevaCantidad = $vendidoIndividual ? 1 : $item->cantidad + $cantidad;

                if ($stockDisponible < $nuevaCantidad && !$backorders) {
                    return ['ok' => false, 'mensaje' => 'Stock insuficiente'];
                }

                $diferencia = $nuevaCantidad - $item->cantidad;

                // Ajustar stock
                if ($diferencia > 0 && $stockDisponible !== PHP_INT_MAX) {
                    if ($variacionId) {
                        ProductoVariacion::where('id', $variacionId)
                            ->decrement('stock', $diferencia);
                    } else {
                        Producto::where('id', $productoId)
                            ->decrement('stock', $diferencia);
                    }

                    ReservaStock::create([
                        'carrito_item_id' => $item->id,
                        'producto_id' => $productoId,
                        'variacion_id' => $variacionId,
                        'cantidad' => $diferencia,
                    ]);
                }

                $item->cantidad = $nuevaCantidad;
                $item->precio_unitario = $precio;
                $item->save();
            } else {
                $item = CarritoItem::create([
                    'carrito_id' => $carrito->id,
                    'producto_id' => $productoId,
                    'variacion_id' => $variacionId,
                    'producto_padre_id' => $padreId,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                ]);

                if (!$item->descontarStock()) {
                    throw new \Exception('No se pudo reservar stock');
                }
            }

            $carrito->tocar();

            return [
                'ok' => true,
                'mensaje' => 'Producto agregado al carrito',
                'total_items' => $carrito->fresh()->total_items,
            ];
        });
    }

    /**
     * Actualizar cantidad
     */
    public function actualizarCantidad(int $userId, int $itemId, int $nuevaCantidad): array
    {
        return DB::transaction(function () use ($userId, $itemId, $nuevaCantidad) {
            $carrito = Carrito::where('user_id', $userId)->first();
            if (!$carrito) return ['ok' => false, 'mensaje' => 'Carrito no encontrado'];

            $item = CarritoItem::where('carrito_id', $carrito->id)->find($itemId);
            if (!$item) return ['ok' => false, 'mensaje' => 'Item no encontrado'];

            $producto = $item->producto;

            if ($producto && $producto->vendido_individualmente) {
                $nuevaCantidad = 1;
            }
            if ($nuevaCantidad < 1) $nuevaCantidad = 1;

            $diferencia = $nuevaCantidad - $item->cantidad;

            if ($diferencia === 0) {
                return ['ok' => true, 'mensaje' => 'Sin cambios'];
            }

            // Validar stock si aumenta
            if ($diferencia > 0) {
                if ($item->variacion_id && $item->variacion) {
                    if ($item->variacion->gestion_inventario
                        && $item->variacion->stock < $diferencia
                        && !$item->variacion->backorders) {
                        return ['ok' => false, 'mensaje' => 'Stock insuficiente'];
                    }
                    if ($item->variacion->gestion_inventario) {
                        $item->variacion->decrement('stock', $diferencia);
                    }
                } elseif ($item->producto) {
                    if ($item->producto->gestion_inventario
                        && $item->producto->stock < $diferencia
                        && !$item->producto->backorders) {
                        return ['ok' => false, 'mensaje' => 'Stock insuficiente'];
                    }
                    if ($item->producto->gestion_inventario) {
                        $item->producto->decrement('stock', $diferencia);
                    }
                }

                ReservaStock::create([
                    'carrito_item_id' => $item->id,
                    'producto_id' => $item->producto_id,
                    'variacion_id' => $item->variacion_id,
                    'cantidad' => $diferencia,
                ]);
            } else {
                // Devolver stock
                $devolver = abs($diferencia);
                if ($item->variacion_id && $item->variacion && $item->variacion->gestion_inventario) {
                    $item->variacion->increment('stock', $devolver);
                } elseif ($item->producto && $item->producto->gestion_inventario) {
                    $item->producto->increment('stock', $devolver);
                }

                ReservaStock::create([
                    'carrito_item_id' => $item->id,
                    'producto_id' => $item->producto_id,
                    'variacion_id' => $item->variacion_id,
                    'cantidad' => $devolver,
                    'liberada' => true,
                ]);
            }

            $item->cantidad = $nuevaCantidad;
            $item->save();
            $carrito->tocar();

            return ['ok' => true, 'mensaje' => 'Cantidad actualizada'];
        });
    }

    public function eliminar(int $userId, int $itemId): array
    {
        return DB::transaction(function () use ($userId, $itemId) {
            $carrito = Carrito::where('user_id', $userId)->first();
            if (!$carrito) return ['ok' => false];

            $item = CarritoItem::where('carrito_id', $carrito->id)->find($itemId);
            if (!$item) return ['ok' => false];

            $item->devolverStock();
            $item->delete();
            $carrito->tocar();

            return ['ok' => true, 'mensaje' => 'Producto eliminado'];
        });
    }

    public function vaciar(int $userId): array
    {
        $carrito = Carrito::where('user_id', $userId)->first();
        if (!$carrito) return ['ok' => true];

        $carrito->vaciar(true);
        return ['ok' => true, 'mensaje' => 'Carrito vaciado'];
    }

    /**
     * Limpiar carritos inactivos (llamar desde un Job/Scheduler)
     */
    public function limpiarInactivos(int $minutos = 120): int
    {
        $carritos = Carrito::where('ultima_actividad', '<', now()->subMinutes($minutos))->get();
        $contador = 0;

        foreach ($carritos as $carrito) {
            $carrito->vaciar(true);
            $carrito->delete();
            $contador++;
        }

        return $contador;
    }
}