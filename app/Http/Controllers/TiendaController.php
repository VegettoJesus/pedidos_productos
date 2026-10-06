<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracionSistema;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Producto;
use App\Models\ProductoValoracion;
use App\Models\ProductoAtributo;
use App\Models\AtributoTerm;
use App\Models\ProductoVariacion;
use App\Models\User;
use App\Models\Rol;
use App\Models\Atributo;
use App\Models\AtributoPrioridad;
use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\Carrito;
use App\Helpers\ConfiguracionHelper;
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
        
        $productos = $this->procesarVariacionesTarjeta($productos);

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

        $productos->getCollection()->transform(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });

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
            $productos->getCollection()->transform(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });

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
        
        // 🔥 Procesar variaciones para tarjeta
        $productos->getCollection()->transform(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });
        
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
            'variaciones' => function($q) {
                $q->where('activo', true)
                ->with(['atributos.atributo', 'imagenes']);
            },
            'subcategoria.categoria',
            'etiquetas',
            'valoraciones' => function($q) {
                $q->where('aprobado', true)->with('usuario');
            },
            'productosRelacionados' => function($q) {
                $q->with(['imagenes', 'valoraciones']);
            },
            'atributos' => function($q) {
                $q->withPivot('visible', 'variacion', 'tipo', 'shape');
            }
        ])->findOrFail($id);
        
        $valorExtraMap = [];
        $productoAtributos = ProductoAtributo::where('producto_id', $producto->id)
            ->with(['valores' => function($q) {
                $q->withPivot('valor_extra');
            }])
            ->get();
            
        foreach ($productoAtributos as $pa) {
            foreach ($pa->valores as $valor) {
                $key = $pa->atributo_id . '_' . $valor->id;
                $valorExtraMap[$key] = $valor->pivot->valor_extra;
            }
        }
        
        if ($producto->productosRelacionados->isNotEmpty()) {
            $producto->productosRelacionados = $this->procesarVariacionesTarjeta($producto->productosRelacionados);
        }
        
        $variacionesAgrupadas = $this->getVariacionesAgrupadas($producto);
        
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.producto-detalle',
            'producto' => $producto,
            'variacionesAgrupadas' => $variacionesAgrupadas,
            'valorExtraMap' => $valorExtraMap,
            'script' => 'js/tienda-producto-detalle.js',
        ]));
    }

    public function getOrdenAtributos(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id'
        ]);

        try {
            $productoId = $request->producto_id;

            // 🔥 Obtener orden desde atributo_prioridad
            $ordenAtributos = \App\Models\AtributoPrioridad::where('producto_id', $productoId)
                ->orderBy('prioridad', 'asc')
                ->pluck('atributo_id')
                ->toArray();

            // Si no hay prioridades definidas, obtener los atributos del producto
            if (empty($ordenAtributos)) {
                $ordenAtributos = ProductoAtributo::where('producto_id', $productoId)
                    ->where('variacion', 1)
                    ->pluck('atributo_id')
                    ->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => $ordenAtributos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el orden de atributos: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getVariacionesAgrupadas($producto)
{
    $variaciones = $producto->variaciones()
        ->where('activo', true)
        ->with(['atributos.atributo', 'imagenes'])
        ->get();
        
    $agrupado = [];
    
    // 🔥 PASO 1: Identificar qué atributos tiene cada variación (para detectar "Cualquier")
    $variacionesConAtributos = [];
    foreach ($variaciones as $variacion) {
        $variacionesConAtributos[$variacion->id] = [];
        foreach ($variacion->atributos as $termino) {
            $variacionesConAtributos[$variacion->id][] = $termino->atributo_id;
        }
        $variacionesConAtributos[$variacion->id] = array_unique($variacionesConAtributos[$variacion->id]);
    }
    
    // 🔥 PASO 2: Obtener términos registrados en producto_atributo_valores
    $terminosRegistrados = [];
    $productoAtributos = ProductoAtributo::where('producto_id', $producto->id)
        ->where('variacion', 1)
        ->with(['valores' => function($q) {
            // 🔥 CORREGIDO: No hacer join adicional, solo seleccionar los campos necesarios
            $q->select('atributo_terminos.id', 'atributo_terminos.nombre', 'atributo_terminos.atributo_id', 'producto_atributo_valores.valor_extra');
        }])
        ->get();
    
    foreach ($productoAtributos as $pa) {
        $atributoId = $pa->atributo_id;
        $terminosRegistrados[$atributoId] = [];
        foreach ($pa->valores as $valor) {
            $terminosRegistrados[$atributoId][] = [
                'id' => $valor->id,
                'nombre' => $valor->nombre,
                'valor_extra' => $valor->pivot->valor_extra ?? null
            ];
        }
    }
    
    // 🔥 PASO 3: Agrupar variaciones por atributo
    foreach ($variaciones as $variacion) {
        foreach ($variacion->atributos as $termino) {
            $atributoId = $termino->atributo_id;
            
            if (!isset($agrupado[$atributoId])) {
                $atributo = Atributo::find($atributoId);
                $productoAtributo = ProductoAtributo::where('producto_id', $producto->id)
                    ->where('atributo_id', $atributoId)
                    ->first();
                    
                $agrupado[$atributoId] = (object) [
                    'atributo' => $atributo,
                    'tipo' => $productoAtributo->tipo ?? 'Default',
                    'shape' => $productoAtributo->shape ?? 'Default',
                    'variaciones' => [],
                    'terminos_disponibles' => [],
                    'tiene_cualquier' => false,
                    'terminos_registrados' => $terminosRegistrados[$atributoId] ?? []
                ];
            }
            
            // Guardar términos que aparecen en variaciones
            if (!in_array($termino->id, $agrupado[$atributoId]->terminos_disponibles)) {
                $agrupado[$atributoId]->terminos_disponibles[] = $termino->id;
            }
            
            $existe = false;
            foreach ($agrupado[$atributoId]->variaciones as $v) {
                if ($v->id == $variacion->id) {
                    $existe = true;
                    break;
                }
            }
            
            if (!$existe) {
                $agrupado[$atributoId]->variaciones[] = $variacion;
            }
        }
    }
    
    // 🔥 PASO 4: Verificar si algún atributo tiene "Cualquier"
    foreach ($agrupado as $atributoId => $data) {
        foreach ($variaciones as $variacion) {
            $tieneAtributo = in_array($atributoId, $variacionesConAtributos[$variacion->id] ?? []);
            if (!$tieneAtributo) {
                $data->tiene_cualquier = true;
                break;
            }
        }
    }
    
    // 🔥 PASO 5: Obtener orden de atributos desde atributo_prioridad
    $ordenAtributos = AtributoPrioridad::where('producto_id', $producto->id)
        ->orderBy('prioridad', 'asc')
        ->pluck('atributo_id')
        ->toArray();
    
    if (!empty($ordenAtributos)) {
        $agrupadoReordenado = [];
        foreach ($ordenAtributos as $atributoId) {
            if (isset($agrupado[$atributoId])) {
                $agrupadoReordenado[$atributoId] = $agrupado[$atributoId];
            }
        }
        foreach ($agrupado as $atributoId => $data) {
            if (!isset($agrupadoReordenado[$atributoId])) {
                $agrupadoReordenado[$atributoId] = $data;
            }
        }
        $agrupado = $agrupadoReordenado;
    }
    
    // 🔥 PASO 6: Determinar qué términos mostrar
    foreach ($agrupado as $atributoId => $data) {
        // Si tiene "Cualquier", mostrar TODOS los términos registrados en producto_atributo_valores
        if ($data->tiene_cualquier) {
            $data->terminos_a_mostrar = collect($data->terminos_registrados)
                ->map(function($termino) {
                    return (object) [
                        'id' => $termino['id'],
                        'nombre' => $termino['nombre'],
                        'valor_extra' => $termino['valor_extra'] ?? null
                    ];
                })
                ->toArray();
            $data->termino_cualquier_id = null;
        } else {
            // Si NO tiene "Cualquier", mostrar SOLO los términos que aparecen en variaciones
            // y que están registrados en producto_atributo_valores
            $terminosRegistradosIds = collect($data->terminos_registrados)->pluck('id')->toArray();
            $terminosDisponiblesFiltrados = array_intersect($data->terminos_disponibles, $terminosRegistradosIds);
            
            $data->terminos_a_mostrar = collect($data->terminos_registrados)
                ->filter(function($termino) use ($terminosDisponiblesFiltrados) {
                    return in_array($termino['id'], $terminosDisponiblesFiltrados);
                })
                ->map(function($termino) {
                    return (object) [
                        'id' => $termino['id'],
                        'nombre' => $termino['nombre'],
                        'valor_extra' => $termino['valor_extra'] ?? null
                    ];
                })
                ->toArray();
            $data->termino_cualquier_id = null;
        }
    }
    
    return $agrupado;
}

    /**
     * Obtiene los detalles de una variación específica usando el procedimiento almacenado
     */
    public function getVariacionDetalle(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'variacion_id' => 'required|exists:producto_variaciones,id'
        ]);

        try {
            $result = DB::select(
                'CALL GetVariacionSeleccionada(?, ?)',
                [$request->producto_id, $request->variacion_id]
            );

            if (empty($result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la variación'
                ], 404);
            }

            $data = $result[0];
            
            // Procesar atributos combinados
            $atributos = [];
            if (!empty($data->atributos_combinados)) {
                $atributosRaw = explode('||', $data->atributos_combinados);
                foreach ($atributosRaw as $item) {
                    $parts = explode('|', $item);
                    if (count($parts) >= 4) {
                        $atributos[] = [
                            'atributo_id' => $parts[0],
                            'atributo_nombre' => $parts[1],
                            'termino_id' => !empty($parts[2]) ? $parts[2] : null,
                            'termino_nombre' => !empty($parts[3]) ? $parts[3] : 'Cualquier',
                        ];
                    }
                }
            }

            // Construir array de imágenes
            $imagenes = [];
            for ($i = 1; $i <= 6; $i++) {
                $imgField = 'img' . $i;
                if (!empty($data->$imgField)) {
                    $imagenes[] = $data->$imgField;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'variacion_id' => $data->variacion_id,
                    'sku' => $data->variacion_sku,
                    'precio_regular' => floatval($data->precio_regular),
                    'precio_rebajado' => $data->precio_rebajado ? floatval($data->precio_rebajado) : null,
                    'precio_final' => floatval($data->precio_final),
                    'descuento_porcentaje' => intval($data->descuento_porcentaje),
                    'stock' => intval($data->stock),
                    'gestion_inventario' => boolval($data->gestion_inventario),
                    'estado_inventario' => $data->estado_inventario,
                    'backorders' => boolval($data->backorders),
                    'vendido_individualmente' => boolval($data->vendido_individualmente),
                    'estado_actual' => $data->estado_actual,
                    'peso' => $data->peso ? floatval($data->peso) : null,
                    'peso_unidad' => $data->peso_unidad,
                    'longitud' => $data->longitud ? floatval($data->longitud) : null,
                    'anchura' => $data->anchura ? floatval($data->anchura) : null,
                    'altura' => $data->altura ? floatval($data->altura) : null,
                    'descripcion' => $data->variacion_descripcion,
                    'imagenes' => $imagenes,
                    'variacion_origen_id' => $data->variacion_origen_id ? intval($data->variacion_origen_id) : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles de la variación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los términos disponibles usando el procedimiento almacenado
     */
    public function getTerminosDisponibles(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'terminos' => 'required|array',
            'terminos.*' => 'exists:atributo_terminos,id'
        ]);

        try {
            $productoId = $request->producto_id;
            $terminosCsv = implode(',', $request->terminos);

            // Ejecutar el procedimiento almacenado
            $result = DB::select(
                'CALL GetTerminosDisponibles(?, ?)',
                [$productoId, $terminosCsv]
            );

            // 🔥 Agrupar resultados por atributo
            $atributos = [];
            foreach ($result as $row) {
                $atributoId = $row->atributo_id;
                if (!isset($atributos[$atributoId])) {
                    $atributos[$atributoId] = [
                        'atributo_id' => $atributoId,
                        'atributo_nombre' => $row->atributo_nombre,
                        'terminos' => []
                    ];
                }
                $atributos[$atributoId]['terminos'][] = [
                    'id' => $row->termino_id,
                    'nombre' => $row->termino_nombre,
                    'tiene_stock' => (bool)$row->tiene_stock
                ];
            }

            return response()->json([
                'success' => true,
                'data' => array_values($atributos)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function valorarProducto(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->id_rol != 2) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'puntuacion'  => 'required|integer|min:1|max:5',
            'comentario'  => 'nullable|string|max:1000'
        ]);

        $valoracion = ProductoValoracion::where([
            'producto_id' => $request->producto_id,
            'user_id'     => auth()->id()
        ])->first();

        if ($valoracion) {
            $valoracion->update([
                'puntuacion' => $request->puntuacion,
                'comentario' => $request->comentario,
                'aprobado'   => true,
                'updated_at' => now() 
            ]);
        } else {
            // Si no existe, crear nueva
            $valoracion = ProductoValoracion::create([
                'producto_id' => $request->producto_id,
                'user_id'     => auth()->id(),
                'puntuacion'  => $request->puntuacion,
                'comentario'  => $request->comentario,
                'aprobado'    => true
            ]);
        }

        $producto = Producto::find($request->producto_id);
        $avg = $producto->valoraciones()->where('aprobado', true)->avg('puntuacion');
        $count = $producto->valoraciones()->where('aprobado', true)->count();

        return response()->json([
            'success' => true,
            'rating'  => round($avg, 1),
            'count'   => $count,
            'user_rating' => $valoracion->puntuacion
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
            'nombres'      => 'required|string|max:150',
            'apellidos'    => 'required|string|max:150',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:6',
            'tipoDoc'      => 'nullable|string|max:50',
            'numeroDoc'    => 'nullable|string|max:20|unique:usuarios_datos,numeroDoc',
            'celular'      => 'nullable|string|max:15',
            'fecha_nacimiento' => 'nullable|date',
            'nacionalidad' => 'nullable|string|max:100',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'provincia_id' => 'nullable|exists:provincias,id',
            'distrito_id'  => 'nullable|exists:distritos,id',
            'calle'        => 'nullable|string|max:255',
            'numero'       => 'nullable|string|max:50',
            'dir_otros'    => 'nullable|string|max:255',
            'cod_postal'   => 'nullable|string|max:20',
        ]);

        // 🔥 Forzar rol client (ID 2)
        $rol = Rol::find(2); // client
        
        if (!$rol) {
            return response()->json(['success' => false, 'message' => 'Rol de cliente no configurado'], 500);
        }

        // Crear usuario
        $user = User::create([
            'nombres'    => $request->nombres,
            'apellidos'  => $request->apellidos,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'id_rol'     => $rol->id, // Siempre 2 (client)
            'estado'     => true,
            'conectado'  => false,
            'dark_mode'  => false,
        ]);

        // 🔥 CREAR DATOS DEL USUARIO EN TABLA usuarios_datos
        $usuarioDato = \App\Models\UsuarioDato::create([
            'id_usuario'      => $user->id,
            'tipoDoc'         => $request->tipoDoc,
            'numeroDoc'       => $request->numeroDoc,
            'celular'         => $request->celular,
            'fecha_nacimiento'=> $request->fecha_nacimiento,
            'nacionalidad'    => $request->nacionalidad,
            'departamento'    => $request->departamento_id,
            'provincia'       => $request->provincia_id,
            'distrito'        => $request->distrito_id,
            'calle'           => $request->calle,
            'numero'          => $request->numero,
            'dir_otros'       => $request->dir_otros,
            'cod_postal'      => $request->cod_postal,
        ]);
        $this->crearNotificacionBienvenida($user);
        Auth::login($user);
        $user->update(['conectado' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada exitosamente',
            'user' => [
                'id'        => $user->id,
                'nombres'   => $user->nombres,
                'apellidos' => $user->apellidos,
                'email'     => $user->email,
                'foto'      => asset('img/user.png'),
                'rol_id'    => $user->id_rol,
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
            
            if ($user->id_rol != 2) {
                Auth::logout();
                return response()->json([
                    'success' => false, 
                    'message' => 'Este usuario no tiene los permisos para acceder'
                ], 403);
            }
            
            $user->update(['conectado' => true]);
            return response()->json([
                'success' => true,
                'user' => [
                    'nombres'   => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email'     => $user->email,
                    'foto'      => asset('img/user.png'),
                    'rol_id'    => $user->id_rol,
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }

    public function logoutCliente(Request $request)
    {
        if (Auth::check()) {
            $carrito = Carrito::where('user_id', Auth::id())->first();
            if ($carrito) {
                $carrito->vaciar(true);
                $carrito->delete();
            }

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
            $productos->getCollection()->transform(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });

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

    /**
     * Obtiene las variaciones simplificadas para la tarjeta del producto
     */
    private function getVariacionesParaTarjeta($producto)
    {
        if ($producto->tipo_producto !== 'variable') {
            return null;
        }
        
        // Obtener el primer atributo que sea visible y variación
        $primerAtributo = ProductoAtributo::where('producto_id', $producto->id)
            ->where('visible', true)
            ->where('variacion', true)
            ->orderBy('id', 'asc')
            ->first();
        
        if (!$primerAtributo) {
            return null;
        }
        
        // Obtener los términos con sus valores extra
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
        
        // 🔥 Preparar imágenes de variaciones para data attribute
        $variacionesImagenes = [];
        
        foreach ($terminos as $termino) {
            // Buscar variación que tenga este término
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
                // Buscar imágenes de la variación
                if ($variacion->imagenes->isNotEmpty()) {
                    foreach ($variacion->imagenes as $img) {
                        $imagenesVariacion[] = $img->imagen_path;
                    }
                    $imagenUrl = $variacion->imagenes->first()->imagen_path;
                }
                $stock = $variacion->stock ?? 0;
                $precioRegular = $variacion->precio_regular ?? null;
                $precioRebajado = $variacion->precio_rebajado ?? null;
                
                // Guardar imágenes de la variación
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
        
        // Guardar imágenes de variaciones en el producto para data attribute
        $producto->variaciones_imagenes = $variacionesImagenes;
        
        return $resultado;
    }

    /**
     * Procesa una colección de productos para agregar variaciones_tarjeta
     */
    public function procesarVariacionesTarjeta($productos)
    {
        return $productos->map(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });
    }

    /**
     * Crear notificación de bienvenida para un nuevo cliente
     */
    private function crearNotificacionBienvenida($usuario)
    {
        $tipoUsuario = TipoNotificacion::where('slug', 'usuario')->first();
        
        if (!$tipoUsuario) {
            return;
        }

        $notificacion = Notificacion::create([
            'tipo_notificacion_id' => $tipoUsuario->id,
            'creado_por' => null,
            'titulo' => '¡Bienvenido a ' . ConfiguracionHelper::getCompanyName() . '!',
            'mensaje' => "Hola {$usuario->nombres} {$usuario->apellidos}, bienvenido a nuestra tienda. Estamos felices de tenerte con nosotros.",
            'mensaje_corto' => '¡Bienvenido a nuestra tienda!',
            'data_extra' => null,
            'url' => route('tienda.home'),
            'boton_texto' => 'Explorar tienda',
            'prioridad' => 'baja',
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(7),
            'usuario_id' => $usuario->id,
            'rol_id' => null,
            'visible' => true,
            'eliminada' => false
        ]);

        return $notificacion;
    }
}