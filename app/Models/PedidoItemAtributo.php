<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoItemAtributo extends Model
{
    use HasFactory;

    protected $table = 'pedido_item_atributos';

    protected $fillable = [
        'pedido_item_id',
        'atributo_id', 'atributo_termino_id',
        'atributo_nombre', 'termino_nombre', 'valor_extra'
    ];

    public function item()
    {
        return $this->belongsTo(PedidoItem::class, 'pedido_item_id');
    }

    public function atributo()
    {
        return $this->belongsTo(Atributo::class);
    }

    public function termino()
    {
        return $this->belongsTo(AtributoTerm::class, 'atributo_termino_id');
    }
}