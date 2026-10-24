<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedidos';

    protected $fillable = [
        'codigo', 'numero_orden',
        'user_id', 'cliente_nombres', 'cliente_apellidos', 'cliente_email', 
        'cliente_celular', 'cliente_documento_tipo', 'cliente_documento_numero',
        'tipo_entrega', 'punto_retiro',
        'departamento_id', 'provincia_id', 'distrito_id',
        'departamento_nombre', 'provincia_nombre', 'distrito_nombre',
        'direccion_entrega', 'referencia_entrega',
        'metodo_pago_id', 'metodo_pago_slug', 'metodo_pago_nombre', 'metodo_pago_config_snapshot',
        'voucher_path', 'voucher_subido_en',
        'subtotal', 'costo_envio', 'impuesto_envio', 'cargo_metodo_pago', 
        'impuesto_cargo', 'descuento_total', 'total', 'moneda',
        'estado', 'estado_pago', 'pagado_en',
        'estado_envio', 'enviado_en', 'entregado_en',
        'fecha_pedido', 'fecha_confirmacion', 'fecha_cancelacion',
        'nota_cliente', 'nota_admin', 'motivo_cancelacion', 'cancelado_por',
        'ip_cliente', 'user_agent'
    ];

    protected $casts = [
        'metodo_pago_config_snapshot' => 'array',
        'subtotal'            => 'decimal:2',
        'costo_envio'         => 'decimal:2',
        'impuesto_envio'      => 'decimal:2',
        'cargo_metodo_pago'   => 'decimal:2',
        'impuesto_cargo'      => 'decimal:2',
        'descuento_total'     => 'decimal:2',
        'total'               => 'decimal:2',
        'pagado_en'           => 'datetime',
        'enviado_en'          => 'datetime',
        'entregado_en'        => 'datetime',
        'fecha_pedido'        => 'datetime',
        'fecha_confirmacion'  => 'datetime',
        'fecha_cancelacion'   => 'datetime',
        'voucher_subido_en'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function canceladoPor()
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function items()
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function historial()
    {
        return $this->hasMany(PedidoEstadoHistorial::class)->orderBy('created_at', 'desc');
    }

    public function evidencias()
    {
        return $this->hasMany(PedidoEvidencia::class);
    }

    public function reembolsos()
    {
        return $this->hasMany(PedidoReembolso::class)->orderBy('created_at', 'desc');
    }

    /**
     * Nota visible para el cliente
     */
    public function getNotaVisibleClienteAttribute()
    {
        return $this->nota_cliente; 
    }

    /**
     * Nota interna 
     */
    public function getNotaInternaAttribute()
    {
        return $this->nota_admin;
    }
}