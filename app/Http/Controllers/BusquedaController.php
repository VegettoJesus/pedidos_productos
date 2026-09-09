<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\ProductoAtributo;
use App\Models\ProductoVariacion;
use App\Models\Atributo;

class BusquedaController extends Controller
{
    private function procesarVariacionesTarjeta($productos)
    {
        return $productos->map(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });
    }

    private function getVariacionesParaTarjeta($producto)
    {
        if ($producto->tipo_producto !== 'variable') {
            return null;
        }
        
        $primerAtributo = ProductoAtributo::where('producto_id', $producto->id)
            ->where('visible', true)
            ->where('variacion', true)
            ->orderBy('id', 'asc')
            ->first();
        
        if (!$primerAtributo) {
            return null;
        }
        
        $terminos = $primerAtributo->valores()
            ->withPivot('valor_extra')
            ->get();
        
        $resultado = [
            'atributo_id' => $primerAtributo->atributo_id,
            'atributo_nombre' => $primerAtributo->atributo->nombre ?? 'Atributo',
            'tipo' => $primerAtributo->tipo ?? 'Default',
            'shape' => $primerAtributo->shape ?? 'Default',
            'terminos' => []
        ];
        
        $variacionesImagenes = [];
        
        foreach ($terminos as $termino) {
            $variacion = ProductoVariacion::where('producto_padre_id', $producto->id)
                ->where('activo', true)
                ->whereHas('atributos', function($q) use ($termino) {
                    $q->where('atributo_terminos.id', $termino->id);
                })
                ->first();
            
            $imagenUrl = null;
            $stock = 0;
            $precioRegular = null;
            $precioRebajado = null;
            $imagenesVariacion = [];
            
            if ($variacion) {
                if ($variacion->imagenes->isNotEmpty()) {
                    foreach ($variacion->imagenes as $img) {
                        $imagenesVariacion[] = $img->imagen_path;
                    }
                    $imagenUrl = $variacion->imagenes->first()->imagen_path;
                }
                $stock = $variacion->stock ?? 0;
                $precioRegular = $variacion->precio_regular ?? null;
                $precioRebajado = $variacion->precio_rebajado ?? null;
                
                if (!empty($imagenesVariacion)) {
                    $variacionesImagenes[$variacion->id] = $imagenesVariacion;
                }
            }
            
            $resultado['terminos'][] = [
                'id' => $termino->id,
                'nombre' => $termino->nombre,
                'valor_extra' => $termino->pivot->valor_extra ?? null,
                'imagen_url' => $imagenUrl,
                'variacion_id' => $variacion ? $variacion->id : null,
                'stock' => $stock,
                'precio_regular' => $precioRegular,
                'precio_rebajado' => $precioRebajado,
                'disponible' => $variacion !== null,
            ];
        }
        
        $producto->variaciones_imagenes = $variacionesImagenes;
        
        return $resultado;
    }

    public function buscar(Request $request)
    {
        $query = trim($request->get('q'));
        
        if (strlen($query) < 2) {
            return redirect()->route('tienda.home');
        }
        
        $productoExacto = Producto::where('estado', 'publicado')
            ->where('nombre', 'LIKE', $query)
            ->first();
            
        if ($productoExacto) {
            return redirect()->route('producto.detalle', $productoExacto->id);
        }
        
        // Búsqueda general con paginación
        $productos = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado')
            ->where(function($q) use ($query) {
                $q->where('nombre', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('descripcion', 'LIKE', "%{$query}%")
                  ->orWhere('marca', 'LIKE', "%{$query}%");
            })
            ->paginate(20);
        
        $productos->getCollection()->transform(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });
        
        $categorias = Categoria::where('nombre', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();
        
        $subcategorias = Subcategoria::where('nombre', 'LIKE', "%{$query}%")
            ->with('categoria')
            ->limit(5)
            ->get();
        
        return view('layouts.contenido2', array_merge([
            'contenido2'  => 'tienda.busqueda',
            'productos'  => $productos,
            'query'  => $query,
            'categorias'  => $categorias,
            'subcategorias'  => $subcategorias,
        ]));
    }
    
    public function sugerencias(Request $request)
    {
        $query = trim($request->get('q'));
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $productos = Producto::where('estado', 'publicado')
            ->where(function($q) use ($query) {
                $q->where('nombre', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('marca', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'nombre', 'imagen_miniatura', 'precio_regular', 'tipo_producto']);
        
        $categorias = Categoria::where('nombre', 'LIKE', "%{$query}%")
            ->limit(3)
            ->get(['id', 'nombre', 'icono']);
        
        $subcategorias = Subcategoria::where('nombre', 'LIKE', "%{$query}%")
            ->with('categoria')
            ->limit(3)
            ->get(['id', 'nombre', 'icono', 'id_categoria']);
        
        $results = [
            'productos' => $productos->map(function($p) {
                $rango = $p->rango_precios;
                $precioMostrar = $rango->precio_actual_min;
                
                if ($p->tipo_producto === 'variable' || $p->tipo_producto === 'agrupado') {
                    $min = $rango->precio_actual_min;
                    $max = $rango->precio_actual_max;
                    
                    if ($min == $max) {
                        $precioMostrar = $min;
                    } else {
                        $precioMostrar = $min . ' - ' . $max;
                    }
                }
                
                return [
                    'tipo' => 'producto',
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'imagen' => $p->imagen_miniatura ? asset($p->imagen_miniatura) : asset('img/default-product.png'),
                    'precio' => $precioMostrar,
                    'precio_formateado' => $p->precio_formateado,
                    'tipo_producto' => $p->tipo_producto,
                    'url' => route('producto.detalle', $p->id)
                ];
            }),
            'categorias' => $categorias->map(fn($c) => [
                'tipo' => 'categoria',
                'id' => $c->id,
                'nombre' => $c->nombre,
                'icono' => $c->icono,
                'url' => route('categoria.productos.completa', $c->id)
            ]),
            'subcategorias' => $subcategorias->map(fn($s) => [
                'tipo' => 'subcategoria',
                'id' => $s->id,
                'nombre' => $s->nombre,
                'categoria_nombre' => $s->categoria->nombre,
                'url' => route('productos.subcategoria', $s->id)
            ]),
            'tiene_resultados' => $productos->isNotEmpty() || $categorias->isNotEmpty() || $subcategorias->isNotEmpty()
        ];
        
        return response()->json($results);
    }
}