<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoEtiqueta extends Model
{
    use HasFactory;

    protected $table = 'producto_etiqueta';

    protected $fillable = [
        'producto_id',
        'etiqueta_id'
    ];

    public $timestamps = true;

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación con Etiqueta
    public function etiqueta()
    {
        return $this->belongsTo(Etiqueta::class, 'etiqueta_id');
    }
}