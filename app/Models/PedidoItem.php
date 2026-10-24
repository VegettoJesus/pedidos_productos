<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    use HasFactory;

    protected $table = 'pedido_items';

    protected $fillable = [
        'pedido_id', 'producto_id', 'variacion_id', 'producto_padre_id',
        'nombre_producto', 'sku', 'marca', 'tipo_item',
        'cantidad', 'precio_unitario_regular', 'precio_unitario_rebajado', 
        'precio_unitario_final', 'descuento_porcentaje', 'subtotal_linea',
        'peso', 'peso_unidad', 'longitud', 'anchura', 'altura',
        'imagen_path', 'estado_item'
    ];

    protected $casts = [
        'cantidad'                => 'integer',
        'precio_unitario_regular' => 'decimal:2',
        'precio_unitario_rebajado'=> 'decimal:2',
        'precio_unitario_final'   => 'decimal:2',
        'descuento_porcentaje'    => 'decimal:2',
        'subtotal_linea'          => 'decimal:2',
        'peso'                    => 'decimal:2',
        'longitud'                => 'decimal:2',
        'anchura'                 => 'decimal:2',
        'altura'                  => 'decimal:2',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
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

    public function atributos()
    {
        return $this->hasMany(PedidoItemAtributo::class);
    }
}