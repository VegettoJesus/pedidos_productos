<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoEvidencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedido_evidencias';

    protected $fillable = [
        'pedido_id', 'tipo_evidencia',
        'archivo_path', 'nombre_original', 'mime_type', 'tamano_bytes',
        'estado_envio_momento', 'subido_por', 'capturada_en',
        'descripcion', 'visible_cliente'
    ];

    protected $casts = [
        'tamano_bytes'    => 'integer',
        'capturada_en'    => 'datetime',
        'visible_cliente' => 'boolean',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}