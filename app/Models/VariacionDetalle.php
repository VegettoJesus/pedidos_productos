<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariacionDetalle extends Model
{
    protected $fillable = [
        'variacion_id',
        'sku',
        'precio_regular',
        'precio_rebajado',
        'stock',
        'gestion_inventario',
        'estado_inventario',
        'backorders',
        'fecha_inicio_rebaja',
        'fecha_fin_rebaja',
        'variacion_peso',
        'variacion_peso_unidad',
        'variacion_longitud',
        'variacion_anchura',
        'variacion_altura',
        'variacion_descripcion',
        'precio_final',
        'descuento_porcentaje',
        'estado_actual',
        'atributos_variacion',
        'terminos_coincidentes',
        'atributos_coincidentes',
        'total_atributos_variacion',
        'terminos_contradictorios',
        'prioridad',
        'img1',
        'img2',
        'img3',
        'img4',
        'img5',
        'img6'
    ];
}