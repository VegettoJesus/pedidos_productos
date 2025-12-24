<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etiqueta extends Model
{
    use HasFactory;

    protected $table = 'etiquetas';

    protected $fillable = ['nombre', 'slug', 'color'];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_etiqueta');
    }
}