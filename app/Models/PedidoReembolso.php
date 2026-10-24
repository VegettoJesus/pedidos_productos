<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoReembolso extends Model
{
    use HasFactory;

    protected $table = 'pedido_reembolsos';

    protected $fillable = [
        'pedido_id', 'user_id',
        'monto_productos', 'monto_envio', 'monto_total',
        'items_reembolsados', 'motivo', 'tipo'
    ];

    protected $casts = [
        'items_reembolsados' => 'array',
        'monto_productos' => 'decimal:2',
        'monto_envio' => 'decimal:2',
        'monto_total' => 'decimal:2',
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