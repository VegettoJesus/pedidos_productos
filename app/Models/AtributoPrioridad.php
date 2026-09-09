<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtributoPrioridad extends Model
{
    protected $table = 'atributo_prioridad';

    protected $fillable = [
        'atributo_id',
        'producto_id',
        'prioridad'
    ];

    public function atributo()
    {
        return $this->belongsTo(Atributo::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}