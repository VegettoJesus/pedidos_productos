<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use HasFactory;

    protected $table = 'subcategorias';

    protected $fillable = ['nombre', 'icono', 'id_categoria'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }
}
