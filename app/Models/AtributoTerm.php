<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtributoTerm extends Model
{
    use HasFactory;

    protected $table = 'atributo_terminos';

    protected $fillable = ['atributo_id', 'nombre', 'slug', 'descripcion'];

    public function atributo()
    {
        return $this->belongsTo(Atributo::class);
    }

    public function productoAtributos()
    {
        return $this->belongsToMany(ProductoAtributo::class, 'producto_atributo_valores', 
            'termino_id', 'producto_atributo_id');
    }

    public function variaciones()
    {
        return $this->belongsToMany(
            ProductoVariacion::class,
            'variacion_atributo_terminos',
            'atributo_termino_id',
            'variacion_id'
        );
    }
}