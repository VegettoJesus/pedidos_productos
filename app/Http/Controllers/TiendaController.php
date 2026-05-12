<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracionSistema;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Producto;
use App\Models\ProductoValoracion;
use App\Models\User;
use App\Models\Rol;
use App\Models\Atributo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class TiendaController extends Controller
{
    
    private function getBaseConfig()
    {
        $config = ConfiguracionSistema::first();
        $authUser = null;
        if (Auth::check()) {
            $user = Auth::user();
            // Solo si el rol es 'client' lo pasamos; de lo contrario, null
            if ($user->rol && $user->rol->name === 'client') {
                $authUser = [
                    'nombres'   => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email'     => $user->email,
                    'foto'      => asset('img/user.png'),
                ];
            }
        }
        return [
            'titulo_site' => $config ? $config->titulo_site : null,
            'descripcion_corta' => $config ? $config->descripcion_corta : null,
            'authUser' => $authUser,
        ];
    }

    public function home()
    {
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.main',
        ]));
    }

    public function productos()
    {
        $base = $this->getBaseConfig();
        // Asumiendo que tienes una vista tienda.productos (podrías listar todos los productos)
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.productos',
        ]));
    }

    public function contacto()
    {
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.contacto',
        ]));
    }

    public function productosPorCategoria($id)
    {
        $categoria = Categoria::with('subcategorias')->findOrFail($id);
        $subcategoriaIds = $categoria->subcategorias->pluck('id')->toArray();

        $productos = Producto::with([
                'imagenes',
                'valoraciones' => function($q) {
                    $q->where('aprobado', true);
                },
                'variaciones' 
            ])
            ->whereIn('id_subCategorias', $subcategoriaIds)
            ->where('estado', 'publicado')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.categoria',
            'categoria'  => $categoria,
            'productos'  => $productos,
        ]));
    }

    public function productosPorSubcategoria(Request $request, $id)
    {
        $subcategoria = Subcategoria::with('categoria')->findOrFail($id);
        $categoria = $subcategoria->categoria;
        $subcategoriaId = $subcategoria->id;

        // ---------- FILTRO PRINCIPAL DE PRODUCTOS (solo en esta subcategoría) ----------
        $query = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado')
            ->where(function ($q) use ($subcategoriaId) {
                $q->where('id_subCategorias', $subcategoriaId)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaId) {
                    $sub->where('id_subCategorias', $subcategoriaId)
                        ->where('estado', 'publicado');
                });
            });

        // Filtro por atributo_termino (igual que en categoría)
        if ($request->filled('atributo_termino')) {
            $terminos = (array)$request->atributo_termino;
            $query->where(function ($mainQuery) use ($terminos, $subcategoriaId) {
                foreach ($terminos as $terminoId) {
                    $mainQuery->orWhereExists(function ($existsQuery) use ($terminoId, $subcategoriaId) {
                        $existsQuery->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($catQuery) use ($subcategoriaId) {
                                $catQuery->where('p2.id_subCategorias', $subcategoriaId)
                                    ->orWhereExists(function ($subQuery) use ($subcategoriaId) {
                                        $subQuery->select(DB::raw(1))
                                            ->from('producto_agrupado as pa')
                                            ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                            ->whereColumn('pa.producto_padre_id', 'p2.id')
                                            ->where('hijo.id_subCategorias', $subcategoriaId)
                                            ->where('hijo.estado', 'publicado');
                                    });
                            })
                            ->where(function ($attrQuery) use ($terminoId) {
                                // Los 4 caminos para que un producto tenga el término (igual que antes)
                                $attrQuery->whereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                            });
                    });
                }
            });
        }

        // Filtro rating mínimo
        if ($request->filled('rating_min') && $request->rating_min >= 1) {
            $ratingMin = (int)$request->rating_min;
            $query->whereHas('valoraciones', function ($q) use ($ratingMin) {
                $q->select('producto_id', \DB::raw('AVG(puntuacion) as avg_rating'))
                ->groupBy('producto_id')
                ->havingRaw('AVG(puntuacion) >= ?', [$ratingMin]);
            });
        }

        // Orden
        switch ($request->get('orden', 'novedad')) {
            case 'precio_asc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL AND precio_rebajado > 0 AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE(
                                (SELECT MIN(CASE WHEN pv.precio_rebajado IS NOT NULL AND pv.precio_rebajado > 0 AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW()) THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1),
                                999999999
                            )
                        ELSE 999999999
                    END ASC
                ");
                break;
            case 'precio_desc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL AND precio_rebajado > 0 AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE(
                                (SELECT MIN(CASE WHEN pv.precio_rebajado IS NOT NULL AND pv.precio_rebajado > 0 AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW()) THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1),
                                0
                            )
                        ELSE 0
                    END DESC
                ");
                break;
            case 'nombre': $query->orderBy('nombre', 'asc'); break;
            default: $query->orderBy('created_at', 'desc');
        }

        $productos = $query->paginate(32)->appends($request->query());

        // ---------- OBTENER ATRIBUTOS Y TÉRMINOS CON CONTADORES REALES (para esta subcategoría) ----------
        $atributosConTerminos = [];

        // Obtener IDs de atributos que aparecen en la subcategoría (directa o mediante hijos agrupados)
        $atributosIds = Producto::where('estado', 'publicado')
            ->where(function ($q) use ($subcategoriaId) {
                $q->where('id_subCategorias', $subcategoriaId)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaId) {
                    $sub->where('id_subCategorias', $subcategoriaId)
                        ->where('estado', 'publicado');
                });
            })
            ->where(function ($q) {
                $q->whereHas('valoresAtributos')
                ->orWhereHas('productosHijos.valoresAtributos')
                ->orWhereHas('variaciones.atributos')
                ->orWhereHas('productosHijos.variaciones.atributos');
            })
            ->join('producto_atributo', 'productos.id', '=', 'producto_atributo.producto_id')
            ->distinct()
            ->pluck('producto_atributo.atributo_id');

        foreach ($atributosIds as $attrId) {
            $atributo = Atributo::find($attrId);
            if (!$atributo) continue;

            $terminosConConteo = $atributo->terminos->map(function ($termino) use ($subcategoriaId) {
                $count = Producto::where('estado', 'publicado')
                    ->where(function ($q) use ($subcategoriaId) {
                        $q->where('id_subCategorias', $subcategoriaId)
                        ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaId) {
                            $sub->where('id_subCategorias', $subcategoriaId)
                                ->where('estado', 'publicado');
                        });
                    })
                    ->whereExists(function ($exists) use ($termino, $subcategoriaId) {
                        $exists->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($cat) use ($subcategoriaId) {
                                $cat->where('p2.id_subCategorias', $subcategoriaId)
                                    ->orWhereExists(function ($sub) use ($subcategoriaId) {
                                        $sub->select(DB::raw(1))
                                            ->from('producto_agrupado as pa')
                                            ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                            ->whereColumn('pa.producto_padre_id', 'p2.id')
                                            ->where('hijo.id_subCategorias', $subcategoriaId)
                                            ->where('hijo.estado', 'publicado');
                                    });
                            })
                            ->where(function ($attr) use ($termino) {
                                // Los 4 caminos para que el término esté presente en el producto
                                $attr->whereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                            });
                    })->count();

                $termino->producto_atributos_count = $count;
                return $termino;
            })->filter(fn($t) => $t->producto_atributos_count > 0)
            ->sortByDesc('producto_atributos_count');

            if ($terminosConConteo->isNotEmpty()) {
                $atributo->terminos = $terminosConConteo;
                $atributosConTerminos[] = $atributo;
            }
        }

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2'          => 'tienda.subcategoria',
            'subcategoria'        => $subcategoria,
            'categoria'           => $categoria,
            'productos'           => $productos,
            'atributosConTerminos'=> $atributosConTerminos,
            'filtros'             => $request->all()
        ]));
    }

    public function todosProductos(Request $request)
    {
        // ---------- QUERY BASE: todos los productos publicados ----------
        $query = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado');

        // ---------- FILTRO POR CATEGORÍA (si se envía) ----------
        $categoriasSeleccionadas = null;
        if ($request->filled('categoria')) {
            $categoriaIds = (array)$request->categoria;
            $categoriasSeleccionadas = Categoria::whereIn('id', $categoriaIds)->get();
            
            if ($categoriasSeleccionadas->isNotEmpty()) {
                // Obtener todos los IDs de subcategorías de las categorías seleccionadas
                $subcategoriaIds = collect();
                foreach ($categoriasSeleccionadas as $categoria) {
                    $subcategoriaIds = $subcategoriaIds->merge($categoria->subcategorias->pluck('id'));
                }
                $subcategoriaIds = $subcategoriaIds->unique()->toArray();
                
                $query->where(function ($q) use ($subcategoriaIds) {
                    $q->whereIn('id_subCategorias', $subcategoriaIds)
                    ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                        $sub->whereIn('id_subCategorias', $subcategoriaIds)
                            ->where('estado', 'publicado');
                    });
                });
            }
        }

        // ---------- FILTRO POR SUBCATEGORÍA (si se envía) ----------
        if ($request->filled('subcategoria')) {
            $subcategoriaIds = (array)$request->subcategoria;
            $query->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds);
                });
            });
        }

        // ---------- FILTRO POR ATRIBUTO_TÉRMINO ----------
        if ($request->filled('atributo_termino')) {
            $terminos = (array)$request->atributo_termino;
            $query->where(function ($mainQuery) use ($terminos) {
                foreach ($terminos as $terminoId) {
                    $mainQuery->orWhereExists(function ($existsQuery) use ($terminoId) {
                        $existsQuery->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($attrQuery) use ($terminoId) {
                                $attrQuery->whereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                            });
                    });
                }
            });
        }

        // ---------- FILTRO RATING MÍNIMO ----------
        if ($request->filled('rating_min') && $request->rating_min >= 1) {
            $ratingMin = (int)$request->rating_min;
            $query->whereHas('valoraciones', function ($q) use ($ratingMin) {
                $q->select('producto_id', DB::raw('AVG(puntuacion) as avg_rating'))
                ->groupBy('producto_id')
                ->havingRaw('AVG(puntuacion) >= ?', [$ratingMin]);
            });
        }

        // ---------- ORDEN ----------
        switch ($request->get('orden', 'novedad')) {
            case 'precio_asc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL AND precio_rebajado > 0 
                                    AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE(
                                (SELECT MIN(CASE WHEN pv.precio_rebajado IS NOT NULL AND pv.precio_rebajado > 0 
                                                AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW()) 
                                            THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1),
                                999999999
                            )
                        ELSE 999999999
                    END ASC
                ");
                break;
            case 'precio_desc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL AND precio_rebajado > 0 
                                    AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE(
                                (SELECT MIN(CASE WHEN pv.precio_rebajado IS NOT NULL AND pv.precio_rebajado > 0 
                                                AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW()) 
                                            THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1),
                                0
                            )
                        ELSE 0
                    END DESC
                ");
                break;
            case 'nombre':
                $query->orderBy('nombre', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $productos = $query->paginate(32)->appends($request->query());

        // ---------- OBTENER CATEGORÍAS (para el select de filtro) ----------
        $categorias = Categoria::with(['subcategorias' => function($q) {
            $q->withCount(['productos' => function($p) {
                $p->where('estado', 'publicado');
            }]);
        }])->withCount(['productos' => function($p) {
            $p->where('estado', 'publicado');
        }])->get();

        // ---------- OBTENER SUBCATEGORÍAS DISPONIBLES (para el filtro) ----------
        $subcategoriasDisponibles = collect();
        if ($request->filled('categoria')) {
            $categoriaIds = (array)$request->categoria;
            $subcategoriasDisponibles = Subcategoria::whereIn('id_categoria', $categoriaIds)
                ->withCount(['productos' => function($q) {
                    $q->where('estado', 'publicado');
                }])
                ->get();
        }

        // ---------- OBTENER ATRIBUTOS Y TÉRMINOS CON CONTADORES REALES ----------
        $atributosConTerminos = [];

        // Subconsulta para obtener los productos que cumplen los filtros actuales
        $productosFiltradosQuery = Producto::where('estado', 'publicado');
        
        if ($request->filled('categoria') && isset($categoriasSeleccionadas) && $categoriasSeleccionadas->isNotEmpty()) {
            $subcategoriaIds = collect();
            foreach ($categoriasSeleccionadas as $categoria) {
                $subcategoriaIds = $subcategoriaIds->merge($categoria->subcategorias->pluck('id'));
            }
            $subcategoriaIds = $subcategoriaIds->unique()->toArray();
            
            $productosFiltradosQuery->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds);
                });
            });
        }
        
        if ($request->filled('subcategoria')) {
            $subcategoriaIds = (array)$request->subcategoria;
            $productosFiltradosQuery->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds);
                });
            });
        }
        
        if ($request->filled('atributo_termino')) {
            $terminos = (array)$request->atributo_termino;
            $productosFiltradosQuery->where(function ($mainQuery) use ($terminos) {
                foreach ($terminos as $terminoId) {
                    $mainQuery->orWhereExists(function ($existsQuery) use ($terminoId) {
                        $existsQuery->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($attrQuery) use ($terminoId) {
                                $attrQuery->whereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                            });
                    });
                }
            });
        }

        // Extraer los atributos y términos con contadores
        $atributosIds = (clone $productosFiltradosQuery)
            ->where(function ($q) {
                $q->whereHas('valoresAtributos')
                ->orWhereHas('productosHijos.valoresAtributos')
                ->orWhereHas('variaciones.atributos')
                ->orWhereHas('productosHijos.variaciones.atributos');
            })
            ->join('producto_atributo', 'productos.id', '=', 'producto_atributo.producto_id')
            ->distinct()
            ->pluck('producto_atributo.atributo_id');

        foreach ($atributosIds as $attrId) {
            $atributo = Atributo::find($attrId);
            if (!$atributo) continue;

            $terminosConConteo = $atributo->terminos->map(function ($termino) use ($productosFiltradosQuery) {
                $count = (clone $productosFiltradosQuery)
                    ->whereExists(function ($exists) use ($termino) {
                        $exists->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($attr) use ($termino) {
                                $attr->whereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                            });
                    })->count();

                $termino->producto_atributos_count = $count;
                return $termino;
            })->filter(fn($t) => $t->producto_atributos_count > 0)
            ->sortByDesc('producto_atributos_count');

            if ($terminosConConteo->isNotEmpty()) {
                $atributo->terminos = $terminosConConteo;
                $atributosConTerminos[] = $atributo;
            }
        }

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.todos-productos',
            'productos' => $productos,
            'categorias' => $categorias,
            'subcategoriasDisponibles' => $subcategoriasDisponibles, 
            'atributosConTerminos' => $atributosConTerminos,
            'filtros' => $request->all()
        ]));
    }

    public function ofertas()
    {
        $productos = Producto::whereNotNull('precio_rebajado')
                    ->where('estado', 'publicado')
                    ->where(function($q) {
                        $q->whereNull('fecha_fin_rebaja')
                        ->orWhere('fecha_fin_rebaja', '>=', now());
                    })
                    ->paginate(12);
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.ofertas',
            'productos' => $productos,
        ]));
    }

    public function nosotros()
    {
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.nosotros',
        ]));
    }

    public function detalleProducto($id)
    {
        $producto = Producto::with([
            'imagenes',
            'variaciones.atributos.atributo',
            'subcategoria.categoria',
            'etiquetas',
            'valoraciones' => function($q) {
                $q->where('aprobado', true)->with('usuario');
            },
            'productosRelacionados' => function($q) {
                $q->with(['imagenes', 'valoraciones']);
            }
        ])->findOrFail($id);
        
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.producto-detalle',
            'producto' => $producto,
        ]));
    }

    public function valorarProducto(Request $request)
    {
        $user = Auth::user();
        // Verificar que el usuario tenga rol 'client'
        if (!$user->rol || $user->rol->name !== 'client') {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'puntuacion'  => 'required|integer|min:1|max:5'
        ]);

        $valoracion = ProductoValoracion::updateOrCreate(
            [
                'producto_id' => $request->producto_id,
                'user_id'     => auth()->id()
            ],
            [
                'puntuacion' => $request->puntuacion,
                'aprobado'   => true  // o false si quieres moderación
            ]
        );

        $producto = Producto::find($request->producto_id);
        $avg = $producto->valoraciones()->where('aprobado', true)->avg('puntuacion');
        $count = $producto->valoraciones()->where('aprobado', true)->count();

        return response()->json([
            'success' => true,
            'rating'  => round($avg, 1),
            'count'   => $count
        ]);
    }

    public function todasCategorias()
    {
        $categorias = Categoria::with(['subcategorias' => function($q) {
            $q->withCount(['productos' => function($p) {
                $p->where('estado', 'publicado');
            }]);
        }])->get();

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2'  => 'tienda.categorias',
            'categorias'  => $categorias,
        ]));
    }

    public function registroCliente(Request $request)
    {
        $request->validate([
            'nombres'  => 'required|string|max:150',
            'apellidos' => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $rol = Rol::where('name', 'client')->first();
        if (!$rol) {
            return response()->json(['success' => false, 'message' => 'Rol de cliente no configurado'], 500);
        }

        $user = User::create([
            'nombres'    => $request->nombres,
            'apellidos'  => $request->apellidos,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'id_rol'     => $rol->id,
            'estado'     => true,
            'conectado'  => false,
            'dark_mode'  => false,
        ]);

        Auth::login($user);
        $user->update(['conectado' => true]);

        return response()->json([
            'success' => true,
            'user' => [
                'nombres'   => $user->nombres,
                'apellidos' => $user->apellidos,
                'email'     => $user->email,
                'foto'      => asset('img/user.png'),
            ]
        ]);
    }

    public function loginCliente(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $user->update(['conectado' => true]);
            return response()->json([
                'success' => true,
                'user' => [
                    'nombres'   => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email'     => $user->email,
                    'foto'      => asset('img/user.png'),
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }

    public function logoutCliente(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->update(['conectado' => false]);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return response()->json(['success' => true]);
    }

    public function productosCategoriaCompleta(Request $request, $id)
    {
        $categoria = Categoria::with('subcategorias')->findOrFail($id);
        $subcategoriaIds = $categoria->subcategorias->pluck('id')->toArray();

        // ---------- FILTRO PRINCIPAL DE PRODUCTOS (CONSULTA SQL EQUIVALENTE) ----------
        $query = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado')
            ->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds)
                        ->where('estado', 'publicado');
                });
            });

        // Filtro subcategorías
        if ($request->filled('subcategoria')) {
            $subs = (array)$request->subcategoria;
            $query->where(function ($q) use ($subs) {
                $q->whereIn('id_subCategorias', $subs)
                ->orWhereHas('productosHijos', function ($sub) use ($subs) {
                    $sub->whereIn('id_subCategorias', $subs);
                });
            });
        }

        // Filtro por atributo_termino (incluye directos y vía variaciones)
        if ($request->filled('atributo_termino')) {
        $terminos = (array)$request->atributo_termino;
        
        $query->where(function ($mainQuery) use ($terminos, $subcategoriaIds) {
            // Construimos una condición OR para cada término (por si se seleccionan varios)
            foreach ($terminos as $terminoId) {
                $mainQuery->orWhereExists(function ($existsQuery) use ($terminoId, $subcategoriaIds) {
                    $existsQuery->select(DB::raw(1))
                        ->from('productos as p2')
                        ->whereColumn('p2.id', 'productos.id')
                        ->where('p2.estado', 'publicado')
                        ->where(function ($catQuery) use ($subcategoriaIds) {
                            // Condición de categoría (producto pertenece a la categoría actual)
                            $catQuery->whereIn('p2.id_subCategorias', $subcategoriaIds)
                                ->orWhereExists(function ($subQuery) use ($subcategoriaIds) {
                                    $subQuery->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->whereIn('hijo.id_subCategorias', $subcategoriaIds)
                                        ->where('hijo.estado', 'publicado');
                                });
                        })
                        ->where(function ($attrQuery) use ($terminoId) {
                            // Los 4 caminos para que un producto tenga el término
                            $attrQuery->whereExists(function ($q) use ($terminoId) {
                                $q->select(DB::raw(1))
                                    ->from('producto_atributo as pa')
                                    ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                    ->whereColumn('pa.producto_id', 'p2.id')
                                    ->where('pav.termino_id', $terminoId);
                            });
                            $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                $q->select(DB::raw(1))
                                    ->from('producto_agrupado as pa')
                                    ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                    ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                    ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                    ->whereColumn('pa.producto_padre_id', 'p2.id')
                                    ->where('hijo.estado', 'publicado')
                                    ->where('pav2.termino_id', $terminoId);
                            });
                            $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                $q->select(DB::raw(1))
                                    ->from('producto_variaciones as pv')
                                    ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                    ->whereColumn('pv.producto_padre_id', 'p2.id')
                                    ->where('vat.atributo_termino_id', $terminoId);
                            });
                            $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                $q->select(DB::raw(1))
                                    ->from('producto_agrupado as pa')
                                    ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                    ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                    ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                    ->whereColumn('pa.producto_padre_id', 'p2.id')
                                    ->where('hijo.estado', 'publicado')
                                    ->where('vat.atributo_termino_id', $terminoId);
                            });
                        });
                });
            }
        });
    }

        // Filtro rating mínimo (igual)
        if ($request->filled('rating_min') && $request->rating_min >= 1) {
            $ratingMin = (int)$request->rating_min;
            $query->whereHas('valoraciones', function ($q) use ($ratingMin) {
                $q->select('producto_id', \DB::raw('AVG(puntuacion) as avg_rating'))
                ->groupBy('producto_id')
                ->havingRaw('AVG(puntuacion) >= ?', [$ratingMin]);
            });
        }

        // Orden
        switch ($request->get('orden', 'novedad')) {
            case 'precio_asc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL 
                                    AND precio_rebajado > 0
                                    AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado
                                ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable', 'agrupado') THEN
                            COALESCE(
                                (SELECT MIN(
                                    CASE 
                                        WHEN pv.precio_rebajado IS NOT NULL 
                                            AND pv.precio_rebajado > 0
                                            AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW())
                                        THEN pv.precio_rebajado
                                        ELSE pv.precio_regular
                                    END
                                ) FROM producto_variaciones pv 
                                WHERE pv.producto_padre_id = productos.id AND pv.activo = 1
                                ),
                                999999999
                            )
                        ELSE 999999999
                    END ASC
                ");
                break;
            case 'precio_desc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL 
                                    AND precio_rebajado > 0
                                    AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado
                                ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable', 'agrupado') THEN
                            COALESCE(
                                (SELECT MIN(
                                    CASE 
                                        WHEN pv.precio_rebajado IS NOT NULL 
                                            AND pv.precio_rebajado > 0
                                            AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW())
                                        THEN pv.precio_rebajado
                                        ELSE pv.precio_regular
                                    END
                                ) FROM producto_variaciones pv 
                                WHERE pv.producto_padre_id = productos.id AND pv.activo = 1
                                ),
                                0
                            )
                        ELSE 0
                    END DESC
                ");
                break;
            case 'nombre':
                $query->orderBy('nombre', 'asc');
                break;
            default: // novedad
                $query->orderBy('created_at', 'desc');
        }

        $productos = $query->paginate(32)->appends($request->query());

        // ---------- CONTADOR DE SUBCATEGORÍAS (incluye agrupados) ----------
        $subcategorias = $categoria->subcategorias->map(function ($sub) {
            $count = Producto::where('estado', 'publicado')
                ->where(function ($q) use ($sub) {
                    $q->where('id_subCategorias', $sub->id)
                    ->orWhereHas('productosHijos', function ($hijos) use ($sub) {
                        $hijos->where('id_subCategorias', $sub->id);
                    });
                })->count();
            $sub->productos_count = $count;
            return $sub;
        });

        // ---------- OBTENER ATRIBUTOS Y TÉRMINOS CON CONTADORES REALES (usando la misma subconsulta que el filtro) ----------
        $atributosConTerminos = [];

        // Obtener todos los atributos que tienen al menos un término asociado a productos de la categoría (siguiendo la misma lógica)
        $atributosIds = Producto::where('estado', 'publicado')
            ->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds)
                        ->where('estado', 'publicado');
                });
            })
            ->where(function ($q) {
                // Restringir a productos que tengan alguna forma de atributo (para obtener solo atributos relevantes)
                $q->whereHas('valoresAtributos')
                ->orWhereHas('productosHijos.valoresAtributos')
                ->orWhereHas('variaciones.atributos')
                ->orWhereHas('productosHijos.variaciones.atributos');
            })
            ->join('producto_atributo', 'productos.id', '=', 'producto_atributo.producto_id')
            ->distinct()
            ->pluck('producto_atributo.atributo_id');

        foreach ($atributosIds as $attrId) {
            $atributo = Atributo::find($attrId);
            if (!$atributo) continue;

            // Para cada término del atributo, calcular cuántos productos de la categoría lo poseen (exactamente como en el filtro)
            $terminosConConteo = $atributo->terminos->map(function ($termino) use ($subcategoriaIds) {
                // Subconsulta que replica exactamente la condición del filtro (sin la restricción del término)
                $count = Producto::where('estado', 'publicado')
                    ->where(function ($q) use ($subcategoriaIds) {
                        // Condición de categoría (producto directo o agrupado con hijo en categoría)
                        $q->whereIn('id_subCategorias', $subcategoriaIds)
                        ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                            $sub->whereIn('id_subCategorias', $subcategoriaIds)
                                ->where('estado', 'publicado');
                        });
                    })
                    ->whereExists(function ($exists) use ($termino, $subcategoriaIds) {
                        // Replicamos la subconsulta del filtro pero con el término específico
                        $exists->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($cat) use ($subcategoriaIds) {
                                $cat->whereIn('p2.id_subCategorias', $subcategoriaIds)
                                    ->orWhereExists(function ($sub) use ($subcategoriaIds) {
                                        $sub->select(DB::raw(1))
                                            ->from('producto_agrupado as pa')
                                            ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                            ->whereColumn('pa.producto_padre_id', 'p2.id')
                                            ->whereIn('hijo.id_subCategorias', $subcategoriaIds)
                                            ->where('hijo.estado', 'publicado');
                                    });
                            })
                            ->where(function ($attr) use ($termino) {
                                // Los 4 caminos para que el término esté presente en el producto
                                $attr->whereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                            });
                    })->count();

                $termino->producto_atributos_count = $count;
                return $termino;
            })->filter(function ($termino) {
                return $termino->producto_atributos_count > 0;
            })->sortByDesc('producto_atributos_count');

            if ($terminosConConteo->isNotEmpty()) {
                $atributo->terminos = $terminosConConteo;
                $atributosConTerminos[] = $atributo;
            }
        }

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2'          => 'tienda.categoria-todos',
            'categoria'           => $categoria,
            'productos'           => $productos,
            'subcategorias'       => $subcategorias,
            'atributosConTerminos'=> $atributosConTerminos,
            'filtros'             => $request->all()
        ]));
    }

}