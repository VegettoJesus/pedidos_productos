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
        'id_usuario', 'nombre', 'descripcion', 'descripcion_completa' , 'tipo_producto', 'estado',
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

    /**
     * Obtiene los productos hijos de un producto agrupado
     */
    public function productosHijos()
    {
        return $this->belongsToMany(
            Producto::class,
            'producto_agrupado',
            'producto_padre_id',
            'producto_hijo_id'
        )->withTimestamps();
    }

    public function productosRelacionados()
    {
        return $this->belongsToMany(Producto::class, 'productos_relacionados', 
            'producto_id', 'producto_relacionado_id')
            ->withPivot('tipo')
            ->withTimestamps();
    }

    public function valoraciones()
    {
        return $this->hasMany(ProductoValoracion::class);
    }

    public function getRatingAttribute()
    {
        $avg = $this->valoraciones()->where('aprobado', true)->avg('puntuacion');
        return $avg ? round($avg, 1) : 0;
    }

    public function getRatingCountAttribute()
    {
        return $this->valoraciones()->where('aprobado', true)->count();
    }

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

    public function scopeAgrupados($query)
    {
        return $query->where('tipo_producto', 'agrupado');
    }

    /**
     * Verifica si un producto tiene rebaja activa
     */
    private function tieneRebajaActiva($producto)
    {
        return !is_null($producto->precio_rebajado) && 
               $producto->precio_rebajado > 0 &&
               (is_null($producto->fecha_fin_rebaja) || $producto->fecha_fin_rebaja >= now());
    }

    /**
     * Obtiene el precio actual de un producto (puede ser simple o variable)
     * Retorna un objeto con min, max, tiene_rebaja
     */
    private function getPrecioProducto($producto)
    {
        if ($producto->tipo_producto === 'variable') {
            // Para productos variables, obtenemos su rango de precios
            $rango = $producto->rango_precios;
            return (object) [
                'min' => $rango->precio_actual_min,
                'max' => $rango->precio_actual_max,
                'tiene_rebaja' => $rango->tiene_rebaja,
                'precio_regular_min' => $rango->precio_regular_min,
                'precio_regular_max' => $rango->precio_regular_max,
                'precio_rebajado_min' => $rango->precio_rebajado_min,
                'precio_rebajado_max' => $rango->precio_rebajado_max,
            ];
        } else {
            // Producto simple
            $tieneRebaja = $this->tieneRebajaActiva($producto);
            
            $precioActual = $tieneRebaja ? $producto->precio_rebajado : $producto->precio_regular;
            
            return (object) [
                'min' => $precioActual,
                'max' => $precioActual,
                'tiene_rebaja' => $tieneRebaja,
                'precio_regular_min' => $tieneRebaja ? $producto->precio_regular : null,
                'precio_regular_max' => $tieneRebaja ? $producto->precio_regular : null,
                'precio_rebajado_min' => $tieneRebaja ? $producto->precio_rebajado : null,
                'precio_rebajado_max' => $tieneRebaja ? $producto->precio_rebajado : null,
            ];
        }
    }

    /**
     * Obtiene el rango de precios para productos variables o agrupados
     */
    public function getRangoPreciosAttribute()
    {
        // Productos agrupados
        if ($this->tipo_producto === 'agrupado') {
            $productosHijos = $this->productosHijos()
                ->where('estado', 'publicado')
                ->get();
            
            if ($productosHijos->isEmpty()) {
                return (object) [
                    'precio_actual_min' => 0,
                    'precio_actual_max' => 0,
                    'precio_regular_min' => null,
                    'precio_regular_max' => null,
                    'precio_rebajado_min' => null,
                    'precio_rebajado_max' => null,
                    'tiene_rebaja' => false,
                    'es_agrupado' => true
                ];
            }
            
            $preciosActualesMin = [];
            $preciosActualesMax = [];
            $preciosRegularesMin = [];
            $preciosRegularesMax = [];
            $tieneAlgunaRebaja = false;
            
            foreach ($productosHijos as $hijo) {
                $precioInfo = $this->getPrecioProducto($hijo);
                
                // Precios actuales (lo que paga el cliente)
                if ($precioInfo->min > 0) {
                    $preciosActualesMin[] = $precioInfo->min;
                    $preciosActualesMax[] = $precioInfo->max;
                }
                
                // SOLO guardamos precios regulares para productos que TIENEN rebaja activa
                if ($precioInfo->tiene_rebaja && $precioInfo->precio_regular_min > 0) {
                    $tieneAlgunaRebaja = true;
                    $preciosRegularesMin[] = $precioInfo->precio_regular_min;
                    $preciosRegularesMax[] = $precioInfo->precio_regular_max;
                }
            }
            
            return (object) [
                'precio_actual_min' => !empty($preciosActualesMin) ? min($preciosActualesMin) : 0,
                'precio_actual_max' => !empty($preciosActualesMax) ? max($preciosActualesMax) : 0,
                'precio_regular_min' => !empty($preciosRegularesMin) ? min($preciosRegularesMin) : null,
                'precio_regular_max' => !empty($preciosRegularesMax) ? max($preciosRegularesMax) : null,
                'tiene_rebaja' => $tieneAlgunaRebaja,
                'es_agrupado' => true
            ];
        }
        
        // Productos variables
        if ($this->tipo_producto === 'variable') {
            $variaciones = $this->variaciones()
                ->where('activo', true)
                ->get();

            if ($variaciones->isEmpty()) {
                return (object) [
                    'precio_actual_min' => 0,
                    'precio_actual_max' => 0,
                    'precio_regular_min' => null,
                    'precio_regular_max' => null,
                    'tiene_rebaja' => false,
                    'es_agrupado' => false
                ];
            }

            $preciosActuales = collect();
            $preciosRegulares = collect();
            $tieneAlgunaRebaja = false;
            
            foreach ($variaciones as $variacion) {
                $tieneRebaja = $this->tieneRebajaActiva($variacion);
                
                if ($tieneRebaja) {
                    $tieneAlgunaRebaja = true;
                    $preciosActuales->push($variacion->precio_rebajado);
                    $preciosRegulares->push($variacion->precio_regular);
                } else {
                    if ($variacion->precio_regular > 0) {
                        $preciosActuales->push($variacion->precio_regular);
                    }
                }
            }
            
            return (object) [
                'precio_actual_min' => $preciosActuales->isNotEmpty() ? $preciosActuales->min() : 0,
                'precio_actual_max' => $preciosActuales->isNotEmpty() ? $preciosActuales->max() : 0,
                'precio_regular_min' => $preciosRegulares->isNotEmpty() ? $preciosRegulares->min() : null,
                'precio_regular_max' => $preciosRegulares->isNotEmpty() ? $preciosRegulares->max() : null,
                'tiene_rebaja' => $tieneAlgunaRebaja,
                'es_agrupado' => false
            ];
        }

        // Productos simples
        $tieneRebaja = $this->tieneRebajaActiva($this);
        
        return (object) [
            'precio_actual_min' => $tieneRebaja ? $this->precio_rebajado : $this->precio_regular,
            'precio_actual_max' => $tieneRebaja ? $this->precio_rebajado : $this->precio_regular,
            'precio_regular_min' => $tieneRebaja ? $this->precio_regular : null,
            'precio_regular_max' => $tieneRebaja ? $this->precio_regular : null,
            'tiene_rebaja' => $tieneRebaja,
            'es_agrupado' => false
        ];
    }

    /**
     * Obtiene el texto formateado del precio actual para mostrar
     */
    public function getPrecioFormateadoAttribute()
    {
        $rango = $this->rango_precios;
        
        if ($this->tipo_producto === 'variable' || $this->tipo_producto === 'agrupado') {
            $min = $rango->precio_actual_min;
            $max = $rango->precio_actual_max;
            
            if ($min == 0 && $max == 0) {
                return 'Precio no disponible';
            }
            
            if ($min == $max) {
                return 'S/.' . number_format($min, 2);
            } else {
                return 'S/.' . number_format($min, 2) . ' - S/.' . number_format($max, 2);
            }
        }
        
        // Producto simple
        if ($rango->precio_actual_min > 0) {
            return 'S/.' . number_format($rango->precio_actual_min, 2);
        }
        
        return 'Precio no disponible';
    }

    /**
     * Obtiene el texto formateado del precio regular original (tachado)
     */
    public function getPrecioRegularOriginalAttribute()
    {
        $rango = $this->rango_precios;
        
        // Solo mostrar tachado si HAY rebaja
        if (!$rango->tiene_rebaja) {
            return null;
        }
        
        $min = $rango->precio_regular_min;
        $max = $rango->precio_regular_max;
        
        if ($min === null || $max === null) {
            return null;
        }
        
        if ($min == $max) {
            return 'S/.' . number_format($min, 2);
        } else {
            return 'S/.' . number_format($min, 2) . ' - S/.' . number_format($max, 2);
        }
    }

    /**
     * Obtiene el porcentaje de descuento (para mostrar badge)
     */
    public function getDescuentoPorcentajeAttribute()
    {
        $rango = $this->rango_precios;
        
        if (!$rango->tiene_rebaja) {
            return null;
        }
        
        if ($this->tipo_producto === 'variable') {
            $descuentos = [];
            
            $variaciones = $this->variaciones()
                ->where('activo', true)
                ->get();
                
            foreach ($variaciones as $variacion) {
                if ($this->tieneRebajaActiva($variacion) && $variacion->precio_regular > 0 && $variacion->precio_rebajado > 0) {
                    $descuento = (1 - $variacion->precio_rebajado / $variacion->precio_regular) * 100;
                    if ($descuento > 0) {
                        $descuentos[] = round($descuento);
                    }
                }
            }
            
            return !empty($descuentos) ? max($descuentos) : null;
        }
        
        if ($this->tipo_producto === 'agrupado') {
            $descuentos = [];
            
            $productosHijos = $this->productosHijos()
                ->where('estado', 'publicado')
                ->get();
                
            foreach ($productosHijos as $hijo) {
                $precioInfo = $this->getPrecioProducto($hijo);
                if ($precioInfo->tiene_rebaja && $precioInfo->precio_regular_min > 0 && $precioInfo->precio_rebajado_min > 0) {
                    // Calcular descuento basado en el primer precio regular y rebajado
                    $descuento = (1 - $precioInfo->precio_rebajado_min / $precioInfo->precio_regular_min) * 100;
                    if ($descuento > 0) {
                        $descuentos[] = round($descuento);
                    }
                }
            }
            
            return !empty($descuentos) ? max($descuentos) : null;
        }
        
        // Producto simple
        if ($this->precio_regular > 0 && $this->precio_rebajado > 0) {
            $descuento = round((1 - $this->precio_rebajado / $this->precio_regular) * 100);
            return $descuento > 0 ? $descuento : null;
        }
        
        return null;
    }

    
}