<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Traits\AuditableTrait;
use App\Models\Producto;
use App\Models\Subcategoria;
use App\Models\Etiqueta;
use App\Models\Atributo;
use App\Models\AtributoTerm;
use App\Models\ProductoAgrupado;
use App\Models\ProductoRelacionado;
use App\Models\ProductoAtributo;
use App\Models\ProductoEtiqueta;
use Illuminate\Support\Facades\File;
use App\Models\ProductoImagen;
use App\Models\ProductoVariacion;
use App\Models\VariacionImagen;
use App\Services\MenuService;
use App\Data\Icons;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Catalogo extends Controller
{
    use AuditableTrait;
    public function gestionCatalogo(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $categorias = Categoria::with('subcategorias')->get();
                    $currentUrl = $request->path();
                    $data->permisosVista = [
                        'editar' => MenuService::tienePermiso($currentUrl, 'editar'),
                        'eliminar' => MenuService::tienePermiso($currentUrl, 'eliminar'),
                        'subcategoria' => MenuService::tienePermiso($currentUrl, 'subcategoria'),
                    ];
                    $data->respuesta = 'ok';
                    $data->categorias = $categorias;
                    break;

                case 'Crear':
                    $categoria = Categoria::create([
                        'nombre' => $request->input('nombre'),
                        'icono'  => $request->input('icono')
                    ]);
                    $this->registrarAuditoria('Crear','categorias',$categoria->id,$categoria->nombre);
                    $data->respuesta = 'ok';
                    $data->mensaje = 'Categoría creada correctamente';
                    break;

                case 'Editar':
                    $categoria = Categoria::find($request->input('id'));
                    if ($categoria) {
                        $data->respuesta = 'ok';
                        $data->categoria = $categoria;
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Categoría no encontrada';
                    }
                    break;

                case 'Actualizar':
                    $categoria = Categoria::find($request->input('id'));
                    
                    if ($categoria) {
                        $nuevosDatos = [
                            'nombre' => $request->input('nombre'),
                            'icono'  => $request->input('icono')
                        ];
                        
                        $this->auditarActualizacion($categoria, $nuevosDatos);
                        $categoria->update($nuevosDatos);
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Categoría actualizada correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Categoría no encontrada';
                    }
                    break;
                case 'ListarSubcategorias':
                    $query = Subcategoria::with('categoria');
                    
                    if ($request->filled('categoria_id')) {
                        $query->where('id_categoria', $request->categoria_id);
                    }
                    
                    $subcategorias = $query->get();
                    $data->respuesta = 'ok';
                    $data->subcategorias = $subcategorias;
                    break;

                case 'Eliminar':
                    $categoria = Categoria::find($request->input('id'));
                    if ($categoria) {
                        $this->registrarAuditoria('Eliminar', 'categorias', $categoria->id, $categoria->nombre);
                        $categoria->delete();
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Categoría eliminada correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Categoría no encontrada';
                    }
                    break;
                case 'Crear_Subcategoria':
                    $subcategoria = Subcategoria::create([
                        'nombre' => $request->input('nombre'),
                        'icono'  => $request->input('icono'),
                        'id_categoria' => $request->input('id_categoria'),
                    ]);
                    $this->registrarAuditoria('Crear','subcategorias',$subcategoria->id,$subcategoria->nombre);
                    $data->respuesta = 'ok';
                    $data->mensaje = 'Subcategoría creada correctamente';
                    break;
                case 'Editar_Subcategoria':
                    $subcategoria = Subcategoria::find($request->input('id'));
                    if ($subcategoria) {
                        $data->respuesta = 'ok';
                        $data->subcategoria = $subcategoria;
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Subcategoría no encontrada';
                    }
                    break;
                case 'Actualizar_Subcategoria':
                    $subcategoria = Subcategoria::find($request->input('id'));
                    
                    if ($subcategoria) {
                        $valoresAnteriores = [
                            'nombre' => $subcategoria->nombre,
                            'icono'  => $subcategoria->icono,
                            'id_categoria' => $subcategoria->id_categoria
                        ];
                        
                        $nuevosDatos = [
                            'nombre' => $request->input('nombre'),
                            'icono'  => $request->input('icono'),
                            'id_categoria' => $request->input('id_categoria')
                        ];
                        
                        $subcategoria->update($nuevosDatos);
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'subcategorias',
                            $subcategoria->id,
                            $subcategoria->nombre,      
                            $valoresAnteriores,         
                            $nuevosDatos                
                        );
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Subcategoría actualizada correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Subcategoría no encontrada';
                    }
                    break;

                case 'Eliminar_Subcategoria':
                    $subcategoria = Subcategoria::find($request->input('id'));
                    
                    if ($subcategoria) {
                        $this->registrarAuditoria(
                            'Eliminar',
                            'subcategorias',
                            $subcategoria->id,
                            $subcategoria->nombre 
                        );
                        
                        $subcategoria->delete();
                        
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Subcategoría eliminada correctamente';
                    } else {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Subcategoría no encontrada';
                    }
                    break;
                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->iconos = Icons::all();
            $data->script = 'js/gestionCatalogo.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'catalogo.gestionCatalogo';
            return view('layouts.contenido', (array) $data);
        }
    }

    public function productos(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    try {
                        $productos = Producto::with([
                            'subcategoria:id,nombre',
                            'imagenes:id,producto_id,imagen_path',
                            'etiquetas:id,nombre,color',
                            'atributos:id,nombre',
                            'variaciones',
                            'productosAgrupados.productoHijo:id,precio_regular,precio_rebajado,fecha_inicio_rebaja,fecha_fin_rebaja,nombre,imagen_miniatura'
                        ])
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($p) {
                            $hoy = now();

                            // 🔹 Inventario en texto
                            $inventario = match ($p->estado_inventario) {
                                'existe'   => 'Hay existencias',
                                'agotado'  => 'Agotado',
                                'reservar' => 'Se puede reservar',
                                default    => '—'
                            };
                            if ($p->stock > 0) {
                                $inventario .= " ({$p->stock})";
                            }

                            // 🔹 Precio dinámico según tipo
                            $precio = '—';

                            // --- SIMPLE ---
                            if ($p->tipo_producto === 'simple') {
                                if ($p->precio_rebajado &&
                                    (!$p->fecha_inicio_rebaja || $hoy->gte($p->fecha_inicio_rebaja)) &&
                                    (!$p->fecha_fin_rebaja || $hoy->lte($p->fecha_fin_rebaja))) {
                                    $precio = "<span class='precio-tachado'>".number_format($p->precio_regular, 2)."</span> 
                                            <span class='precio-rebajado'>".number_format($p->precio_rebajado, 2)."</span>";
                                } else {
                                    $precio = "<span class='precio-normal'>".number_format($p->precio_regular, 2)."</span>";
                                }
                            }

                            // --- VARIABLE ---
                            if ($p->tipo_producto === 'variable') {
                                $variaciones = $p->variaciones->map(function ($v) use ($hoy) {
                                    $rebajaActiva = $v->precio_rebajado > 0 &&
                                        (!$v->fecha_inicio_rebaja || $hoy->gte($v->fecha_inicio_rebaja)) &&
                                        (!$v->fecha_fin_rebaja || $hoy->lte($v->fecha_fin_rebaja));

                                    return [
                                        'regular' => $v->precio_regular > 0 ? $v->precio_regular : null,
                                        'rebajado' => $rebajaActiva ? $v->precio_rebajado : null
                                    ];
                                });

                                // Filtrar solo variaciones con precios válidos
                                $valores = $variaciones->map(function ($v) {
                                    return $v['rebajado'] ?? $v['regular'];
                                })->filter(fn($val) => $val !== null && $val > 0);

                                if ($valores->count()) {
                                    $min = $valores->min();
                                    $max = $valores->max();

                                    if ($min == $max) {
                                        // Mostrar si la rebaja está activa en todas
                                        $rebajasActivas = $variaciones->filter(fn($v) => $v['rebajado'] !== null);
                                        if ($rebajasActivas->count()) {
                                            $precio = "<span class='precio-tachado'>".number_format($rebajasActivas->first()['regular'], 2)."</span> 
                                                    <span class='precio-rebajado'>".number_format($rebajasActivas->first()['rebajado'], 2)."</span>";
                                        } else {
                                            $precio = "<span class='precio-unico'>".number_format($min, 2)."</span>";
                                        }
                                    } else {
                                        $precio = "<span class='precio-rango'>".number_format($min, 2)." - ".number_format($max, 2)."</span>";
                                    }
                                } else {
                                    $precio = '—';
                                }
                            }

                            // --- AGRUPADO ---
                            if ($p->tipo_producto === 'agrupado') {
                                $hijos = $p->productosAgrupados->map(function ($ag) use ($hoy) {
                                    $hijo = $ag->productoHijo;
                                    if (!$hijo) return null;

                                    if ($hijo->precio_rebajado &&
                                        (!$hijo->fecha_inicio_rebaja || $hoy->gte($hijo->fecha_inicio_rebaja)) &&
                                        (!$hijo->fecha_fin_rebaja || $hoy->lte($hijo->fecha_fin_rebaja))) {
                                        return $hijo->precio_rebajado;
                                    }
                                    return $hijo->precio_regular;
                                })->filter(fn($val) => $val > 0);

                                if ($hijos->count()) {
                                    $min = $hijos->min();
                                    $max = $hijos->max();
                                    $precio = $min == $max
                                        ? "<span class='precio-unico'>".number_format($min, 2)."</span>"
                                        : "<span class='precio-rango'>".number_format($min, 2)." - ".number_format($max, 2)."</span>";
                                }
                            }

                            return [
                                'id' => $p->id,
                                'nombre' => $p->nombre,
                                'descripcion' => Str::limit(strip_tags($p->descripcion), 60),
                                'tipo_producto' => ucfirst($p->tipo_producto),
                                'sku' => $p->sku,
                                'inventario' => $inventario,
                                'precio' => $precio,
                                'marca' => $p->marca,
                                'subcategoria' => $p->subcategoria->nombre ?? '-',
                                'imagen' => $p->imagen_miniatura ? asset($p->imagen_miniatura) : asset('/img/default.png'),
                                'etiquetas' => $p->etiquetas->map(fn($e) => [
                                    'nombre' => $e->nombre,
                                    'color' => $e->color
                                ]),
                                'created_at' => $p->estado . ' - ' . $p->created_at->format('Y-m-d H:i'),
                            ];
                        });

                        $data->respuesta = 'ok';
                        $data->productos = $productos;
                    } catch (\Exception $e) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al listar productos: ' . $e->getMessage();
                    }
                    break;

                case 'Obtener':
                    $id = $request->input('id');

                    $producto = Producto::with([
                        'subcategoria',
                        'imagenes',
                        'etiquetas',
                        'atributos', 
                        'variaciones.atributos',
                        'variaciones.imagenes',
                        'productosAgrupados.productoHijo',
                        'productosRelacionados' => function($query) {
                            $query->select('productos.id', 'productos.nombre', 'productos.sku')
                                ->withPivot('tipo');
                        }
                    ])->find($id);

                    if (!$producto) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Producto no encontrado';
                        break;
                    }

                    foreach ($producto->atributos as $atributo) {
                        $terminosSeleccionados = \DB::table('producto_atributo_valores')
                            ->join('producto_atributo', 'producto_atributo.id', '=', 'producto_atributo_valores.producto_atributo_id')
                            ->join('atributo_terminos', 'atributo_terminos.id', '=', 'producto_atributo_valores.termino_id')
                            ->where('producto_atributo.producto_id', $producto->id)
                            ->where('producto_atributo.atributo_id', $atributo->id)
                            ->select('atributo_terminos.*')
                            ->get();
                        
                        $atributo->setRelation('terminos', $terminosSeleccionados);
                    }

                    $data->respuesta = 'ok';
                    $data->producto = $producto;
                    break;

                case 'Crear':
                    DB::beginTransaction();

                    try {

                        $descripcionLimpia = preg_replace('/<img[^>]*>/i', '', $request->input('descripcion_larga', ''));                   
                        $productoBaseFields = [
                            'nombre', 'descripcion', 'tipo_producto', 'id_subCategorias',
                            'sku', 'marca', 'peso', 'peso_unidad', 'longitud',
                            'anchura', 'altura', 'nota_interna', 'gestion_inventario',
                            'estado_inventario', 'vendido_individualmente','stock', 
                            'stock_minimo', 'max_stock'
                        ];

                        // Si es simple o agrupado → sí necesita precios y stock
                        if ($request->input('tipo_producto') === 'simple') {
                            $productoBaseFields = array_merge($productoBaseFields, [
                                'precio_regular', 'precio_rebajado',
                                'fecha_inicio_rebaja', 'fecha_fin_rebaja'
                            ]);
                        }

                        $productoData = $request->only($productoBaseFields);
                        $productoData['descripcion_completa'] = $descripcionLimpia;
                        $productoData['id_usuario'] = Auth::id();
                        $productoData['estado'] = $request->input('estado');
                        $productoData['gestion_inventario'] = $request->boolean('gestion_inventario');
                        $productoData['vendido_individualmente'] = $request->boolean('vendido_individualmente');
                        $productoData['permite_valoraciones'] = $request->boolean('permite_valoraciones');
                        $productoData['backorders'] = $request->input('backorders') === 'yes';
                        $productoData['stock'] = intval($request->input('stock') ?? 0);
                        $producto = Producto::create($productoData);

                        // --- Carpeta destino ---
                        $carpetaDestino = public_path('image_producto');
                        if (!File::exists($carpetaDestino)) {
                            File::makeDirectory($carpetaDestino, 0755, true);
                        }

                        $fecha = date('Ymd_His');

                        // 1️⃣ Miniatura
                        if ($request->hasFile('imagen_miniatura')) {
                            $file = $request->file('imagen_miniatura');
                            $nombre = 'mini_' . Str::random(5) . "_$fecha." . $file->getClientOriginalExtension();
                            $file->move($carpetaDestino, $nombre);
                            $producto->update(['imagen_miniatura' => "image_producto/$nombre"]);
                        }

                        // 2️⃣ Imágenes principales
                        if ($request->hasFile('imagenes')) {
                            foreach ($request->file('imagenes') as $i => $imagen) {
                                $nombre = 'img_' . ($i + 1) . '_' . Str::random(5) . "_$fecha." . $imagen->getClientOriginalExtension();
                                $imagen->move($carpetaDestino, $nombre);

                                ProductoImagen::create([
                                    'producto_id' => $producto->id,
                                    'imagen_path' => "image_producto/$nombre"
                                ]);
                            }
                        }

                        // 3️⃣ Etiquetas
                        if ($request->has('etiquetas')) {
                            $etiquetasIds = json_decode($request->input('etiquetas'), true);
                            
                            // Validar que las etiquetas existen
                            $etiquetasExistentes = Etiqueta::whereIn('id', $etiquetasIds)->pluck('id')->toArray();
                            
                            // Sincronizar solo etiquetas válidas
                            $producto->etiquetas()->sync($etiquetasExistentes);
                        }

                        // 4️⃣ Atributos del producto
                        if ($request->has('atributos')) {
                            $atributosData = json_decode($request->input('atributos'), true);
                            $this->syncProductoAtributos($producto, $atributosData);
                        }

                        // 5️⃣ Variaciones (solo para productos variables)
                        if ($producto->tipo_producto === 'variable' && $request->has('variaciones')) {
                            $variacionesData = json_decode($request->input('variaciones'), true);
                            $this->syncVariaciones($producto, $variacionesData, $request);
                        }

                        // 6️⃣ Ventas dirigidas (Upsells) y cruzadas (Cross-sells)
                        if ($request->has('upsells')) {
                            $upsellsIds = json_decode($request->input('upsells'), true);
                            foreach ($upsellsIds as $relId) {
                                ProductoRelacionado::create([
                                    'producto_id' => $producto->id,
                                    'producto_relacionado_id' => $relId,
                                    'tipo' => 'upsell'
                                ]);
                            }
                        }

                        if ($request->has('crosssells')) {
                            $crosssellsIds = json_decode($request->input('crosssells'), true);
                            foreach ($crosssellsIds as $relId) {
                                ProductoRelacionado::create([
                                    'producto_id' => $producto->id,
                                    'producto_relacionado_id' => $relId,
                                    'tipo' => 'crosssell'
                                ]);
                            }
                        }

                        if ($producto->tipo_producto === 'agrupado' && $request->has('relacionados')) {
                            $agrupadosIds = json_decode($request->input('relacionados'), true);

                            foreach ($agrupadosIds as $hijoId) {
                                ProductoAgrupado::create([
                                    'producto_padre_id' => $producto->id,
                                    'producto_hijo_id'  => $hijoId
                                ]);
                            }
                        }

                        DB::commit();

                        $this->registrarAuditoria(
                            'Crear',
                            'productos',
                            $producto->id,
                            $producto->nombre,              
                            null,
                            null,
                            "Tipo: {$producto->tipo_producto} | SKU: {$producto->sku}"  
                        );

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Producto creado correctamente';

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al crear el producto: ' . $e->getMessage();
                    }

                    break;

                case 'Editar':
                    DB::beginTransaction();
                    
                    try {
                        $id = $request->input('id');
                        $producto = Producto::find($id);
                        
                        if (!$producto) {
                            $data->respuesta = 'error';
                            $data->mensaje = 'Producto no encontrado';
                            break;
                        }

                        $valoresAnteriores = [
                            'nombre' => $producto->nombre,
                            'descripcion' => $producto->descripcion,
                            'descripcion_completa' => $producto->descripcion_completa,
                            'tipo_producto' => $producto->tipo_producto,
                            'id_subCategorias' => $producto->id_subCategorias,
                            'sku' => $producto->sku,
                            'marca' => $producto->marca,
                            'precio_regular' => $producto->precio_regular,
                            'precio_rebajado' => $producto->precio_rebajado,
                            'fecha_inicio_rebaja' => $producto->fecha_inicio_rebaja,
                            'fecha_fin_rebaja' => $producto->fecha_fin_rebaja,
                            'estado' => $producto->estado,
                            'gestion_inventario' => $producto->gestion_inventario,
                            'estado_inventario' => $producto->estado_inventario,
                            'stock' => $producto->stock,
                            'vendido_individualmente' => $producto->vendido_individualmente,
                            'backorders' => $producto->backorders,
                            'permite_valoraciones' => $producto->permite_valoraciones,
                            'peso' => $producto->peso,
                            'peso_unidad' => $producto->peso_unidad,
                            'longitud' => $producto->longitud,
                            'anchura' => $producto->anchura,
                            'altura' => $producto->altura,
                            'nota_interna' => $producto->nota_interna
                        ];

                        $etiquetasAnteriores = $producto->etiquetas->map(function($etiqueta) {
                            return [
                                'id' => $etiqueta->id,
                                'nombre' => $etiqueta->nombre
                            ];
                        })->toArray();
                        $etiquetasAnterioresIds = array_column($etiquetasAnteriores, 'id');
                        $etiquetasAnterioresNombres = [];
                        foreach ($etiquetasAnteriores as $et) {
                            $etiquetasAnterioresNombres[$et['id']] = $et['nombre'];
                        }

                        // Guardar ATRIBUTOS anteriores (con nombres y valores)
                        $atributosAnteriores = ProductoAtributo::where('producto_id', $producto->id)
                            ->with(['atributo', 'valores'])
                            ->get();
                        
                        $atributosAnterioresData = [];
                        foreach ($atributosAnteriores as $pa) {
                            $atributosAnterioresData[] = [
                                'atributo_id' => $pa->atributo_id,
                                'atributo_nombre' => $pa->atributo ? $pa->atributo->nombre : 'Desconocido',
                                'visible' => $pa->visible,
                                'variacion' => $pa->variacion,
                                'valores' => $pa->valores->map(function($valor) {
                                    return [
                                        'id' => $valor->id,
                                        'nombre' => $valor->nombre
                                    ];
                                })->toArray()
                            ];
                        }

                        $descripcionLimpia = preg_replace('/<img[^>]*>/i', '', $request->input('descripcion_larga', ''));
                        $productoData = [
                            'nombre' => $request->input('nombre'),
                            'descripcion' => $request->input('descripcion'),
                            'descripcion_completa' => $descripcionLimpia,
                            'tipo_producto' => 'simple', 
                            'id_subCategorias' => $request->input('subcategoria_id'),
                            'sku' => $request->input('sku'),
                            'marca' => $request->input('marca'),
                            'precio_regular' => $request->input('precio_regular'),
                            'precio_rebajado' => $request->input('precio_rebajado'),
                            'fecha_inicio_rebaja' => $request->input('fecha_inicio_rebaja'),
                            'fecha_fin_rebaja' => $request->input('fecha_fin_rebaja'),
                            'estado' => $request->input('estado'),
                            'gestion_inventario' => $request->boolean('gestion_inventario'),
                            'estado_inventario' => $request->input('estado_inventario'),
                            'stock' => intval($request->input('stock') ?? 0),
                            'vendido_individualmente' => $request->boolean('vendido_individualmente'),
                            'backorders' => $request->input('backorders') === 'yes',
                            'permite_valoraciones' => $request->boolean('permite_valoraciones'),
                            'peso' => $request->input('peso'),
                            'peso_unidad' => $request->input('peso_unidad'),
                            'longitud' => $request->input('longitud'),
                            'anchura' => $request->input('anchura'),
                            'altura' => $request->input('altura'),
                            'nota_interna' => $request->input('nota_interna')
                        ];

                        // Limpiar fechas de rebaja si no están activas
                        if (!$request->input('programar_rebaja')) {
                            $productoData['fecha_inicio_rebaja'] = null;
                            $productoData['fecha_fin_rebaja'] = null;
                        }

                        $producto->update($productoData);

                        // --- Carpeta destino para imágenes ---
                        $carpetaDestino = public_path('image_producto');
                        if (!File::exists($carpetaDestino)) {
                            File::makeDirectory($carpetaDestino, 0755, true);
                        }

                        $fecha = date('Ymd_His');

                        // --- 1. MINIATURA ---
                        $miniaturaCambio = false;
                        if ($request->input('eliminar_miniatura') === 'true' && $producto->imagen_miniatura) {
                            $rutaMiniatura = public_path($producto->imagen_miniatura);
                            if (File::exists($rutaMiniatura)) {
                                File::delete($rutaMiniatura);
                            }
                            $producto->update(['imagen_miniatura' => null]);
                            $miniaturaCambio = true;
                        }

                        if ($request->hasFile('imagen_miniatura')) {
                            if ($producto->imagen_miniatura) {
                                $rutaAnterior = public_path($producto->imagen_miniatura);
                                if (File::exists($rutaAnterior)) {
                                    File::delete($rutaAnterior);
                                }
                            }
                            
                            $file = $request->file('imagen_miniatura');
                            $nombre = 'mini_' . Str::random(5) . "_$fecha." . $file->getClientOriginalExtension();
                            $file->move($carpetaDestino, $nombre);
                            $producto->update(['imagen_miniatura' => "image_producto/$nombre"]);
                            $miniaturaCambio = true;
                        }

                        // --- 2. IMÁGENES PRINCIPALES ---
                        $imagenesEliminadas = 0;
                        $imagenesAgregadas = 0;
                        
                        if ($request->has('imagenes_eliminar')) {
                            $idsEliminar = json_decode($request->input('imagenes_eliminar'), true);
                            if (is_array($idsEliminar) && count($idsEliminar) > 0) {
                                $imagenesEliminar = ProductoImagen::whereIn('id', $idsEliminar)->get();
                                foreach ($imagenesEliminar as $img) {
                                    $ruta = public_path($img->imagen_path);
                                    if (File::exists($ruta)) {
                                        File::delete($ruta);
                                    }
                                    $img->delete();
                                    $imagenesEliminadas++;
                                }
                            }
                        }

                        // Subir nuevas imágenes
                        if ($request->hasFile('imagenes')) {
                            foreach ($request->file('imagenes') as $i => $imagen) {
                                $nombre = 'img_' . ($i + 1) . '_' . Str::random(5) . "_$fecha." . $imagen->getClientOriginalExtension();
                                $imagen->move($carpetaDestino, $nombre);

                                ProductoImagen::create([
                                    'producto_id' => $producto->id,
                                    'imagen_path' => "image_producto/$nombre"
                                ]);
                                $imagenesAgregadas++;
                            }
                        }

                        // --- 3. ETIQUETAS ---
                        $etiquetasNuevasIds = [];
                        $etiquetasNuevasNombres = [];
                        if ($request->has('etiquetas')) {
                            $etiquetasNuevasIds = json_decode($request->input('etiquetas'), true);
                            if (!empty($etiquetasNuevasIds)) {
                                $etiquetasNuevas = Etiqueta::whereIn('id', $etiquetasNuevasIds)->get();
                                foreach ($etiquetasNuevas as $et) {
                                    $etiquetasNuevasNombres[$et->id] = $et->nombre;
                                }
                            }
                            $producto->etiquetas()->sync($etiquetasNuevasIds);
                        }

                        // --- 4. ATRIBUTOS ---
                        $atributosNuevosData = [];
        
                        if ($request->has('atributos')) {
                            $atributosData = json_decode($request->input('atributos'), true);
                            
                            // Guardar nuevos atributos para auditoría
                            foreach ($atributosData as $attr) {
                                $atributo = Atributo::find($attr['atributo_id']);
                                $valoresInfo = [];
                                
                                if (!empty($attr['valores'])) {
                                    // Obtener los valores con sus IDs y nombres
                                    $valores = AtributoTerm::whereIn('id', $attr['valores'])->get();
                                    foreach ($valores as $valor) {
                                        $valoresInfo[] = [
                                            'id' => $valor->id,
                                            'nombre' => $valor->nombre
                                        ];
                                    }
                                }
                                
                                $atributosNuevosData[] = [
                                    'atributo_id' => $attr['atributo_id'],
                                    'atributo_nombre' => $atributo ? $atributo->nombre : 'Desconocido',
                                    'visible' => $attr['visible'] ?? true,
                                    'variacion' => $attr['variacion'] ?? false,
                                    'valores' => $valoresInfo  // Guardar como array asociativo con id y nombre
                                ];
                            }
                            
                            // Eliminar atributos actuales
                            ProductoAtributo::where('producto_id', $producto->id)->delete();
                            
                            foreach ($atributosData as $atributoData) {
                                $productoAtributo = ProductoAtributo::create([
                                    'producto_id' => $producto->id,
                                    'atributo_id' => $atributoData['atributo_id'],
                                    'visible' => $atributoData['visible'] ?? true,
                                    'variacion' => $atributoData['variacion'] ?? false
                                ]);

                                if (!empty($atributoData['valores'])) {
                                    $productoAtributo->valores()->sync($atributoData['valores']);
                                }
                            }
                        }

                        // --- 5. PRODUCTOS RELACIONADOS (Upsells/Crosssells) ---
                        $upsellsAnteriores = ProductoRelacionado::where('producto_id', $producto->id)
                            ->where('tipo', 'upsell')
                            ->with('relacionado')
                            ->get();

                        $crosssellsAnteriores = ProductoRelacionado::where('producto_id', $producto->id)
                            ->where('tipo', 'crosssell')
                            ->with('relacionado')
                            ->get();

                        $upsellsAnterioresIds = $upsellsAnteriores->pluck('producto_relacionado_id')->toArray();
                        $upsellsAnterioresNombres = [];
                        foreach ($upsellsAnteriores as $upsell) {
                            if ($upsell->relacionado) {
                                $upsellsAnterioresNombres[$upsell->producto_relacionado_id] = $upsell->relacionado->nombre;
                            }
                        }

                        $crosssellsAnterioresIds = $crosssellsAnteriores->pluck('producto_relacionado_id')->toArray();
                        $crosssellsAnterioresNombres = [];
                        foreach ($crosssellsAnteriores as $crosssell) {
                            if ($crosssell->relacionado) {
                                $crosssellsAnterioresNombres[$crosssell->producto_relacionado_id] = $crosssell->relacionado->nombre;
                            }
                        }

                        $deleted = ProductoRelacionado::where('producto_id', $producto->id)->delete();

                        $relacionesAGuardar = [];
                        $upsellsNuevosIds = [];
                        $crosssellsNuevosIds = [];

                        if ($request->has('upsells')) {
                            $upsellsIds = json_decode($request->input('upsells'), true);
                            if (is_array($upsellsIds)) {
                                foreach ($upsellsIds as $relId) {
                                    $relacionesAGuardar[] = [
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => 'upsell'
                                    ];
                                    $upsellsNuevosIds[] = $relId;
                                }
                            }
                        }

                        if ($request->has('crosssells')) {
                            $crosssellsIds = json_decode($request->input('crosssells'), true);
                            if (is_array($crosssellsIds)) {
                                foreach ($crosssellsIds as $relId) {
                                    $relacionesAGuardar[] = [
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => 'crosssell'
                                    ];
                                    $crosssellsNuevosIds[] = $relId;
                                }
                            }
                        }

                        $upsellsNuevosNombres = [];
                        if (!empty($upsellsNuevosIds)) {
                            $productosUpsells = Producto::whereIn('id', $upsellsNuevosIds)->get();
                            foreach ($productosUpsells as $prod) {
                                $upsellsNuevosNombres[$prod->id] = $prod->nombre;
                            }
                        }

                        $crosssellsNuevosNombres = [];
                        if (!empty($crosssellsNuevosIds)) {
                            $productosCrosssells = Producto::whereIn('id', $crosssellsNuevosIds)->get();
                            foreach ($productosCrosssells as $prod) {
                                $crosssellsNuevosNombres[$prod->id] = $prod->nombre;
                            }
                        }

                        if (!empty($relacionesAGuardar)) {
                            foreach ($relacionesAGuardar as $relacion) {
                                try {
                                    ProductoRelacionado::create($relacion);
                                } catch (\Exception $e) {
                                    if ($e->getCode() == 23000) {
                                        ProductoRelacionado::updateOrCreate(
                                            [
                                                'producto_id' => $relacion['producto_id'],
                                                'producto_relacionado_id' => $relacion['producto_relacionado_id']
                                            ],
                                            ['tipo' => $relacion['tipo']]
                                        );
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }

                        DB::commit();
                        
                        $detalleExtra = [];
        
                        if ($miniaturaCambio) {
                            $detalleExtra[] = "Miniatura actualizada";
                        }
                        if ($imagenesEliminadas > 0) {
                            $detalleExtra[] = "{$imagenesEliminadas} imagen(es) eliminada(s)";
                        }
                        if ($imagenesAgregadas > 0) {
                            $detalleExtra[] = "{$imagenesAgregadas} imagen(es) agregada(s)";
                        }

                        $etiquetasEliminadasIds = array_diff($etiquetasAnterioresIds, $etiquetasNuevasIds);
                        $etiquetasEliminadasNombres = [];
                        foreach ($etiquetasEliminadasIds as $id) {
                            if (isset($etiquetasAnterioresNombres[$id])) {
                                $etiquetasEliminadasNombres[] = $etiquetasAnterioresNombres[$id];
                            }
                        }
                        
                        $etiquetasAgregadasIds = array_diff($etiquetasNuevasIds, $etiquetasAnterioresIds);
                        $etiquetasAgregadasNombres = [];
                        foreach ($etiquetasAgregadasIds as $id) {
                            if (isset($etiquetasNuevasNombres[$id])) {
                                $etiquetasAgregadasNombres[] = $etiquetasNuevasNombres[$id];
                            }
                        }
                        
                        if (!empty($etiquetasEliminadasNombres)) {
                            $detalleExtra[] = "Etiquetas eliminadas: " . implode(', ', $etiquetasEliminadasNombres);
                        }
                        if (!empty($etiquetasAgregadasNombres)) {
                            $detalleExtra[] = "Etiquetas agregadas: " . implode(', ', $etiquetasAgregadasNombres);
                        }
                        $atributosAnterioresIds = array_column($atributosAnterioresData, 'atributo_id');
                        $atributosNuevosIds = array_column($atributosNuevosData, 'atributo_id');

                        $atributosEliminadosIds = array_diff($atributosAnterioresIds, $atributosNuevosIds);
                        $atributosEliminadosNombres = [];
                        foreach ($atributosAnterioresData as $attr) {
                            if (in_array($attr['atributo_id'], $atributosEliminadosIds)) {
                                $atributosEliminadosNombres[] = $attr['atributo_nombre'];
                            }
                        }

                        $atributosAgregadosIds = array_diff($atributosNuevosIds, $atributosAnterioresIds);
                        $atributosAgregadosNombres = [];
                        foreach ($atributosNuevosData as $attr) {
                            if (in_array($attr['atributo_id'], $atributosAgregadosIds)) {
                                $atributosAgregadosNombres[] = $attr['atributo_nombre'];
                            }
                        }

                        if (!empty($atributosEliminadosNombres)) {
                            $detalleExtra[] = "Atributos eliminados: " . implode(', ', $atributosEliminadosNombres);
                        }
                        if (!empty($atributosAgregadosNombres)) {
                            $detalleExtra[] = "Atributos agregados: " . implode(', ', $atributosAgregadosNombres);
                        }

                        foreach ($atributosAnterioresData as $attrAnt) {
                            $attrNuevo = null;
                            foreach ($atributosNuevosData as $attrNue) {
                                if ($attrNue['atributo_id'] == $attrAnt['atributo_id']) {
                                    $attrNuevo = $attrNue;
                                    break;
                                }
                            }
                            
                            if ($attrNuevo) {
                                // Obtener IDs de valores anteriores
                                $valoresAntIds = [];
                                $valoresAntNombres = [];
                                foreach ($attrAnt['valores'] as $valor) {
                                    $valoresAntIds[] = $valor['id'];
                                    $valoresAntNombres[$valor['id']] = $valor['nombre'];
                                }
                                
                                // Obtener IDs de valores nuevos
                                $valoresNueIds = [];
                                $valoresNueNombres = [];
                                
                                if (!empty($attrNuevo['valores'])) {
                                    foreach ($attrNuevo['valores'] as $valor) {
                                        if (is_array($valor) && isset($valor['id'])) {
                                            $valoresNueIds[] = $valor['id'];
                                            $valoresNueNombres[$valor['id']] = $valor['nombre'];
                                        } elseif (is_numeric($valor)) {
                                            $valoresNueIds[] = $valor;
                                        }
                                    }
                                }
                                
                                // Valores eliminados
                                $valoresEliminados = array_diff($valoresAntIds, $valoresNueIds);
                                $valoresEliminadosNombres = [];
                                foreach ($valoresEliminados as $id) {
                                    if (isset($valoresAntNombres[$id])) {
                                        $valoresEliminadosNombres[] = $valoresAntNombres[$id];
                                    }
                                }
                                
                                // Valores agregados
                                $valoresAgregados = array_diff($valoresNueIds, $valoresAntIds);
                                $valoresAgregadosNombres = [];
                                foreach ($valoresAgregados as $id) {
                                    if (isset($valoresNueNombres[$id])) {
                                        $valoresAgregadosNombres[] = $valoresNueNombres[$id];
                                    }
                                }
                                
                                if (!empty($valoresEliminadosNombres)) {
                                    $detalleExtra[] = "Atributo '{$attrAnt['atributo_nombre']}': valores eliminados - " . implode(', ', $valoresEliminadosNombres);
                                }
                                if (!empty($valoresAgregadosNombres)) {
                                    $detalleExtra[] = "Atributo '{$attrAnt['atributo_nombre']}': valores agregados - " . implode(', ', $valoresAgregadosNombres);
                                }
                            }
                        }

                        foreach ($atributosNuevosData as $attrNue) {
                            $existia = false;
                            foreach ($atributosAnterioresData as $ant) {
                                if ($ant['atributo_id'] == $attrNue['atributo_id']) {
                                    $existia = true;
                                    break;
                                }
                            }
                            
                            if (!$existia && !empty($attrNue['valores'])) {
                                $valoresAgregadosNombres = [];
                                foreach ($attrNue['valores'] as $valor) {
                                    if (is_array($valor) && isset($valor['nombre'])) {
                                        $valoresAgregadosNombres[] = $valor['nombre'];
                                    } elseif (is_string($valor)) {
                                        $valoresAgregadosNombres[] = $valor;
                                    } elseif (is_numeric($valor)) {
                                        $valorObj = AtributoTerm::find($valor);
                                        if ($valorObj) {
                                            $valoresAgregadosNombres[] = $valorObj->nombre;
                                        }
                                    }
                                }
                                
                                if (!empty($valoresAgregadosNombres)) {
                                    $detalleExtra[] = "Atributo NUEVO '{$attrNue['atributo_nombre']}': valores agregados - " . implode(', ', $valoresAgregadosNombres);
                                } else {
                                    $detalleExtra[] = "Atributo NUEVO agregado: '{$attrNue['atributo_nombre']}'";
                                }
                            }
                        }

                        if (!empty($upsellsAnterioresIds) || !empty($upsellsNuevosIds)) {
                            $upsellsEliminadosIds = array_diff($upsellsAnterioresIds, $upsellsNuevosIds);
                            $upsellsEliminadosNombres = [];
                            foreach ($upsellsEliminadosIds as $id) {
                                if (isset($upsellsAnterioresNombres[$id])) {
                                    $upsellsEliminadosNombres[] = $upsellsAnterioresNombres[$id] . " (ID: {$id})";
                                }
                            }
                            
                            $upsellsAgregadosIds = array_diff($upsellsNuevosIds, $upsellsAnterioresIds);
                            $upsellsAgregadosNombres = [];
                            foreach ($upsellsAgregadosIds as $id) {
                                if (isset($upsellsNuevosNombres[$id])) {
                                    $upsellsAgregadosNombres[] = $upsellsNuevosNombres[$id] . " (ID: {$id})";
                                }
                            }
                            
                            if (!empty($upsellsEliminadosNombres)) {
                                $detalleExtra[] = "Upsells eliminados: " . implode(', ', $upsellsEliminadosNombres);
                            }
                            if (!empty($upsellsAgregadosNombres)) {
                                $detalleExtra[] = "Upsells agregados: " . implode(', ', $upsellsAgregadosNombres);
                            }
                        }

                        if (!empty($crosssellsAnterioresIds) || !empty($crosssellsNuevosIds)) {
                            $crosssellsEliminadosIds = array_diff($crosssellsAnterioresIds, $crosssellsNuevosIds);
                            $crosssellsEliminadosNombres = [];
                            foreach ($crosssellsEliminadosIds as $id) {
                                if (isset($crosssellsAnterioresNombres[$id])) {
                                    $crosssellsEliminadosNombres[] = $crosssellsAnterioresNombres[$id] . " (ID: {$id})";
                                }
                            }
                            
                            $crosssellsAgregadosIds = array_diff($crosssellsNuevosIds, $crosssellsAnterioresIds);
                            $crosssellsAgregadosNombres = [];
                            foreach ($crosssellsAgregadosIds as $id) {
                                if (isset($crosssellsNuevosNombres[$id])) {
                                    $crosssellsAgregadosNombres[] = $crosssellsNuevosNombres[$id] . " (ID: {$id})";
                                }
                            }
                            
                            if (!empty($crosssellsEliminadosNombres)) {
                                $detalleExtra[] = "Cross-sells eliminados: " . implode(', ', $crosssellsEliminadosNombres);
                            }
                            if (!empty($crosssellsAgregadosNombres)) {
                                $detalleExtra[] = "Cross-sells agregados: " . implode(', ', $crosssellsAgregadosNombres);
                            }
                        }
                        
                        $this->registrarAuditoria(
                            'Actualizar',               
                            'productos',                
                            $producto->id,              
                            $producto->nombre,          
                            $valoresAnteriores,         
                            $productoData,             
                            !empty($detalleExtra) ? implode(' | ', $detalleExtra) : null  
                        );

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Producto actualizado correctamente';

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al actualizar el producto: ' . $e->getMessage();
                    }
                    break;
                case 'Editar_Agrupado':
                    DB::beginTransaction();
                    
                    try {
                        $id = $request->input('id');
                        $producto = Producto::find($id);
                        
                        if (!$producto) {
                            $data->respuesta = 'error';
                            $data->mensaje = 'Producto no encontrado';
                            break;
                        }
          
                        $valoresAnteriores = [
                            'nombre' => $producto->nombre,
                            'descripcion' => $producto->descripcion,
                            'descripcion_completa' => $producto->descripcion_completa,
                            'tipo_producto' => $producto->tipo_producto,
                            'id_subCategorias' => $producto->id_subCategorias,
                            'sku' => $producto->sku,
                            'marca' => $producto->marca,
                            'precio_regular' => $producto->precio_regular,
                            'precio_rebajado' => $producto->precio_rebajado,
                            'fecha_inicio_rebaja' => $producto->fecha_inicio_rebaja,
                            'fecha_fin_rebaja' => $producto->fecha_fin_rebaja,
                            'estado' => $producto->estado,
                            'gestion_inventario' => $producto->gestion_inventario,
                            'estado_inventario' => $producto->estado_inventario,
                            'stock' => $producto->stock,
                            'vendido_individualmente' => $producto->vendido_individualmente,
                            'backorders' => $producto->backorders,
                            'permite_valoraciones' => $producto->permite_valoraciones,
                            'peso' => $producto->peso,
                            'peso_unidad' => $producto->peso_unidad,
                            'longitud' => $producto->longitud,
                            'anchura' => $producto->anchura,
                            'altura' => $producto->altura,
                            'nota_interna' => $producto->nota_interna
                        ];

                        $etiquetasAnteriores = $producto->etiquetas->map(function($etiqueta) {
                            return [
                                'id' => $etiqueta->id,
                                'nombre' => $etiqueta->nombre
                            ];
                        })->toArray();
                        $etiquetasAnterioresIds = array_column($etiquetasAnteriores, 'id');
                        $etiquetasAnterioresNombres = [];
                        foreach ($etiquetasAnteriores as $et) {
                            $etiquetasAnterioresNombres[$et['id']] = $et['nombre'];
                        }

                        $atributosAnteriores = ProductoAtributo::where('producto_id', $producto->id)
                            ->with(['atributo', 'valores'])
                            ->get();
                        
                        $atributosAnterioresData = [];
                        foreach ($atributosAnteriores as $pa) {
                            $atributosAnterioresData[] = [
                                'atributo_id' => $pa->atributo_id,
                                'atributo_nombre' => $pa->atributo ? $pa->atributo->nombre : 'Desconocido',
                                'visible' => $pa->visible,
                                'variacion' => $pa->variacion,
                                'valores' => $pa->valores->map(function($valor) {
                                    return [
                                        'id' => $valor->id,
                                        'nombre' => $valor->nombre
                                    ];
                                })->toArray()
                            ];
                        }
            
                        $descripcionLimpia = preg_replace('/<img[^>]*>/i', '', $request->input('descripcion_larga', ''));
                        $productoData = [
                            'nombre' => $request->input('nombre'),
                            'descripcion' => $request->input('descripcion'),
                            'descripcion_completa' => $descripcionLimpia,
                            'tipo_producto' => $request->input('tipo_producto', 'agrupado'),
                            'id_subCategorias' => $request->input('subcategoria_id'),
                            'sku' => $request->input('sku'),
                            'marca' => $request->input('marca'),
                            'estado' => $request->input('estado'),
                            'permite_valoraciones' => $request->boolean('permite_valoraciones'),
                            'nota_interna' => $request->input('nota_interna'),
                            'gestion_inventario' => false,
                            'estado_inventario' => 'existe',
                            'stock' => 0,
                            'precio_regular' => 0,
                            'precio_rebajado' => null,
                            'fecha_inicio_rebaja' => null,
                            'fecha_fin_rebaja' => null,
                            'backorders' => false
                        ];

                        $producto->update($productoData);

                        // --- Carpeta destino para imágenes ---
                        $carpetaDestino = public_path('image_producto');
                        if (!File::exists($carpetaDestino)) {
                            File::makeDirectory($carpetaDestino, 0755, true);
                        }

                        $fecha = date('Ymd_His');

                        // --- 1. MINIATURA ---
                        $miniaturaCambio = false;
                        if ($request->input('eliminar_miniatura') === 'true' && $producto->imagen_miniatura) {
                            $rutaMiniatura = public_path($producto->imagen_miniatura);
                            if (File::exists($rutaMiniatura)) {
                                File::delete($rutaMiniatura);
                            }
                            $producto->update(['imagen_miniatura' => null]);
                            $miniaturaCambio = true;
                        }

                        if ($request->hasFile('imagen_miniatura')) {
                            if ($producto->imagen_miniatura) {
                                $rutaAnterior = public_path($producto->imagen_miniatura);
                                if (File::exists($rutaAnterior)) {
                                    File::delete($rutaAnterior);
                                }
                            }
                            
                            $file = $request->file('imagen_miniatura');
                            $nombre = 'mini_' . Str::random(5) . "_$fecha." . $file->getClientOriginalExtension();
                            $file->move($carpetaDestino, $nombre);
                            $producto->update(['imagen_miniatura' => "image_producto/$nombre"]);
                            $miniaturaCambio = true;
                        }

                        // --- 2. IMÁGENES PRINCIPALES ---
                        $imagenesEliminadas = 0;
                        $imagenesAgregadas = 0;
                        if ($request->has('imagenes_eliminar')) {
                            $idsEliminar = json_decode($request->input('imagenes_eliminar'), true);
                            if (is_array($idsEliminar) && count($idsEliminar) > 0) {
                                $imagenesEliminar = ProductoImagen::whereIn('id', $idsEliminar)->get();
                                foreach ($imagenesEliminar as $img) {
                                    $ruta = public_path($img->imagen_path);
                                    if (File::exists($ruta)) {
                                        File::delete($ruta);
                                    }
                                    $img->delete();
                                    $imagenesEliminadas++;
                                }
                            }
                        }

                        if ($request->hasFile('imagenes')) {
                            foreach ($request->file('imagenes') as $i => $imagen) {
                                $nombre = 'img_' . ($i + 1) . '_' . Str::random(5) . "_$fecha." . $imagen->getClientOriginalExtension();
                                $imagen->move($carpetaDestino, $nombre);

                                ProductoImagen::create([
                                    'producto_id' => $producto->id,
                                    'imagen_path' => "image_producto/$nombre"
                                ]);
                                $imagenesAgregadas++;
                            }
                        }

                        // --- 3. ETIQUETAS ---
                        $etiquetasNuevasIds = [];
                        $etiquetasNuevasNombres = [];
                        if ($request->has('etiquetas')) {
                            $etiquetasNuevasIds = json_decode($request->input('etiquetas'), true);
                            if (!empty($etiquetasNuevasIds)) {
                                $etiquetasNuevas = Etiqueta::whereIn('id', $etiquetasNuevasIds)->get();
                                foreach ($etiquetasNuevas as $et) {
                                    $etiquetasNuevasNombres[$et->id] = $et->nombre;
                                }
                            }
                            $producto->etiquetas()->sync($etiquetasNuevasIds);
                        } else {
                            $producto->etiquetas()->sync([]);
                        }

                        // --- 4. ATRIBUTOS ---
                        $atributosNuevosData = [];

                        if ($request->has('atributos')) {
                            $atributosData = json_decode($request->input('atributos'), true);
                            
                            // Guardar nuevos atributos para auditoría
                            foreach ($atributosData as $attr) {
                                $atributo = Atributo::find($attr['atributo_id']);
                                $valoresInfo = [];
                                
                                if (!empty($attr['valores'])) {
                                    $valores = AtributoTerm::whereIn('id', $attr['valores'])->get();
                                    foreach ($valores as $valor) {
                                        $valoresInfo[] = [
                                            'id' => $valor->id,
                                            'nombre' => $valor->nombre
                                        ];
                                    }
                                }
                                
                                $atributosNuevosData[] = [
                                    'atributo_id' => $attr['atributo_id'],
                                    'atributo_nombre' => $atributo ? $atributo->nombre : 'Desconocido',
                                    'visible' => $attr['visible'] ?? true,
                                    'variacion' => $attr['variacion'] ?? false,
                                    'valores' => $valoresInfo
                                ];
                            }
                            
                            // Eliminar atributos actuales
                            ProductoAtributo::where('producto_id', $producto->id)->delete();
                            
                            foreach ($atributosData as $atributoData) {
                                $productoAtributo = ProductoAtributo::create([
                                    'producto_id' => $producto->id,
                                    'atributo_id' => $atributoData['atributo_id'],
                                    'visible' => $atributoData['visible'] ?? true,
                                    'variacion' => $atributoData['variacion'] ?? false
                                ]);

                                if (!empty($atributoData['valores'])) {
                                    $productoAtributo->valores()->sync($atributoData['valores']);
                                }
                            }
                        }

                        // --- 5. PRODUCTOS AGRUPADOS (hijos) ---
                        $hijosAnteriores = ProductoAgrupado::where('producto_padre_id', $producto->id)
                            ->with('productoHijo')  
                            ->get();
                            
                        $hijosNuevos = [];
                        $hijosNuevosIds = [];
                        ProductoAgrupado::where('producto_padre_id', $producto->id)->delete();
                            
                        if ($request->has('relacionados')) {
                            $hijosIds = json_decode($request->input('relacionados'), true);        
                            if (is_array($hijosIds)) {
                                foreach ($hijosIds as $hijoId) {
                                    ProductoAgrupado::create([
                                        'producto_padre_id' => $producto->id,
                                        'producto_hijo_id' => $hijoId
                                    ]);
                                    $hijosNuevosIds[] = $hijoId;
                                    $productoHijo = Producto::find($hijoId);
                                    if ($productoHijo) {
                                        $hijosNuevos[] = [
                                            'id' => $hijoId,
                                            'nombre' => $productoHijo->nombre
                                        ];
                                    }
                                }
                            }
                        }

                        // --- 6. PRODUCTOS RELACIONADOS (Crosssells) ---
                        $crosssellsAnteriores = ProductoRelacionado::where('producto_id', $producto->id)
                            ->where('tipo', 'crosssell')
                            ->with('relacionado')  
                            ->get();

                        $crosssellsAnterioresIds = $crosssellsAnteriores->pluck('producto_relacionado_id')->toArray();
                        $crosssellsAnterioresNombres = [];
                        foreach ($crosssellsAnteriores as $cross) {
                            if ($cross->relacionado) {
                                $crosssellsAnterioresNombres[$cross->producto_relacionado_id] = $cross->relacionado->nombre;
                            }
                        }
                        ProductoRelacionado::where('producto_id', $producto->id)->delete();
                        $crosssellsNuevosIds = [];
                        $crosssellsNuevosNombres = [];
                        if ($request->has('crosssells')) {
                            $crosssellsIds = json_decode($request->input('crosssells'), true);
                            if (is_array($crosssellsIds)) {
                                foreach ($crosssellsIds as $relId) {
                                    ProductoRelacionado::create([
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => 'crosssell'
                                    ]);
                                    $crosssellsNuevosIds[] = $relId;
                                    $productoRel = Producto::find($relId);
                                    if ($productoRel) {
                                        $crosssellsNuevosNombres[$relId] = $productoRel->nombre;
                                    }
                                }
                            }
                        }

                        DB::commit();

                        $detalleExtra = [];

                        if ($miniaturaCambio) {
                            $detalleExtra[] = "Miniatura actualizada";
                        }
                        if ($imagenesEliminadas > 0) {
                            $detalleExtra[] = "{$imagenesEliminadas} imagen(es) eliminada(s)";
                        }
                        if ($imagenesAgregadas > 0) {
                            $detalleExtra[] = "{$imagenesAgregadas} imagen(es) agregada(s)";
                        }

                        $etiquetasEliminadasIds = array_diff($etiquetasAnterioresIds, $etiquetasNuevasIds);
                        $etiquetasEliminadasNombres = [];
                        foreach ($etiquetasEliminadasIds as $id) {
                            if (isset($etiquetasAnterioresNombres[$id])) {
                                $etiquetasEliminadasNombres[] = $etiquetasAnterioresNombres[$id];
                            }
                        }
                        
                        $etiquetasAgregadasIds = array_diff($etiquetasNuevasIds, $etiquetasAnterioresIds);
                        $etiquetasAgregadasNombres = [];
                        foreach ($etiquetasAgregadasIds as $id) {
                            if (isset($etiquetasNuevasNombres[$id])) {
                                $etiquetasAgregadasNombres[] = $etiquetasNuevasNombres[$id];
                            }
                        }
                        
                        if (!empty($etiquetasEliminadasNombres)) {
                            $detalleExtra[] = "Etiquetas eliminadas: " . implode(', ', $etiquetasEliminadasNombres);
                        }
                        if (!empty($etiquetasAgregadasNombres)) {
                            $detalleExtra[] = "Etiquetas agregadas: " . implode(', ', $etiquetasAgregadasNombres);
                        }

                        $atributosAnterioresIds = array_column($atributosAnterioresData, 'atributo_id');
                        $atributosNuevosIds = array_column($atributosNuevosData, 'atributo_id');
                        $atributosEliminadosIds = array_diff($atributosAnterioresIds, $atributosNuevosIds);
                        $atributosEliminadosNombres = [];
                        foreach ($atributosAnterioresData as $attr) {
                            if (in_array($attr['atributo_id'], $atributosEliminadosIds)) {
                                $atributosEliminadosNombres[] = $attr['atributo_nombre'];
                            }
                        }

                        $atributosAgregadosIds = array_diff($atributosNuevosIds, $atributosAnterioresIds);
                        $atributosAgregadosNombres = [];
                        foreach ($atributosNuevosData as $attr) {
                            if (in_array($attr['atributo_id'], $atributosAgregadosIds)) {
                                $atributosAgregadosNombres[] = $attr['atributo_nombre'];
                            }
                        }

                        if (!empty($atributosEliminadosNombres)) {
                            $detalleExtra[] = "Atributos eliminados: " . implode(', ', $atributosEliminadosNombres);
                        }
                        if (!empty($atributosAgregadosNombres)) {
                            $detalleExtra[] = "Atributos agregados: " . implode(', ', $atributosAgregadosNombres);
                        }

                        foreach ($atributosAnterioresData as $attrAnt) {
                            $attrNuevo = null;
                            foreach ($atributosNuevosData as $attrNue) {
                                if ($attrNue['atributo_id'] == $attrAnt['atributo_id']) {
                                    $attrNuevo = $attrNue;
                                    break;
                                }
                            }
                            
                            if ($attrNuevo) {
                                $valoresAntIds = [];
                                $valoresAntNombres = [];
                                foreach ($attrAnt['valores'] as $valor) {
                                    $valoresAntIds[] = $valor['id'];
                                    $valoresAntNombres[$valor['id']] = $valor['nombre'];
                                }
                                
                                $valoresNueIds = [];
                                $valoresNueNombres = [];
                                if (!empty($attrNuevo['valores'])) {
                                    foreach ($attrNuevo['valores'] as $valor) {
                                        if (is_array($valor) && isset($valor['id'])) {
                                            $valoresNueIds[] = $valor['id'];
                                            $valoresNueNombres[$valor['id']] = $valor['nombre'];
                                        } elseif (is_numeric($valor)) {
                                            $valoresNueIds[] = $valor;
                                        }
                                    }
                                }
                                
                                $valoresEliminados = array_diff($valoresAntIds, $valoresNueIds);
                                $valoresEliminadosNombres = [];
                                foreach ($valoresEliminados as $id) {
                                    if (isset($valoresAntNombres[$id])) {
                                        $valoresEliminadosNombres[] = $valoresAntNombres[$id];
                                    }
                                }
                                
                                $valoresAgregados = array_diff($valoresNueIds, $valoresAntIds);
                                $valoresAgregadosNombres = [];
                                foreach ($valoresAgregados as $id) {
                                    if (isset($valoresNueNombres[$id])) {
                                        $valoresAgregadosNombres[] = $valoresNueNombres[$id];
                                    }
                                }
                                
                                if (!empty($valoresEliminadosNombres)) {
                                    $detalleExtra[] = "Atributo '{$attrAnt['atributo_nombre']}': valores eliminados - " . implode(', ', $valoresEliminadosNombres);
                                }
                                if (!empty($valoresAgregadosNombres)) {
                                    $detalleExtra[] = "Atributo '{$attrAnt['atributo_nombre']}': valores agregados - " . implode(', ', $valoresAgregadosNombres);
                                }
                            }
                        }

                        foreach ($atributosNuevosData as $attrNue) {
                            $existia = false;
                            foreach ($atributosAnterioresData as $ant) {
                                if ($ant['atributo_id'] == $attrNue['atributo_id']) {
                                    $existia = true;
                                    break;
                                }
                            }
                            
                            if (!$existia && !empty($attrNue['valores'])) {
                                $valoresAgregadosNombres = [];
                                foreach ($attrNue['valores'] as $valor) {
                                    if (is_array($valor) && isset($valor['nombre'])) {
                                        $valoresAgregadosNombres[] = $valor['nombre'];
                                    } elseif (is_string($valor)) {
                                        $valoresAgregadosNombres[] = $valor;
                                    } elseif (is_numeric($valor)) {
                                        $valorObj = AtributoTerm::find($valor);
                                        if ($valorObj) {
                                            $valoresAgregadosNombres[] = $valorObj->nombre;
                                        }
                                    }
                                }
                                
                                if (!empty($valoresAgregadosNombres)) {
                                    $detalleExtra[] = "Atributo NUEVO '{$attrNue['atributo_nombre']}': valores agregados - " . implode(', ', $valoresAgregadosNombres);
                                } else {
                                    $detalleExtra[] = "Atributo NUEVO agregado: '{$attrNue['atributo_nombre']}'";
                                }
                            }
                        }

                        if ($hijosAnteriores->count() > 0 || !empty($hijosNuevos)) {
                            $hijosAnterioresIds = $hijosAnteriores->pluck('producto_hijo_id')->toArray();
                            $hijosAnterioresNombres = [];
                            foreach ($hijosAnteriores as $hijo) {
                                if ($hijo->productoHijo) {
                                    $hijosAnterioresNombres[$hijo->producto_hijo_id] = $hijo->productoHijo->nombre;
                                }
                            }
                        
                            $hijosNuevosIds = array_column($hijosNuevos, 'id');
                            $hijosNuevosNombres = [];
                            foreach ($hijosNuevos as $hijo) {
                                $hijosNuevosNombres[$hijo['id']] = $hijo['nombre'];
                            }
                            $hijosEliminadosIds = array_diff($hijosAnterioresIds, $hijosNuevosIds);
                            $hijosEliminadosNombres = [];
                            foreach ($hijosEliminadosIds as $id) {
                                if (isset($hijosAnterioresNombres[$id])) {
                                    $hijosEliminadosNombres[] = $hijosAnterioresNombres[$id] . " (ID: {$id})";
                                }
                            }
                            $hijosAgregadosIds = array_diff($hijosNuevosIds, $hijosAnterioresIds);
                            $hijosAgregadosNombres = [];
                            foreach ($hijosAgregadosIds as $id) {
                                if (isset($hijosNuevosNombres[$id])) {
                                    $hijosAgregadosNombres[] = $hijosNuevosNombres[$id] . " (ID: {$id})";
                                }
                            }
                            
                            if (!empty($hijosEliminadosNombres)) {
                                $detalleExtra[] = "Productos agrupados eliminados: " . implode(', ', $hijosEliminadosNombres);
                            }
                            if (!empty($hijosAgregadosNombres)) {
                                $detalleExtra[] = "Productos agrupados agregados: " . implode(', ', $hijosAgregadosNombres);
                            }
                        }

                        if (!empty($crosssellsAnterioresIds) || !empty($crosssellsNuevosIds)) {
                            $crosssellsEliminadosIds = array_diff($crosssellsAnterioresIds, $crosssellsNuevosIds);
                            $crosssellsEliminadosNombres = [];
                            foreach ($crosssellsEliminadosIds as $id) {
                                if (isset($crosssellsAnterioresNombres[$id])) {
                                    $crosssellsEliminadosNombres[] = $crosssellsAnterioresNombres[$id] . " (ID: {$id})";
                                }
                            }
                            $crosssellsAgregadosIds = array_diff($crosssellsNuevosIds, $crosssellsAnterioresIds);
                            $crosssellsAgregadosNombres = [];
                            foreach ($crosssellsAgregadosIds as $id) {
                                if (isset($crosssellsNuevosNombres[$id])) {
                                    $crosssellsAgregadosNombres[] = $crosssellsNuevosNombres[$id] . " (ID: {$id})";
                                }
                            }
                            
                            if (!empty($crosssellsEliminadosNombres)) {
                                $detalleExtra[] = "Cross-sells eliminados: " . implode(', ', $crosssellsEliminadosNombres);
                            }
                            if (!empty($crosssellsAgregadosNombres)) {
                                $detalleExtra[] = "Cross-sells agregados: " . implode(', ', $crosssellsAgregadosNombres);
                            }
                        }
                        
                        $this->registrarAuditoria(
                            'Actualizar',               
                            'productos',                
                            $producto->id,              
                            $producto->nombre,          
                            $valoresAnteriores,         
                            $productoData,             
                            !empty($detalleExtra) ? implode(' | ', $detalleExtra) : null  
                        );

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Producto actualizado correctamente';

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al actualizar el producto: ' . $e->getMessage();
                    }
                    break;
                case 'Editar_Variable':
                    DB::beginTransaction();
                    try {
                        $id = $request->input('id');
                        $producto = Producto::findOrFail($id);

                        $valoresAnteriores = [
                            'nombre' => $producto->nombre,
                            'estado' => $producto->estado,
                            'sku' => $producto->sku
                        ];

                        $valoresAnteriores = [
                            'nombre' => $producto->nombre,
                            'descripcion' => $producto->descripcion,
                            'descripcion_completa' => $producto->descripcion_completa,
                            'tipo_producto' => $producto->tipo_producto,
                            'id_subCategorias' => $producto->id_subCategorias,
                            'sku' => $producto->sku,
                            'marca' => $producto->marca,
                            'estado' => $producto->estado,
                            'gestion_inventario' => $producto->gestion_inventario,
                            'stock' => $producto->stock,
                            'vendido_individualmente' => $producto->vendido_individualmente,
                            'permite_valoraciones' => $producto->permite_valoraciones,
                            'peso' => $producto->peso,
                            'peso_unidad' => $producto->peso_unidad,
                            'longitud' => $producto->longitud,
                            'anchura' => $producto->anchura,
                            'altura' => $producto->altura,
                            'nota_interna' => $producto->nota_interna
                        ];

                        $productoData = [
                            'nombre' => $request->input('nombre'),
                            'descripcion' => $request->input('descripcion'),
                            'descripcion_completa' => preg_replace('/<img[^>]*>/i', '', $request->input('descripcion_larga', '')),
                            'id_subCategorias' => $request->input('subcategoria_id'),
                            'sku' => $request->input('sku'),
                            'marca' => $request->input('marca'),
                            'estado' => $request->input('estado'),
                            'gestion_inventario' => $request->boolean('gestion_inventario'),
                            'stock' => intval($request->input('stock') ?? 0),
                            'vendido_individualmente' => $request->boolean('vendido_individualmente'),
                            'permite_valoraciones' => $request->boolean('permite_valoraciones'),
                            'peso' => $request->input('peso'),
                            'peso_unidad' => $request->input('peso_unidad'),
                            'longitud' => $request->input('longitud'),
                            'anchura' => $request->input('anchura'),
                            'altura' => $request->input('altura'),
                            'nota_interna' => $request->input('nota_interna'),
                            'precio_regular' => 0,
                            'precio_rebajado' => null,
                            'fecha_inicio_rebaja' => null,
                            'fecha_fin_rebaja' => null,
                        ];

                        $etiquetasAnteriores = $producto->etiquetas->map(function($etiqueta) {
                            return [
                                'id' => $etiqueta->id,
                                'nombre' => $etiqueta->nombre
                            ];
                        })->toArray();
                        $etiquetasAnterioresIds = array_column($etiquetasAnteriores, 'id');
                        $etiquetasAnterioresNombres = [];
                        foreach ($etiquetasAnteriores as $et) {
                            $etiquetasAnterioresNombres[$et['id']] = $et['nombre'];
                        }

                        $atributosAnteriores = ProductoAtributo::where('producto_id', $producto->id)
                            ->with(['atributo', 'valores'])
                            ->get();
                        
                        $atributosAnterioresData = [];
                        foreach ($atributosAnteriores as $pa) {
                            $atributosAnterioresData[] = [
                                'atributo_id' => $pa->atributo_id,
                                'atributo_nombre' => $pa->atributo ? $pa->atributo->nombre : 'Desconocido',
                                'visible' => $pa->visible,
                                'variacion' => $pa->variacion,
                                'valores' => $pa->valores->map(function($valor) {
                                    return [
                                        'id' => $valor->id,
                                        'nombre' => $valor->nombre
                                    ];
                                })->toArray()
                            ];
                        }
                        
                        $producto->update($productoData);
                        $upsellsAnteriores = ProductoRelacionado::where('producto_id', $producto->id)
                            ->where('tipo', 'upsell')
                            ->with('relacionado')
                            ->get();
                        
                        $crosssellsAnteriores = ProductoRelacionado::where('producto_id', $producto->id)
                            ->where('tipo', 'crosssell')
                            ->with('relacionado')
                            ->get();
                        
                        $upsellsAnterioresIds = $upsellsAnteriores->pluck('producto_relacionado_id')->toArray();
                        $upsellsAnterioresNombres = [];
                        foreach ($upsellsAnteriores as $upsell) {
                            if ($upsell->relacionado) {
                                $upsellsAnterioresNombres[$upsell->producto_relacionado_id] = $upsell->relacionado->nombre;
                            }
                        }
                        
                        $crosssellsAnterioresIds = $crosssellsAnteriores->pluck('producto_relacionado_id')->toArray();
                        $crosssellsAnterioresNombres = [];
                        foreach ($crosssellsAnteriores as $crosssell) {
                            if ($crosssell->relacionado) {
                                $crosssellsAnterioresNombres[$crosssell->producto_relacionado_id] = $crosssell->relacionado->nombre;
                            }
                        }
                        
                        $variacionesAnteriores = ProductoVariacion::where('producto_padre_id', $producto->id)->get();
                        $cantidadVariacionesAnteriores = $variacionesAnteriores->count();
                        $miniaturaExistia = !is_null($producto->imagen_miniatura);
                        $cantidadImagenesAnteriores = $producto->imagenes->count();

                        $fecha = date('Ymd_His');
                        $carpetaDestino = public_path('image_producto');
                        if (!File::exists($carpetaDestino)) File::makeDirectory($carpetaDestino, 0755, true);

                        $this->sincronizarImagenes($producto, $request, $fecha, $carpetaDestino);
                        
                        $etiquetasNuevasIds = [];
                        $etiquetasNuevasNombres = [];
                        if ($request->has('etiquetas')) {
                            $etiquetasNuevasIds = json_decode($request->input('etiquetas'), true);
                            if (!empty($etiquetasNuevasIds)) {
                                $etiquetasNuevas = Etiqueta::whereIn('id', $etiquetasNuevasIds)->get();
                                foreach ($etiquetasNuevas as $et) {
                                    $etiquetasNuevasNombres[$et->id] = $et->nombre;
                                }
                            }
                            $producto->etiquetas()->sync($etiquetasNuevasIds);
                        }
                        
                        // Guardar nuevos atributos para auditoría ANTES de sync
                        $atributosNuevosData = [];
                        if ($request->has('atributos')) {
                            $atributosData = json_decode($request->input('atributos'), true);
                            
                            // Guardar nuevos atributos para auditoría
                            foreach ($atributosData as $attr) {
                                $atributo = Atributo::find($attr['atributo_id']);
                                $valoresInfo = [];
                                
                                if (!empty($attr['valores'])) {
                                    $valores = AtributoTerm::whereIn('id', $attr['valores'])->get();
                                    foreach ($valores as $valor) {
                                        $valoresInfo[] = [
                                            'id' => $valor->id,
                                            'nombre' => $valor->nombre
                                        ];
                                    }
                                }
                                
                                $atributosNuevosData[] = [
                                    'atributo_id' => $attr['atributo_id'],
                                    'atributo_nombre' => $atributo ? $atributo->nombre : 'Desconocido',
                                    'visible' => $attr['visible'] ?? true,
                                    'variacion' => $attr['variacion'] ?? false,
                                    'valores' => $valoresInfo
                                ];
                            }
                            
                            // Sincronizar atributos (función existente)
                            $this->syncProductoAtributos($producto, $atributosData);
                        }

                        $variacionesAnterioresDetalle = [];
                        foreach ($variacionesAnteriores as $variacion) {
                            // Obtener atributos de la variación (términos)
                            $atributosVariacion = $variacion->atributos()->get()->map(function($attr) {
                                return [
                                    'id' => $attr->id,
                                    'nombre' => $attr->nombre,
                                    'atributo_id' => $attr->atributo_id
                                ];
                            })->toArray();
                            
                            // Obtener imágenes de la variación
                            $imagenesVariacion = $variacion->imagenes()->get()->map(function($img) {
                                return [
                                    'id' => $img->id,
                                    'path' => $img->imagen_path
                                ];
                            })->toArray();
                            
                            $variacionesAnterioresDetalle[$variacion->id] = [
                                'id' => $variacion->id,
                                'sku' => $variacion->sku,
                                'precio_regular' => $variacion->precio_regular,
                                'precio_rebajado' => $variacion->precio_rebajado,
                                'stock' => $variacion->stock,
                                'fecha_inicio_rebaja' => $variacion->fecha_inicio_rebaja,
                                'fecha_fin_rebaja' => $variacion->fecha_fin_rebaja,
                                'peso' => $variacion->peso,
                                'peso_unidad' => $variacion->peso_unidad,
                                'longitud' => $variacion->longitud,
                                'anchura' => $variacion->anchura,
                                'altura' => $variacion->altura,
                                'descripcion' => $variacion->descripcion,
                                'backorders' => $variacion->backorders,
                                'atributos' => $atributosVariacion,
                                'imagenes' => $imagenesVariacion
                            ];
                        }
                        
                        if ($request->has('variaciones')) {
                            $variacionesData = json_decode($request->input('variaciones'), true);
                            $this->syncVariaciones($producto, $variacionesData, $request);
                        }
                        
                        $upsellsNuevosIds = [];
                        $crosssellsNuevosIds = [];
                        ProductoRelacionado::where('producto_id', $producto->id)->delete();
                        foreach (['upsells', 'crosssells'] as $tipo) {
                            if ($request->has($tipo)) {
                                $ids = json_decode($request->input($tipo), true);
                                foreach ($ids as $relId) {
                                    ProductoRelacionado::create([
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => rtrim($tipo, 's'),
                                    ]);
                                    if ($tipo === 'upsells') {
                                        $upsellsNuevosIds[] = $relId;
                                    } else {
                                        $crosssellsNuevosIds[] = $relId;
                                    }
                                }
                            }
                        }
                        
                        DB::commit();

                        $producto->refresh();
        
                        $variacionesNuevas = ProductoVariacion::where('producto_padre_id', $producto->id)->count();
                        $miniaturaExisteAhora = !is_null($producto->imagen_miniatura);
                        $cantidadImagenesNuevas = $producto->imagenes->count();
                        $detalleExtra = [];
                        
                        if ($miniaturaExistia != $miniaturaExisteAhora) {
                            $detalleExtra[] = "Miniatura " . ($miniaturaExisteAhora ? "agregada/cambiada" : "eliminada");
                        }
                        
                        if ($cantidadImagenesAnteriores != $cantidadImagenesNuevas) {
                            $detalleExtra[] = "Imágenes principales: {$cantidadImagenesAnteriores} → {$cantidadImagenesNuevas}";
                        }
                        
                        if ($cantidadVariacionesAnteriores != $variacionesNuevas) {
                            $detalleExtra[] = "Variaciones: {$cantidadVariacionesAnteriores} → {$variacionesNuevas}";
                        }

                        $etiquetasEliminadasIds = array_diff($etiquetasAnterioresIds, $etiquetasNuevasIds);
                        $etiquetasEliminadasNombres = [];
                        foreach ($etiquetasEliminadasIds as $id) {
                            if (isset($etiquetasAnterioresNombres[$id])) {
                                $etiquetasEliminadasNombres[] = $etiquetasAnterioresNombres[$id];
                            }
                        }
                        
                        $etiquetasAgregadasIds = array_diff($etiquetasNuevasIds, $etiquetasAnterioresIds);
                        $etiquetasAgregadasNombres = [];
                        foreach ($etiquetasAgregadasIds as $id) {
                            if (isset($etiquetasNuevasNombres[$id])) {
                                $etiquetasAgregadasNombres[] = $etiquetasNuevasNombres[$id];
                            }
                        }
                        
                        if (!empty($etiquetasEliminadasNombres)) {
                            $detalleExtra[] = "Etiquetas eliminadas: " . implode(', ', $etiquetasEliminadasNombres);
                        }
                        if (!empty($etiquetasAgregadasNombres)) {
                            $detalleExtra[] = "Etiquetas agregadas: " . implode(', ', $etiquetasAgregadasNombres);
                        }
                        
                        $atributosAnterioresIds = array_column($atributosAnterioresData, 'atributo_id');
                        $atributosNuevosIds = array_column($atributosNuevosData, 'atributo_id');
                        $atributosEliminadosIds = array_diff($atributosAnterioresIds, $atributosNuevosIds);
                        $atributosEliminadosNombres = [];
                        foreach ($atributosAnterioresData as $attr) {
                            if (in_array($attr['atributo_id'], $atributosEliminadosIds)) {
                                $atributosEliminadosNombres[] = $attr['atributo_nombre'];
                            }
                        }

                        $atributosAgregadosIds = array_diff($atributosNuevosIds, $atributosAnterioresIds);
                        $atributosAgregadosNombres = [];
                        foreach ($atributosNuevosData as $attr) {
                            if (in_array($attr['atributo_id'], $atributosAgregadosIds)) {
                                $atributosAgregadosNombres[] = $attr['atributo_nombre'];
                            }
                        }

                        if (!empty($atributosEliminadosNombres)) {
                            $detalleExtra[] = "Atributos eliminados: " . implode(', ', $atributosEliminadosNombres);
                        }
                        if (!empty($atributosAgregadosNombres)) {
                            $detalleExtra[] = "Atributos agregados: " . implode(', ', $atributosAgregadosNombres);
                        }
                        
                        foreach ($atributosAnterioresData as $attrAnt) {
                            $attrNuevo = null;
                            foreach ($atributosNuevosData as $attrNue) {
                                if ($attrNue['atributo_id'] == $attrAnt['atributo_id']) {
                                    $attrNuevo = $attrNue;
                                    break;
                                }
                            }
                            
                            if ($attrNuevo) {
                                $valoresAntIds = [];
                                $valoresAntNombres = [];
                                foreach ($attrAnt['valores'] as $valor) {
                                    $valoresAntIds[] = $valor['id'];
                                    $valoresAntNombres[$valor['id']] = $valor['nombre'];
                                }
                                
                                $valoresNueIds = [];
                                $valoresNueNombres = [];
                                if (!empty($attrNuevo['valores'])) {
                                    foreach ($attrNuevo['valores'] as $valor) {
                                        if (is_array($valor) && isset($valor['id'])) {
                                            $valoresNueIds[] = $valor['id'];
                                            $valoresNueNombres[$valor['id']] = $valor['nombre'];
                                        } elseif (is_numeric($valor)) {
                                            $valoresNueIds[] = $valor;
                                        }
                                    }
                                }
                                
                                $valoresEliminados = array_diff($valoresAntIds, $valoresNueIds);
                                $valoresEliminadosNombres = [];
                                foreach ($valoresEliminados as $id) {
                                    if (isset($valoresAntNombres[$id])) {
                                        $valoresEliminadosNombres[] = $valoresAntNombres[$id];
                                    }
                                }
                                
                                $valoresAgregados = array_diff($valoresNueIds, $valoresAntIds);
                                $valoresAgregadosNombres = [];
                                foreach ($valoresAgregados as $id) {
                                    if (isset($valoresNueNombres[$id])) {
                                        $valoresAgregadosNombres[] = $valoresNueNombres[$id];
                                    }
                                }
                                
                                if (!empty($valoresEliminadosNombres)) {
                                    $detalleExtra[] = "Atributo '{$attrAnt['atributo_nombre']}': valores eliminados - " . implode(', ', $valoresEliminadosNombres);
                                }
                                if (!empty($valoresAgregadosNombres)) {
                                    $detalleExtra[] = "Atributo '{$attrAnt['atributo_nombre']}': valores agregados - " . implode(', ', $valoresAgregadosNombres);
                                }
                            }
                        }

                        foreach ($atributosNuevosData as $attrNue) {
                            $existia = false;
                            foreach ($atributosAnterioresData as $ant) {
                                if ($ant['atributo_id'] == $attrNue['atributo_id']) {
                                    $existia = true;
                                    break;
                                }
                            }
                            
                            if (!$existia && !empty($attrNue['valores'])) {
                                $valoresAgregadosNombres = [];
                                foreach ($attrNue['valores'] as $valor) {
                                    if (is_array($valor) && isset($valor['nombre'])) {
                                        $valoresAgregadosNombres[] = $valor['nombre'];
                                    } elseif (is_string($valor)) {
                                        $valoresAgregadosNombres[] = $valor;
                                    } elseif (is_numeric($valor)) {
                                        $valorObj = AtributoTerm::find($valor);
                                        if ($valorObj) {
                                            $valoresAgregadosNombres[] = $valorObj->nombre;
                                        }
                                    }
                                }
                                
                                if (!empty($valoresAgregadosNombres)) {
                                    $detalleExtra[] = "Atributo NUEVO '{$attrNue['atributo_nombre']}': valores agregados - " . implode(', ', $valoresAgregadosNombres);
                                } else {
                                    $detalleExtra[] = "Atributo NUEVO agregado: '{$attrNue['atributo_nombre']}'";
                                }
                            }
                        }

                        $variacionesActuales = ProductoVariacion::where('producto_padre_id', $producto->id)->get();
                        $variacionesNuevasDetalle = [];

                        foreach ($variacionesActuales as $variacion) {
                            $atributosVariacion = $variacion->atributos()->get()->map(function($attr) {
                                return [
                                    'id' => $attr->id,
                                    'nombre' => $attr->nombre,
                                    'atributo_id' => $attr->atributo_id
                                ];
                            })->toArray();
                            
                            $imagenesVariacion = $variacion->imagenes()->get()->map(function($img) {
                                return [
                                    'id' => $img->id,
                                    'path' => $img->imagen_path
                                ];
                            })->toArray();
                            
                            $variacionesNuevasDetalle[$variacion->id] = [
                                'id' => $variacion->id,
                                'sku' => $variacion->sku,
                                'precio_regular' => $variacion->precio_regular,
                                'precio_rebajado' => $variacion->precio_rebajado,
                                'stock' => $variacion->stock,
                                'fecha_inicio_rebaja' => $variacion->fecha_inicio_rebaja,
                                'fecha_fin_rebaja' => $variacion->fecha_fin_rebaja,
                                'peso' => $variacion->peso,
                                'peso_unidad' => $variacion->peso_unidad,
                                'longitud' => $variacion->longitud,
                                'anchura' => $variacion->anchura,
                                'altura' => $variacion->altura,
                                'descripcion' => $variacion->descripcion,
                                'backorders' => $variacion->backorders,
                                'atributos' => $atributosVariacion,
                                'imagenes' => $imagenesVariacion
                            ];
                        }

                        // Detectar variaciones eliminadas
                        $variacionesEliminadasIds = array_diff(array_keys($variacionesAnterioresDetalle), array_keys($variacionesNuevasDetalle));
                        if (!empty($variacionesEliminadasIds)) {
                            $nombresEliminadas = [];
                            foreach ($variacionesEliminadasIds as $id) {
                                if (isset($variacionesAnterioresDetalle[$id]) && !empty($variacionesAnterioresDetalle[$id]['atributos'])) {
                                    $nombres = array_column($variacionesAnterioresDetalle[$id]['atributos'], 'nombre');
                                    $nombresEliminadas[] = implode(', ', $nombres);
                                } else {
                                    $nombresEliminadas[] = "ID {$id}";
                                }
                            }
                            $detalleExtra[] = "Variaciones eliminadas: " . implode('; ', $nombresEliminadas);
                        }

                        // Detectar variaciones agregadas
                        $variacionesAgregadasIds = array_diff(array_keys($variacionesNuevasDetalle), array_keys($variacionesAnterioresDetalle));
                        if (!empty($variacionesAgregadasIds)) {
                            $nombresAgregadas = [];
                            foreach ($variacionesAgregadasIds as $id) {
                                if (isset($variacionesNuevasDetalle[$id]) && !empty($variacionesNuevasDetalle[$id]['atributos'])) {
                                    $nombres = array_column($variacionesNuevasDetalle[$id]['atributos'], 'nombre');
                                    $nombresAgregadas[] = implode(', ', $nombres);
                                } else {
                                    $nombresAgregadas[] = "ID {$id}";
                                }
                            }
                            $detalleExtra[] = "Variaciones agregadas: " . implode('; ', $nombresAgregadas);
                        }

                        // Detectar cambios en variaciones existentes
                        foreach ($variacionesAnterioresDetalle as $id => $variacionAnt) {
                            if (isset($variacionesNuevasDetalle[$id])) {
                                $variacionNue = $variacionesNuevasDetalle[$id];
                                $cambiosVariacion = [];
                                
                                // Comparar SKU
                                if ($variacionAnt['sku'] != $variacionNue['sku']) {
                                    $cambiosVariacion[] = "SKU: '{$variacionAnt['sku']}' → '{$variacionNue['sku']}'";
                                }
                                
                                // Comparar precios
                                if ($variacionAnt['precio_regular'] != $variacionNue['precio_regular']) {
                                    $cambiosVariacion[] = "precio: {$variacionAnt['precio_regular']} → {$variacionNue['precio_regular']}";
                                }
                                if ($variacionAnt['precio_rebajado'] != $variacionNue['precio_rebajado']) {
                                    $cambiosVariacion[] = "precio rebajado: {$variacionAnt['precio_rebajado']} → {$variacionNue['precio_rebajado']}";
                                }
                                
                                // Comparar stock
                                if ($variacionAnt['stock'] != $variacionNue['stock']) {
                                    $cambiosVariacion[] = "stock: {$variacionAnt['stock']} → {$variacionNue['stock']}";
                                }
                                
                                // Comparar peso y dimensiones
                                if ($variacionAnt['peso'] != $variacionNue['peso']) {
                                    $cambiosVariacion[] = "peso: {$variacionAnt['peso']} → {$variacionNue['peso']}";
                                }
                                if ($variacionAnt['longitud'] != $variacionNue['longitud'] || 
                                    $variacionAnt['anchura'] != $variacionNue['anchura'] || 
                                    $variacionAnt['altura'] != $variacionNue['altura']) {
                                    $cambiosVariacion[] = "dimensiones actualizadas";
                                }
                                
                                // Comparar atributos de la variación
                                $atributosAntIds = array_column($variacionAnt['atributos'], 'id');
                                $atributosNueIds = array_column($variacionNue['atributos'], 'id');
                                
                                $atributosEliminados = array_diff($atributosAntIds, $atributosNueIds);
                                $atributosAgregados = array_diff($atributosNueIds, $atributosAntIds);
                                
                                if (!empty($atributosEliminados)) {
                                    $nombresElim = [];
                                    foreach ($atributosEliminados as $aid) {
                                        foreach ($variacionAnt['atributos'] as $a) {
                                            if ($a['id'] == $aid) {
                                                $nombresElim[] = $a['nombre'];
                                                break;
                                            }
                                        }
                                    }
                                    $cambiosVariacion[] = "atributos eliminados: " . implode(', ', $nombresElim);
                                }
                                if (!empty($atributosAgregados)) {
                                    $nombresAgr = [];
                                    foreach ($atributosAgregados as $aid) {
                                        foreach ($variacionNue['atributos'] as $a) {
                                            if ($a['id'] == $aid) {
                                                $nombresAgr[] = $a['nombre'];
                                                break;
                                            }
                                        }
                                    }
                                    $cambiosVariacion[] = "atributos agregados: " . implode(', ', $nombresAgr);
                                }
                                
                                // Comparar imágenes de la variación
                                $imagenesAntCount = count($variacionAnt['imagenes']);
                                $imagenesNueCount = count($variacionNue['imagenes']);
                                
                                if ($imagenesAntCount != $imagenesNueCount) {
                                    if ($imagenesNueCount > $imagenesAntCount) {
                                        $cambiosVariacion[] = "imágenes agregadas: +" . ($imagenesNueCount - $imagenesAntCount);
                                    } else {
                                        $cambiosVariacion[] = "imágenes eliminadas: -" . ($imagenesAntCount - $imagenesNueCount);
                                    }
                                }
                                
                                // Si hay cambios en esta variación, agregar al detalle
                                if (!empty($cambiosVariacion)) {
                                    $descVariacion = '';
                                    if (!empty($variacionAnt['atributos'])) {
                                        $nombresAtributos = array_column($variacionAnt['atributos'], 'nombre');
                                        $descVariacion = ' [' . implode(', ', $nombresAtributos) . ']';
                                    }
                                    $detalleExtra[] = "Variación{$descVariacion}: " . implode('; ', $cambiosVariacion);
                                }
                            }
                        }
                        
                        $upsellsEliminadosIds = array_diff($upsellsAnterioresIds, $upsellsNuevosIds);
                        $upsellsAgregadosIds = array_diff($upsellsNuevosIds, $upsellsAnterioresIds);
                        
                        $upsellsAgregadosNombres = [];
                        if (!empty($upsellsAgregadosIds)) {
                            $productosUpsells = Producto::whereIn('id', $upsellsAgregadosIds)->get();
                            foreach ($productosUpsells as $prod) {
                                $upsellsAgregadosNombres[] = $prod->nombre . " (ID: {$prod->id})";
                            }
                        }
                        
                        $upsellsEliminadosNombres = [];
                        foreach ($upsellsEliminadosIds as $id) {
                            if (isset($upsellsAnterioresNombres[$id])) {
                                $upsellsEliminadosNombres[] = $upsellsAnterioresNombres[$id] . " (ID: {$id})";
                            }
                        }
                        
                        if (!empty($upsellsEliminadosNombres)) {
                            $detalleExtra[] = "Upsells eliminados: " . implode(', ', $upsellsEliminadosNombres);
                        }
                        if (!empty($upsellsAgregadosNombres)) {
                            $detalleExtra[] = "Upsells agregados: " . implode(', ', $upsellsAgregadosNombres);
                        }
                        
                        $crosssellsEliminadosIds = array_diff($crosssellsAnterioresIds, $crosssellsNuevosIds);
                        $crosssellsAgregadosIds = array_diff($crosssellsNuevosIds, $crosssellsAnterioresIds);
                        
                        $crosssellsAgregadosNombres = [];
                        if (!empty($crosssellsAgregadosIds)) {
                            $productosCrosssells = Producto::whereIn('id', $crosssellsAgregadosIds)->get();
                            foreach ($productosCrosssells as $prod) {
                                $crosssellsAgregadosNombres[] = $prod->nombre . " (ID: {$prod->id})";
                            }
                        }
                        
                        $crosssellsEliminadosNombres = [];
                        foreach ($crosssellsEliminadosIds as $id) {
                            if (isset($crosssellsAnterioresNombres[$id])) {
                                $crosssellsEliminadosNombres[] = $crosssellsAnterioresNombres[$id] . " (ID: {$id})";
                            }
                        }
                        
                        if (!empty($crosssellsEliminadosNombres)) {
                            $detalleExtra[] = "Cross-sells eliminados: " . implode(', ', $crosssellsEliminadosNombres);
                        }
                        if (!empty($crosssellsAgregadosNombres)) {
                            $detalleExtra[] = "Cross-sells agregados: " . implode(', ', $crosssellsAgregadosNombres);
                        }
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'productos',
                            $producto->id,
                            $producto->nombre,
                            $valoresAnteriores,
                            $productoData,
                            !empty($detalleExtra) ? implode(' | ', $detalleExtra) : null
                        );
                        $data->respuesta = 'ok';
                        $data->mensaje = 'Producto variable actualizado correctamente';
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error: ' . $e->getMessage();
                    }
                    break;
                case 'Eliminar':
                    DB::beginTransaction();

                    try {
                        $producto = Producto::with([
                            'imagenes',
                            'variaciones.imagenes',
                            'etiquetas',
                            'atributos',
                            'productosAgrupados',
                            'productosRelacionados',
                        ])->find($request->id);

                        if (!$producto) {
                            throw new \Exception('Producto no encontrado.');
                        }

                        $productoInfo = [
                            'id' => $producto->id,
                            'nombre' => $producto->nombre,
                            'tipo_producto' => $producto->tipo_producto,
                            'sku' => $producto->sku,
                            'estado' => $producto->estado
                        ];
                        
                        // Contar elementos relacionados para la auditoría
                        $contadorImagenes = $producto->imagenes->count();
                        $contadorVariaciones = $producto->variaciones->count();
                        $contadorEtiquetas = $producto->etiquetas->count();
                        $contadorAtributos = $producto->atributos->count();
                        $contadorValoraciones = $producto->valoraciones()->count();
                        $contadorAgrupados = $producto->productosAgrupados->count();
                        $contadorRelacionados = $producto->productosRelacionados->count();
                        
                        // Construir detalle extra con los elementos que se eliminarán
                        $detalleEliminacion = [];
                        if ($contadorImagenes > 0) $detalleEliminacion[] = "{$contadorImagenes} imágenes";
                        if ($contadorVariaciones > 0) $detalleEliminacion[] = "{$contadorVariaciones} variaciones";
                        if ($contadorEtiquetas > 0) $detalleEliminacion[] = "{$contadorEtiquetas} etiquetas";
                        if ($contadorAtributos > 0) $detalleEliminacion[] = "{$contadorAtributos} atributos";
                        if ($contadorValoraciones > 0) $detalleEliminacion[] = "{$contadorValoraciones} valoraciones";
                        if ($contadorAgrupados > 0) $detalleEliminacion[] = "{$contadorAgrupados} productos agrupados";
                        if ($contadorRelacionados > 0) $detalleEliminacion[] = "{$contadorRelacionados} productos relacionados";
                        
                        // Eliminar imágenes del producto
                        foreach ($producto->imagenes as $img) {
                            $ruta = public_path($img->imagen_path);
                            if (File::exists($ruta)) {
                                File::delete($ruta);
                            }
                            $img->delete();
                        }

                        // Eliminar miniatura si existe
                        if ($producto->imagen_miniatura && File::exists(public_path($producto->imagen_miniatura))) {
                            File::delete(public_path($producto->imagen_miniatura));
                        }

                        // Eliminar variaciones e imágenes de cada una
                        foreach ($producto->variaciones as $variacion) {
                            foreach ($variacion->imagenes as $vimg) {
                                $ruta = public_path($vimg->imagen_path);
                                if (File::exists($ruta)) {
                                    File::delete($ruta);
                                }
                                $vimg->delete();
                            }
                            $variacion->atributos()->detach();
                            $variacion->delete();
                        }

                        // Eliminar relaciones pivot
                        $producto->etiquetas()->detach();
                        $producto->atributos()->detach();
                        $producto->productosRelacionados()->detach();

                        // Eliminar agrupados en los que actúe como padre
                        $producto->productosAgrupados()->delete();

                        // Eliminar agrupados donde sea hijo
                        ProductoAgrupado::where('producto_hijo_id', $producto->id)->delete();

                        // Eliminar relacionados donde sea hijo
                        ProductoRelacionado::where('producto_relacionado_id', $producto->id)->delete();

                        // Eliminar valoraciones
                        $producto->valoraciones()->delete();

                        // Finalmente, eliminar el producto
                        $producto->forceDelete();

                        DB::commit();
                        
                        // Construir descripción detallada
                            $descripcionAuditoria = "Producto eliminado: {$productoInfo['nombre']} ";
                            $descripcionAuditoria .= "(ID: {$productoInfo['id']}, Tipo: {$productoInfo['tipo_producto']})";

                            if (!empty($detalleEliminacion)) {
                                $descripcionAuditoria .= " | Elementos eliminados: " . implode(', ', $detalleEliminacion);
                            }

                            $this->registrarAuditoria(
                                'Eliminar',
                                'productos',
                                $productoInfo['id'],
                                $productoInfo['nombre'],
                                null,
                                null,
                                $descripcionAuditoria  
                            );
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al eliminar el producto: ' . $e->getMessage();
                    }
                    break;
                case 'Buscar':
                    try {
                        $query = $request->input('query');
                        $productoId = $request->input('producto_id');

                        $productos = Producto::select('id', 'nombre', 'sku')
                            ->where('tipo_producto', 'simple')
                            ->where('estado', 'publicado')
                            ->when($productoId, function ($q) use ($productoId) {
                                $q->where('id', '!=', $productoId);
                            })
                            ->where(function ($q) use ($query) {
                                $q->where('nombre', 'like', "%{$query}%")
                                ->orWhere('sku', 'like', "%{$query}%");
                            })
                            ->limit(10)
                            ->get();

                        $data->respuesta = 'ok';
                        $data->productos = $productos;
                    } catch (\Exception $e) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al buscar productos: ' . $e->getMessage();
                    }
                    break;
                case 'CrearEtiqueta':
                    $nombre = trim($request->input('nombre'));

                    if (!$nombre) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'El nombre de la etiqueta es obligatorio.';
                        break;
                    }

                    $nombreLower = mb_strtolower($nombre);

                    $nombreSinAcentos = iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLower);
                    $nombreSinAcentos = preg_replace('/[^a-z0-9 ]/u', '', $nombreSinAcentos); // solo letras, números y espacios

                    $slugBase = preg_replace('/\s+/', '-', trim($nombreSinAcentos)); // espacios → guiones

                    $slug = $slugBase;
                    $contador = 1;
                    while (Etiqueta::where('slug', $slug)->exists()) {
                        $slug = $slugBase . '-' . $contador;
                        $contador++;
                    }

                    $etiqueta = Etiqueta::create([
                        'nombre' => $nombre,
                        'slug'   => $slug,
                        'color'  => '#3498db',
                    ]);

                    $this->registrarAuditoria(
                        'Crear',           
                        'etiquetas',       
                        $etiqueta->id,     
                        $etiqueta->nombre, 
                        null,              
                        null,              
                        "Slug: {$etiqueta->slug}"  
                    );

                    $data->respuesta = 'ok';
                    $data->mensaje   = 'Etiqueta creada correctamente';
                    $data->etiqueta  = $etiqueta;
                    break;
                
                case 'CrearAtributo':
                    $nombre = trim($request->input('nombre'));
                    $slugIn = trim($request->input('slug', ''));
                    if (!$nombre) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'El nombre del atributo es obligatorio.';
                        break;
                    }

                    // Normalizar nombre y generar slug si no viene
                    $nombreLower = mb_strtolower($nombre);
                    $nombreSinAcentos = iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLower);
                    $nombreSinAcentos = preg_replace('/[^a-z0-9 ]/u', '', $nombreSinAcentos);
                    $slugBase = $slugIn ? mb_strtolower($slugIn) : preg_replace('/\s+/', '-', trim($nombreSinAcentos));
                    $slugBase = preg_replace('/[^a-z0-9\-]/', '', $slugBase);

                    // Si slug fue ingresado, validar que no exista
                    if ($slugIn && Atributo::where('slug', $slugBase)->exists()) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'El slug ya existe, cámbialo.'; break;
                    }

                    // Si slug no fue ingresado, autogenerar con sufijo si existe
                    $slug = $slugBase;
                    $contador = 1;
                    while (Atributo::where('slug', $slug)->exists()) {
                        $slug = $slugBase . '-' . $contador;
                        $contador++;
                    }

                    $atributo = Atributo::create([
                        'nombre' => $nombre,
                        'slug' => $slug
                    ]);

                    $this->registrarAuditoria(
                        'Crear',           
                        'atributos',       
                        $atributo->id,     
                        $atributo->nombre, 
                        null,              
                        null,              
                        "Slug: {$atributo->slug}"  
                    );

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Atributo creado';
                    $data->atributo = $atributo->fresh();
                    break;


                case 'CrearValorAtributo':
                    // espera: atributo_id OR atributo_slug, nombre, optional slug, descripcion
                    $atributo_id = $request->input('atributo_id');
                    $atributo = null;
                    if ($atributo_id) $atributo = Atributo::find($atributo_id);
                    if (!$atributo) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Atributo no encontrado.';
                        break;
                    }

                    $nombre = trim($request->input('nombre'));
                    $slugIn = trim($request->input('slug', ''));

                    if (!$nombre) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'El nombre del valor es obligatorio.';
                        break;
                    }

                    $nombreLower = mb_strtolower($nombre);
                    $nombreSinAcentos = iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLower);
                    $nombreSinAcentos = preg_replace('/[^a-z0-9 ]/u', '', $nombreSinAcentos);
                    $slugBase = $slugIn ? mb_strtolower($slugIn) : preg_replace('/\s+/', '-', trim($nombreSinAcentos));
                    $slugBase = preg_replace('/[^a-z0-9\-]/', '', $slugBase);

                    // Validar unicidad: unique(['atributo_id','slug'])
                    if ($slugIn && AtributoTerm::where('atributo_id', $atributo->id)->where('slug', $slugBase)->exists()) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'El slug del valor ya existe para ese atributo.';
                        break;
                    }

                    $slug = $slugBase;
                    $contador = 1;
                    while (AtributoTerm::where('atributo_id', $atributo->id)->where('slug', $slug)->exists()) {
                        $slug = $slugBase . '-' . $contador;
                        $contador++;
                    }

                    $termino = AtributoTerm::create([
                        'atributo_id' => $atributo->id,
                        'nombre' => $nombre,
                        'slug' => $slug,
                        'descripcion' => $request->input('descripcion', null),
                    ]);

                    $this->registrarAuditoria(
                        'Crear',           
                        'atributo_terminos',       
                        $termino->id,     
                        $termino->nombre, 
                        null,              
                        null,              
                        null  
                    );

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Valor creado';
                    $data->termino = $termino->fresh();
                    break;

                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->categorias   = Categoria::with('subcategorias')->get();
            $data->etiquetas    = Etiqueta::all();
            $data->atributos    = Atributo::with('terminos')->get();
            $data->script       = ['js/productos.js', 'js/productos-simples.js', 'js/productos-variables.js', 'js/productos-agrupados.js'];
            $data->css          = 'css/administracion.css';
            $data->contenido    = 'catalogo.productos';
            return view('layouts.contenido', (array) $data);
        }
    }
    private function syncProductoAtributos(Producto $producto, array $atributosData)
    {
        // Eliminar relaciones actuales
        $producto->atributos()->detach();
        
        foreach ($atributosData as $attr) {
            $productoAtributo = ProductoAtributo::create([
                'producto_id' => $producto->id,
                'atributo_id' => $attr['atributo_id'],
                'visible'     => $attr['visible'] ?? true,
                'variacion'   => $attr['variacion'] ?? false,
            ]);
            
            if (!empty($attr['valores'])) {
                $productoAtributo->valores()->sync($attr['valores']);
            }
        }
    }

    private function syncVariaciones(Producto $producto, array $variacionesData, $request)
    {
        $idsMantener = [];
        
        foreach ($variacionesData as $index => $vData) {
            // 🔹 NORMALIZAR CAMPOS NUMÉRICOS Y FECHAS
            $normalized = [
                'sku'                => !empty($vData['sku']) ? $vData['sku'] : null,
                'precio_regular'     => !empty($vData['price_normal']) ? floatval($vData['price_normal']) : 0,
                'precio_rebajado'    => !empty($vData['price_sale']) ? floatval($vData['price_sale']) : 0,
                'stock'              => isset($vData['stock']) && $vData['stock'] !== '' ? intval($vData['stock']) : 0,
                'fecha_inicio_rebaja'=> !empty($vData['sale_start']) ? $vData['sale_start'] : null,
                'fecha_fin_rebaja'   => !empty($vData['sale_end']) ? $vData['sale_end'] : null,
                'peso'               => !empty($vData['weight']) ? floatval($vData['weight']) : null,
                'peso_unidad'        => !empty($vData['weight_type']) ? $vData['weight_type'] : 'kg',
                'longitud'           => !empty($vData['length']) ? floatval($vData['length']) : null,
                'anchura'            => !empty($vData['width']) ? floatval($vData['width']) : null,
                'altura'             => !empty($vData['height']) ? floatval($vData['height']) : null,
                'descripcion'        => !empty($vData['description']) ? $vData['description'] : null,
                'backorders'         => ($vData['backorder'] ?? 'no') === 'yes',
            ];

            // ... resto del código (actualizar o crear variación)
            if (!empty($vData['id'])) {
                $variacion = ProductoVariacion::find($vData['id']);
                if ($variacion && $variacion->producto_padre_id == $producto->id) {
                    $variacion->update($normalized);
                    $idsMantener[] = $variacion->id;
                }
            } else {
                $variacion = ProductoVariacion::create(array_merge(
                    ['producto_padre_id' => $producto->id],
                    $normalized
                ));
                $idsMantener[] = $variacion->id;
            }

            if ($variacion) {
                // Sincronizar atributos (termIds)
                $termIds = collect($vData['atributos'] ?? [])
                    ->filter(fn($attr) => !is_null($attr['termId']))
                    ->pluck('termId')
                    ->toArray();
                $variacion->atributos()->sync($termIds);
                
                // Manejar imágenes
                $this->syncVariacionImagenes($variacion, $request, $index);
            }
        }
        
        // Eliminar variaciones sobrantes...
        ProductoVariacion::where('producto_padre_id', $producto->id)
            ->whereNotIn('id', $idsMantener)
            ->each(function ($var) {
                $var->atributos()->detach();
                $var->imagenes()->delete();
                $var->delete();
            });
    }

    private function syncVariacionImagenes(ProductoVariacion $variacion, $request, $index)
    {
        // Primero, eliminar imágenes marcadas en el frontend
        if ($request->has('variaciones_imagenes_eliminar')) {
            $eliminarIds = json_decode($request->input('variaciones_imagenes_eliminar'), true);
            if (is_array($eliminarIds)) {
                VariacionImagen::whereIn('id', $eliminarIds)->each(function ($img) {
                    $ruta = public_path($img->imagen_path);
                    if (File::exists($ruta)) File::delete($ruta);
                    $img->delete();
                });
            }
        }
        
        // Subir nuevas imágenes
        if ($request->hasFile("variation_images_{$index}")) {
            $carpeta = public_path('image_producto');
            foreach ($request->file("variation_images_{$index}") as $file) {
                $nombre = 'var_' . uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($carpeta, $nombre);
                VariacionImagen::create([
                    'variacion_id' => $variacion->id,
                    'imagen_path'  => "image_producto/{$nombre}",
                ]);
            }
        }
    }

    private function sincronizarImagenes(Producto $producto, $request, $fecha, $carpetaDestino)
    {
        // --- Miniatura ---
        if ($request->input('eliminar_miniatura') === 'true' && $producto->imagen_miniatura) {
            $ruta = public_path($producto->imagen_miniatura);
            if (File::exists($ruta)) File::delete($ruta);
            $producto->update(['imagen_miniatura' => null]);
        }

        if ($request->hasFile('imagen_miniatura')) {
            if ($producto->imagen_miniatura) {
                $rutaAnterior = public_path($producto->imagen_miniatura);
                if (File::exists($rutaAnterior)) File::delete($rutaAnterior);
            }
            $file = $request->file('imagen_miniatura');
            $nombre = 'mini_' . Str::random(5) . "_{$fecha}." . $file->getClientOriginalExtension();
            $file->move($carpetaDestino, $nombre);
            $producto->update(['imagen_miniatura' => "image_producto/{$nombre}"]);
        }

        // --- Imágenes principales ---
        if ($request->has('imagenes_eliminar')) {
            $idsEliminar = json_decode($request->input('imagenes_eliminar'), true);
            if (is_array($idsEliminar)) {
                ProductoImagen::whereIn('id', $idsEliminar)->each(function ($img) use ($carpetaDestino) {
                    $ruta = public_path($img->imagen_path);
                    if (File::exists($ruta)) File::delete($ruta);
                    $img->delete();
                });
            }
        }

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $i => $imagen) {
                $nombre = 'img_' . ($i+1) . '_' . Str::random(5) . "_{$fecha}." . $imagen->getClientOriginalExtension();
                $imagen->move($carpetaDestino, $nombre);
                ProductoImagen::create([
                    'producto_id' => $producto->id,
                    'imagen_path' => "image_producto/{$nombre}"
                ]);
            }
        }
    }
}
