<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoEstadoHistorial extends Model
{
    use HasFactory;

    protected $table = 'pedido_estados_historial';

    protected $fillable = [
        'pedido_id', 'user_id',
        'tipo_cambio', 'estado_anterior', 'estado_nuevo',
        'motivo', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}