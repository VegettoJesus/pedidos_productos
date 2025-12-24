<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoValoracion extends Model
{
    use HasFactory;

    protected $table = 'producto_valoraciones';

    protected $fillable = ['producto_id', 'user_id', 'puntuacion', 'comentario', 'aprobado'];

    protected $casts = [
        'aprobado' => 'boolean',
        'puntuacion' => 'integer'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}