<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaStock extends Model
{
    protected $table = 'reservas_stock';

    protected $fillable = [
        'carrito_item_id', 'producto_id', 'variacion_id',
        'cantidad', 'liberada'
    ];

    protected $casts = ['liberada' => 'boolean'];

    public function item()
    {
        return $this->belongsTo(CarritoItem::class, 'carrito_item_id');
    }
}