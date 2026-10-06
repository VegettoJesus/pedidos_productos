<?php
// app/Models/CarritoItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CarritoItem extends Model
{
    protected $table = 'carrito_items';

    protected $fillable = [
        'carrito_id', 'producto_id', 'variacion_id',
        'producto_padre_id', 'cantidad', 'precio_unitario'
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'cantidad' => 'integer',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    public function carrito()
    {
        return $this->belongsTo(Carrito::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function variacion()
    {
        return $this->belongsTo(ProductoVariacion::class, 'variacion_id');
    }

    public function productoPadre()
    {
        return $this->belongsTo(Producto::class, 'producto_padre_id');
    }

    // ============================================
    // ACCESORES
    // ============================================

    public function getSubtotalAttribute(): float
    {
        return $this->cantidad * $this->precio_unitario;
    }

    /**
     * Precio real actual (sin depender del snapshot)
     */
    public function getPrecioActualAttribute(): float
    {
        if ($this->variacion_id && $this->variacion) {
            return $this->variacion->precio_rebajado > 0
                ? $this->variacion->precio_rebajado
                : $this->variacion->precio_regular;
        }

        if ($this->producto) {
            return $this->producto->precio_rebajado > 0
                ? $this->producto->precio_rebajado
                : $this->producto->precio_regular;
        }

        return (float) $this->precio_unitario;
    }

    // ============================================
    // RESERVA / DEVOLUCIÓN DE STOCK
    // ============================================

    /**
     * Descuenta stock al agregar al carrito
     */
    public function descontarStock(): bool
    {
        return DB::transaction(function () {
            if ($this->variacion_id) {
                $variacion = ProductoVariacion::lockForUpdate()->find($this->variacion_id);
                if (!$variacion) return false;

                if ($variacion->gestion_inventario) {
                    if ($variacion->stock < $this->cantidad) {
                        return false;
                    }
                    $variacion->decrement('stock', $this->cantidad);
                }
            } elseif ($this->producto_id) {
                $producto = Producto::lockForUpdate()->find($this->producto_id);
                if (!$producto) return false;

                if ($producto->gestion_inventario && !$producto->backorders) {
                    if ($producto->stock < $this->cantidad) {
                        return false;
                    }
                    $producto->decrement('stock', $this->cantidad);
                }
            }

            // Registrar reserva
            ReservaStock::create([
                'carrito_item_id' => $this->id,
                'producto_id' => $this->producto_id,
                'variacion_id' => $this->variacion_id,
                'cantidad' => $this->cantidad,
            ]);

            return true;
        });
    }

    /**
     * Devuelve stock al eliminar/vaciar el carrito
     */
    public function devolverStock(): void
    {
        DB::transaction(function () {
            if ($this->variacion_id) {
                $variacion = ProductoVariacion::lockForUpdate()->find($this->variacion_id);
                if ($variacion && $variacion->gestion_inventario) {
                    $variacion->increment('stock', $this->cantidad);
                }
            } elseif ($this->producto_id) {
                $producto = Producto::lockForUpdate()->find($this->producto_id);
                if ($producto && $producto->gestion_inventario && !$producto->backorders) {
                    $producto->increment('stock', $this->cantidad);
                }
            }

            // Marcar reservas como liberadas
            ReservaStock::where('carrito_item_id', $this->id)
                ->where('liberada', false)
                ->update(['liberada' => true]);
        });
    }
}