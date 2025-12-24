<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoAtributo extends Model
{
    use HasFactory;

    protected $table = 'producto_atributo';

    protected $fillable = ['producto_id', 'atributo_id', 'visible', 'variacion'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function atributo()
    {
        return $this->belongsTo(Atributo::class);
    }

    public function valores()
    {
        return $this->belongsToMany(AtributoTerm::class, 'producto_atributo_valores', 
            'producto_atributo_id', 'termino_id');
    }
}