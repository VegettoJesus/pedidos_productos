<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoAgrupado extends Model
{
    use HasFactory;

    protected $table = 'producto_agrupado';

    protected $fillable = ['producto_padre_id', 'producto_hijo_id'];

    public function productoPadre()
    {
        return $this->belongsTo(Producto::class, 'producto_padre_id');
    }

    public function productoHijo()
    {
        return $this->belongsTo(Producto::class, 'producto_hijo_id');
    }
}