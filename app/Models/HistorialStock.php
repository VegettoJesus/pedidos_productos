<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialStock extends Model
{
    use HasFactory;

    protected $table = 'historial_stock';

    protected $fillable = [
        'producto_id',
        'variacion_id',
        'cantidad_anterior',
        'nueva_cantidad',
        'diferencia',
        'motivo',
        'usuario_id',
    ];

    protected $casts = [
        'cantidad_anterior' => 'integer',
        'nueva_cantidad' => 'integer',
        'diferencia' => 'integer',
        'created_at' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function variacion()
    {
        return $this->belongsTo(ProductoVariacion::class, 'variacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeForProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopeForVariacion($query, $variacionId)
    {
        return $query->where('variacion_id', $variacionId);
    }

    public static function registrarMovimiento($producto, $cantidadAnterior, $nuevaCantidad, $motivo = null, $usuarioId = null, $variacionId = null)
    {
        return self::create([
            'producto_id' => $producto->id,
            'variacion_id' => $variacionId,
            'cantidad_anterior' => $cantidadAnterior,
            'nueva_cantidad' => $nuevaCantidad,
            'diferencia' => $nuevaCantidad - $cantidadAnterior,
            'motivo' => $motivo,
            'usuario_id' => $usuarioId ?? auth()->id(),
        ]);
    }
}