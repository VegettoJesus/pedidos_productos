<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;

class BusquedaController extends Controller
{
    public function buscar(Request $request)
    {
        $query = $request->get('q');
        if (strlen($query) < 2) {
            return redirect()->route('tienda.home');
        }
        
        $productos = Producto::where('estado', 'publicado')
            ->where(function($q) use ($query) {
                $q->where('nombre', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('descripcion', 'LIKE', "%{$query}%");
            })
            ->paginate(20);
        
        return view('tienda.busqueda', compact('productos', 'query'));
    }
    
    public function sugerencias(Request $request)
    {
        $query = $request->get('q');
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        // Productos
        $productos = Producto::where('estado', 'publicado')
            ->where('nombre', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'nombre', 'imagen_miniatura', 'precio_regular']);
        
        // Categorías
        $categorias = Categoria::where('nombre', 'LIKE', "%{$query}%")
            ->limit(3)
            ->get(['id', 'nombre', 'icono']);
        
        // Subcategorías
        $subcategorias = Subcategoria::where('nombre', 'LIKE', "%{$query}%")
            ->with('categoria')
            ->limit(3)
            ->get(['id', 'nombre', 'icono', 'id_categoria']);
        
        $results = [
            'productos' => $productos->map(fn($p) => [
                'tipo' => 'producto',
                'id' => $p->id,
                'nombre' => $p->nombre,
                'imagen' => $p->imagen_miniatura ? asset($p->imagen_miniatura) : asset('img/default-product.png'),
                'precio' => $p->precio_regular,
                'url' => route('producto.detalle', $p->id)
            ]),
            'categorias' => $categorias->map(fn($c) => [
                'tipo' => 'categoria',
                'id' => $c->id,
                'nombre' => $c->nombre,
                'icono' => $c->icono,
                'url' => route('productos.categoria', $c->id)
            ]),
            'subcategorias' => $subcategorias->map(fn($s) => [
                'tipo' => 'subcategoria',
                'id' => $s->id,
                'nombre' => $s->nombre,
                'categoria_nombre' => $s->categoria->nombre,
                'url' => route('productos.subcategoria', $s->id)
            ])
        ];
        
        return response()->json($results);
    }
}