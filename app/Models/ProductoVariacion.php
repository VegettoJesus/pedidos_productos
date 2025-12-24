<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoVariacion extends Model
{
    use HasFactory;

    protected $table = 'producto_variaciones';

    protected $fillable = [
        'producto_padre_id', 'nombre', 'descripcion', 'gestion_inventario',
        'estado_inventario', 'backorders', 'fecha_inicio_rebaja', 'fecha_fin_rebaja',
        'peso_unidad', 'sku', 'precio_regular', 'precio_rebajado', 'stock',
        'peso', 'longitud', 'anchura', 'altura', 'activo'
    ];

    protected $casts = [
        'gestion_inventario' => 'boolean',
        'backorders' => 'boolean',
        'activo' => 'boolean',
        'precio_regular' => 'decimal:2',
        'precio_rebajado' => 'decimal:2',
        'peso' => 'decimal:2',
        'longitud' => 'decimal:2',
        'anchura' => 'decimal:2',
        'altura' => 'decimal:2',
        'fecha_inicio_rebaja' => 'date',
        'fecha_fin_rebaja' => 'date',
    ];

    public function productoPadre()
    {
        return $this->belongsTo(Producto::class, 'producto_padre_id');
    }

    public function atributos()
    {
        return $this->belongsToMany(
            AtributoTerm::class,
            'variacion_atributo_terminos',
            'variacion_id',         // FK hacia producto_variaciones
            'atributo_termino_id'   // FK hacia atributo_terminos
        );
    }

    public function imagenes()
    {
        return $this->hasMany(VariacionImagen::class, 'variacion_id');
    }
}