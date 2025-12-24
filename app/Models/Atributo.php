<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atributo extends Model
{
    use HasFactory;

    protected $table = 'atributos';

    protected $fillable = ['nombre', 'slug'];

    public function terminos()
    {
        return $this->hasMany(AtributoTerm::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_atributo')
                    ->withPivot('visible', 'variacion')
                    ->withTimestamps();
    }
}