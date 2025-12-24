<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'id_usuario', 'nombre', 'descripcion', 'tipo_producto', 'estado',
        'id_subCategorias', 'precio_regular', 'precio_rebajado', 'fecha_inicio_rebaja',
        'fecha_fin_rebaja', 'gestion_inventario', 'estado_inventario', 'stock',
        'stock_minimo', 'max_stock', 'vendido_individualmente', 'backorders',
        'sku', 'marca', 'peso', 'peso_unidad', 'longitud', 'anchura', 'altura',
        'permite_valoraciones', 'nota_interna', 'imagen_miniatura', 
    ];

    protected $casts = [
        'gestion_inventario' => 'boolean',
        'vendido_individualmente' => 'boolean',
        'backorders' => 'boolean',
        'permite_valoraciones' => 'boolean',
        'precio_regular' => 'decimal:2',
        'precio_rebajado' => 'decimal:2',
        'peso' => 'decimal:2',
        'longitud' => 'decimal:2',
        'anchura' => 'decimal:2',
        'altura' => 'decimal:2',
        'fecha_inicio_rebaja' => 'date',
        'fecha_fin_rebaja' => 'date',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'id_subCategorias');
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id');
    }

    public function etiquetas()
    {
        return $this->belongsToMany(Etiqueta::class, 'producto_etiqueta');
    }

    public function atributos()
    {
        return $this->belongsToMany(Atributo::class, 'producto_atributo')
                    ->withPivot('visible', 'variacion')
                    ->withTimestamps();
    }

    public function valoresAtributos()
    {
        return $this->hasManyThrough(
            AtributoTerm::class,
            ProductoAtributo::class,
            'producto_id', // FK en producto_atributo
            'id', // FK en atributo_terminos
            'id', // Local key en productos
            'atributo_id' // Local key en producto_atributo
        );
    }

    public function variaciones()
    {
        return $this->hasMany(ProductoVariacion::class, 'producto_padre_id');
    }

    public function productosAgrupados()
    {
        return $this->hasMany(ProductoAgrupado::class, 'producto_padre_id');
    }

    public function productosRelacionados()
    {
        return $this->belongsToMany(Producto::class, 'productos_relacionados', 
            'producto_id', 'producto_relacionado_id')
            ->withTimestamps();
    }

    public function valoraciones()
    {
        return $this->hasMany(ProductoValoracion::class);
    }

    // Scopes
    public function scopePublicados($query)
    {
        return $query->where('estado', 'publicado');
    }

    public function scopeSimples($query)
    {
        return $query->where('tipo_producto', 'simple');
    }

    public function scopeVariables($query)
    {
        return $query->where('tipo_producto', 'variable');
    }
}