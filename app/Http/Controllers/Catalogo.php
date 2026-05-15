<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Auditoria;
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
    private function registrarAuditoria($accion, $tabla, $registroId = null, $descripcion = null)
    {
        Auditoria::create([
            'user_id'       => Auth::id(),
            'accion'        => $accion,
            'tabla_afectada'=> $tabla,
            'registro_id'   => $registroId,
            'descripcion'   => $descripcion,
            'ip'            => request()->ip(),
            'navegador'     => request()->header('User-Agent')
        ]);
    }

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
                    $this->registrarAuditoria('Crear', 'categorias', $categoria->id, "Categoría: {$categoria->nombre}");
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
                        $categoria->update([
                            'nombre' => $request->input('nombre'),
                            'icono'  => $request->input('icono')
                        ]);
                        $this->registrarAuditoria('Actualizar', 'categorias', $categoria->id, "Categoría: {$categoria->nombre}");
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
                        $this->registrarAuditoria('Eliminar', 'categorias', $categoria->id, "Categoría: {$categoria->nombre}");
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
                    $this->registrarAuditoria('Crear', 'subcategorias', $subcategoria->id, "Subcategoría: {$subcategoria->nombre}");
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
                        $subcategoria->update([
                            'nombre' => $request->input('nombre'),
                            'icono'  => $request->input('icono'),
                            'id_categoria' => $request->input('id_categoria'),
                        ]);
                        $this->registrarAuditoria('Actualizar', 'subcategorias', $subcategoria->id, "Subcategoría: {$subcategoria->nombre}");
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
                        $this->registrarAuditoria('Eliminar', 'subcategorias', $subcategoria->id, "Subcategoría: {$subcategoria->nombre}");
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
                            
                            foreach ($atributosData as $atributoData) {
                                $productoAtributo = ProductoAtributo::create([
                                    'producto_id' => $producto->id,
                                    'atributo_id' => $atributoData['atributo_id'],
                                    'visible' => $atributoData['visible'],
                                    'variacion' => $atributoData['variacion']
                                ]);

                                // Sincronizar valores del atributo
                                if (!empty($atributoData['valores'])) {
                                    $productoAtributo->valores()->sync($atributoData['valores']);
                                }
                            }
                        }

                        // 5️⃣ Variaciones (solo para productos variables)
                        if ($producto->tipo_producto === 'variable' && $request->has('variaciones')) {
                            $variacionesData = json_decode($request->input('variaciones'), true);

                            foreach ($variacionesData as $index => $variacionData) {
                                $variacion = ProductoVariacion::create([
                                    'producto_padre_id' => $producto->id,
                                    'sku' => !empty($variacionData['sku']) ? $variacionData['sku'] : null,
                                    'precio_regular' => isset($variacionData['price_normal']) && $variacionData['price_normal'] !== ''
                                        ? floatval($variacionData['price_normal'])
                                        : 0.00,
                                    'precio_rebajado' => isset($variacionData['price_sale']) && $variacionData['price_sale'] !== ''
                                        ? floatval($variacionData['price_sale'])
                                        : 0.00,
                                    'stock' => intval($variacionData['stock'] ?? 0),
                                    'peso' => $variacionData['weight'] ? floatval($variacionData['weight']) : null,
                                    'peso_unidad' => $variacionData['weight_type'] ?? 'kg',
                                    'longitud' => $variacionData['length'] ? floatval($variacionData['length']) : null,
                                    'anchura' => $variacionData['width'] ? floatval($variacionData['width']) : null,
                                    'altura' => $variacionData['height'] ? floatval($variacionData['height']) : null,
                                    'descripcion' => $variacionData['description'] ?? null,
                                    'backorders' => ($variacionData['backorder'] ?? 'no') === 'yes',
                                    'fecha_inicio_rebaja' => $variacionData['sale_start'] ?: null,
                                    'fecha_fin_rebaja' => $variacionData['sale_end'] ?: null
                                ]);

                                // Sincronizar atributos de la variación
                                if (!empty($variacionData['atributos'])) {
                                    $atributoTermIds = collect($variacionData['atributos'])
                                        ->filter(function($attr) {
                                            return !is_null($attr['termId']) && $attr['termId'] !== '';
                                        })
                                        ->pluck('termId')
                                        ->toArray();
                                    $variacion->atributos()->sync($atributoTermIds);
                                }

                                // Manejar imágenes de la variación
                                if ($request->hasFile("variation_images_{$index}")) {
                                    foreach ($request->file("variation_images_{$index}") as $file) {
                                        if ($file->isValid()) {
                                            $nombre = 'var_' . $index . '_' . Str::random(5) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
                                            $file->move($carpetaDestino, $nombre);

                                            VariacionImagen::create([
                                                'variacion_id' => $variacion->id,
                                                'imagen_path' => "image_producto/$nombre"
                                            ]);
                                        }
                                    }
                                }
                            }
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

                        $descripcionLimpia = preg_replace('/<img[^>]*>/i', '', $request->input('descripcion_larga', ''));
                        $productoData = [
                            'nombre' => $request->input('nombre'),
                            'descripcion' => $request->input('descripcion'),
                            'descripcion_completa' => $descripcionLimpia,
                            'tipo_producto' => 'simple', // Forzamos simple por ahora
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
                        // Eliminar miniatura existente si se marcó para eliminar
                        if ($request->input('eliminar_miniatura') === 'true' && $producto->imagen_miniatura) {
                            $rutaMiniatura = public_path($producto->imagen_miniatura);
                            if (File::exists($rutaMiniatura)) {
                                File::delete($rutaMiniatura);
                            }
                            $producto->update(['imagen_miniatura' => null]);
                        }

                        // Subir nueva miniatura si se proporcionó
                        if ($request->hasFile('imagen_miniatura')) {
                            // Eliminar la anterior si existe
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
                        }

                        // --- 2. IMÁGENES PRINCIPALES ---
                        // Eliminar imágenes marcadas
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
                            }
                        }

                        // --- 3. ETIQUETAS ---
                        if ($request->has('etiquetas')) {
                            $etiquetasIds = json_decode($request->input('etiquetas'), true);
                            $etiquetasExistentes = Etiqueta::whereIn('id', $etiquetasIds)->pluck('id')->toArray();
                            $producto->etiquetas()->sync($etiquetasExistentes);
                        }

                        // --- 4. ATRIBUTOS ---
                        if ($request->has('atributos')) {
                            $atributosData = json_decode($request->input('atributos'), true);
                            
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
                        \Log::info('=== DEBUG RELACIONES ===');
                        \Log::info('Producto ID: ' . $producto->id);
                        \Log::info('Request has upsells: ' . ($request->has('upsells') ? 'SI' : 'NO'));
                        \Log::info('Request has crosssells: ' . ($request->has('crosssells') ? 'SI' : 'NO'));

                        if ($request->has('upsells')) {
                            $upsellsIds = json_decode($request->input('upsells'), true);
                            \Log::info('Upsells recibidos (RAW): ' . $request->input('upsells'));
                            \Log::info('Upsells decodificados: ' . json_encode($upsellsIds));
                        }

                        if ($request->has('crosssells')) {
                            $crosssellsIds = json_decode($request->input('crosssells'), true);
                            \Log::info('Crosssells recibidos (RAW): ' . $request->input('crosssells'));
                            \Log::info('Crosssells decodificados: ' . json_encode($crosssellsIds));
                        }

                        $deleted = ProductoRelacionado::where('producto_id', $producto->id)->delete();
                        \Log::info("Eliminadas {$deleted} relaciones para producto {$producto->id}");

                        // SEGUNDO: Usar upsert o firstOrCreate para evitar duplicados
                        $relacionesAGuardar = [];

                        // Agregar upsells
                        if ($request->has('upsells')) {
                            $upsellsIds = json_decode($request->input('upsells'), true);
                            if (is_array($upsellsIds)) {
                                foreach ($upsellsIds as $relId) {
                                    $relacionesAGuardar[] = [
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => 'upsell'
                                    ];
                                }
                            }
                        }

                        // Agregar crosssells
                        if ($request->has('crosssells')) {
                            $crosssellsIds = json_decode($request->input('crosssells'), true);
                            if (is_array($crosssellsIds)) {
                                foreach ($crosssellsIds as $relId) {
                                    $relacionesAGuardar[] = [
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => 'crosssell'
                                    ];
                                }
                            }
                        }

                        // TERCERO: Insertar todas las nuevas relaciones
                        if (!empty($relacionesAGuardar)) {
                            foreach ($relacionesAGuardar as $relacion) {
                                try {
                                    ProductoRelacionado::create($relacion);
                                } catch (\Exception $e) {
                                    // Si falla por duplicado, intentar actualizar
                                    if ($e->getCode() == 23000) { // Código de error de duplicado
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
                        if ($request->input('eliminar_miniatura') === 'true' && $producto->imagen_miniatura) {
                            $rutaMiniatura = public_path($producto->imagen_miniatura);
                            if (File::exists($rutaMiniatura)) {
                                File::delete($rutaMiniatura);
                            }
                            $producto->update(['imagen_miniatura' => null]);
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
                        }

                        // --- 2. IMÁGENES PRINCIPALES ---
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
                            }
                        }

                        // --- 3. ETIQUETAS ---
                        if ($request->has('etiquetas')) {
                            $etiquetasIds = json_decode($request->input('etiquetas'), true);
                            $etiquetasExistentes = Etiqueta::whereIn('id', $etiquetasIds)->pluck('id')->toArray();
                            $producto->etiquetas()->sync($etiquetasExistentes);
                        }

                        // --- 4. ATRIBUTOS ---
                        if ($request->has('atributos')) {
                            $atributosData = json_decode($request->input('atributos'), true);
                            
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
                        ProductoAgrupado::where('producto_padre_id', $producto->id)->delete();
                            
                        if ($request->has('relacionados')) {
                            $hijosIds = json_decode($request->input('relacionados'), true);        
                            if (is_array($hijosIds)) {
                                foreach ($hijosIds as $hijoId) {
                                    ProductoAgrupado::create([
                                        'producto_padre_id' => $producto->id,
                                        'producto_hijo_id' => $hijoId
                                    ]);
                                }
                            }
                        }

                        // --- 6. PRODUCTOS RELACIONADOS (Upsells/Crosssells) ---
                        ProductoRelacionado::where('producto_id', $producto->id)->delete();

                        if ($request->has('crosssells')) {
                            $crosssellsIds = json_decode($request->input('crosssells'), true);
                            if (is_array($crosssellsIds)) {
                                foreach ($crosssellsIds as $relId) {
                                    ProductoRelacionado::create([
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => 'crosssell'
                                    ]);
                                }
                            }
                        }

                        DB::commit();

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
                        $producto = Producto::find($id);
                        
                        if (!$producto) {
                            $data->respuesta = 'error';
                            $data->mensaje = 'Producto no encontrado';
                            break;
                        }
                        $descripcionLimpia = preg_replace('/<img[^>]*>/i', '', $request->input('descripcion_larga', ''));
                        // --- Datos básicos del producto ---
                        $productoData = [
                            'nombre' => $request->input('nombre'),
                            'descripcion' => $request->input('descripcion'),
                            'descripcion_completa' => $descripcionLimpia,
                            'tipo_producto' => 'variable',
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
                            'nota_interna' => $request->input('nota_interna')
                        ];

                        // Los productos variables no tienen precio directo
                        $productoData['precio_regular'] = 0;
                        $productoData['precio_rebajado'] = null;
                        $productoData['fecha_inicio_rebaja'] = null;
                        $productoData['fecha_fin_rebaja'] = null;

                        $producto->update($productoData);

                        // --- Carpeta destino para imágenes ---
                        $carpetaDestino = public_path('image_producto');
                        if (!File::exists($carpetaDestino)) {
                            File::makeDirectory($carpetaDestino, 0755, true);
                        }

                        $fecha = date('Ymd_His');

                        // --- 1. MINIATURA ---
                        if ($request->input('eliminar_miniatura') === 'true' && $producto->imagen_miniatura) {
                            $rutaMiniatura = public_path($producto->imagen_miniatura);
                            if (File::exists($rutaMiniatura)) File::delete($rutaMiniatura);
                            $producto->update(['imagen_miniatura' => null]);
                        }

                        if ($request->hasFile('imagen_miniatura')) {
                            if ($producto->imagen_miniatura) {
                                $rutaAnterior = public_path($producto->imagen_miniatura);
                                if (File::exists($rutaAnterior)) File::delete($rutaAnterior);
                            }
                            
                            $file = $request->file('imagen_miniatura');
                            $nombre = 'mini_' . Str::random(5) . "_$fecha." . $file->getClientOriginalExtension();
                            $file->move($carpetaDestino, $nombre);
                            $producto->update(['imagen_miniatura' => "image_producto/$nombre"]);
                        }

                        // --- 2. IMÁGENES PRINCIPALES ---
                        if ($request->has('imagenes_eliminar')) {
                            $idsEliminar = json_decode($request->input('imagenes_eliminar'), true);
                            if (is_array($idsEliminar) && count($idsEliminar) > 0) {
                                $imagenesEliminar = ProductoImagen::whereIn('id', $idsEliminar)->get();
                                foreach ($imagenesEliminar as $img) {
                                    $ruta = public_path($img->imagen_path);
                                    if (File::exists($ruta)) File::delete($ruta);
                                    $img->delete();
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
                            }
                        }

                        // --- 3. ETIQUETAS ---
                        if ($request->has('etiquetas')) {
                            $etiquetasIds = json_decode($request->input('etiquetas'), true);
                            $etiquetasExistentes = Etiqueta::whereIn('id', $etiquetasIds)->pluck('id')->toArray();
                            $producto->etiquetas()->sync($etiquetasExistentes);
                        }

                        // --- 4. ATRIBUTOS (con visible y variacion) ---
                        if ($request->has('atributos')) {
                            $atributosData = json_decode($request->input('atributos'), true);
                            
                            // Eliminar atributos actuales
                            ProductoAtributo::where('producto_id', $producto->id)->delete();
                            
                            foreach ($atributosData as $atributoData) {
                                $productoAtributo = ProductoAtributo::create([
                                    'producto_id' => $producto->id,
                                    'atributo_id' => $atributoData['atributo_id'],
                                    'visible' => $atributoData['visible'] ?? true,
                                    'variacion' => $atributoData['variacion'] ?? false
                                ]);

                                // ✅ SOLO sincronizar los valores seleccionados
                                if (!empty($atributoData['valores'])) {
                                    $productoAtributo->valores()->sync($atributoData['valores']);
                                }
                            }
                        }

                        // --- 5. VARIACIONES ---
                        if ($request->has('variaciones')) {
                            $variacionesData = json_decode($request->input('variaciones'), true);
                            
                            // Obtener IDs de variaciones a conservar
                            $idsAConservar = collect($variacionesData)->pluck('id')->filter()->toArray();
                            
                            // Eliminar variaciones que no están en la lista actual
                            if (!empty($idsAConservar)) {
                                $variacionesAEliminar = ProductoVariacion::where('producto_padre_id', $producto->id)
                                    ->whereNotIn('id', $idsAConservar)
                                    ->get();
                            } else {
                                $variacionesAEliminar = ProductoVariacion::where('producto_padre_id', $producto->id)->get();
                            }
                            
                            // Eliminar imágenes de variaciones eliminadas
                            foreach ($variacionesAEliminar as $varEliminar) {
                                foreach ($varEliminar->imagenes as $img) {
                                    $ruta = public_path($img->imagen_path);
                                    if (File::exists($ruta)) File::delete($ruta);
                                    $img->delete();
                                }
                                $varEliminar->atributos()->detach();
                                $varEliminar->delete();
                            }

                            // Procesar cada variación
                            foreach ($variacionesData as $index => $variacionData) {
                                if (!empty($variacionData['id'])) {
                                    // Actualizar variación existente
                                    $variacion = ProductoVariacion::find($variacionData['id']);
                                    if ($variacion) {
                                        $variacion->update([
                                            'sku' => !empty($variacionData['sku']) ? $variacionData['sku'] : null,
                                            'precio_regular' => floatval($variacionData['price_normal'] ?? 0),
                                            'precio_rebajado' => floatval($variacionData['price_sale'] ?? 0),
                                            'stock' => intval($variacionData['stock'] ?? 0),
                                            'fecha_inicio_rebaja' => $variacionData['sale_start'] ?: null,
                                            'fecha_fin_rebaja' => $variacionData['sale_end'] ?: null,
                                            'peso' => $variacionData['weight'] ? floatval($variacionData['weight']) : null,
                                            'peso_unidad' => $variacionData['weight_type'] ?? 'kg',
                                            'longitud' => $variacionData['length'] ? floatval($variacionData['length']) : null,
                                            'anchura' => $variacionData['width'] ? floatval($variacionData['width']) : null,
                                            'altura' => $variacionData['height'] ? floatval($variacionData['height']) : null,
                                            'descripcion' => $variacionData['description'] ?? null,
                                            'backorders' => ($variacionData['backorder'] ?? 'no') === 'yes'
                                        ]);

                                        // Sincronizar atributos (filtrar valores null)
                                        if (!empty($variacionData['atributos'])) {
                                            $atributoTermIds = collect($variacionData['atributos'])
                                                ->filter(function($attr) {
                                                    // Solo sincronizar si termId NO es null
                                                    return !is_null($attr['termId']);
                                                })
                                                ->pluck('termId')
                                                ->toArray();
                                            $variacion->atributos()->sync($atributoTermIds);
                                        }
                                    }
                                } else {
                                    // Crear nueva variación
                                    $variacion = ProductoVariacion::create([
                                        'producto_padre_id' => $producto->id,
                                        'sku' => !empty($variacionData['sku']) ? $variacionData['sku'] : null,
                                        'precio_regular' => floatval($variacionData['price_normal'] ?? 0),
                                        'precio_rebajado' => floatval($variacionData['price_sale'] ?? 0),
                                        'stock' => intval($variacionData['stock'] ?? 0),
                                        'fecha_inicio_rebaja' => $variacionData['sale_start'] ?: null,
                                        'fecha_fin_rebaja' => $variacionData['sale_end'] ?: null,
                                        'peso' => $variacionData['weight'] ? floatval($variacionData['weight']) : null,
                                        'peso_unidad' => $variacionData['weight_type'] ?? 'kg',
                                        'longitud' => $variacionData['length'] ? floatval($variacionData['length']) : null,
                                        'anchura' => $variacionData['width'] ? floatval($variacionData['width']) : null,
                                        'altura' => $variacionData['height'] ? floatval($variacionData['height']) : null,
                                        'descripcion' => $variacionData['description'] ?? null,
                                        'backorders' => ($variacionData['backorder'] ?? 'no') === 'yes'
                                    ]);

                                    if (!empty($variacionData['atributos'])) {
                                        $atributoTermIds = collect($variacionData['atributos'])
                                            ->filter(function($attr) {
                                                return !is_null($attr['termId']);
                                            })
                                            ->pluck('termId')
                                            ->toArray();
                                        $variacion->atributos()->sync($atributoTermIds);
                                    }
                                }

                                // --- Imágenes de la variación (código existente) ---
                                if ($request->hasFile("variation_images_{$index}")) {
                                    foreach ($request->file("variation_images_{$index}") as $file) {
                                        if ($file->isValid()) {
                                            $nombre = 'var_' . $index . '_' . Str::random(5) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
                                            $file->move($carpetaDestino, $nombre);

                                            VariacionImagen::create([
                                                'variacion_id' => $variacion->id,
                                                'imagen_path' => "image_producto/$nombre"
                                            ]);
                                        }
                                    }
                                }
                            }
                        }

                        // --- Eliminar imágenes de variaciones marcadas ---
                        if ($request->has('variaciones_imagenes_eliminar')) {
                            $imgIdsEliminar = json_decode($request->input('variaciones_imagenes_eliminar'), true);
                            if (is_array($imgIdsEliminar) && count($imgIdsEliminar) > 0) {
                                $imagenesEliminar = VariacionImagen::whereIn('id', $imgIdsEliminar)->get();
                                foreach ($imagenesEliminar as $img) {
                                    $ruta = public_path($img->imagen_path);
                                    if (File::exists($ruta)) File::delete($ruta);
                                    $img->delete();
                                }
                            }
                        }

                        // --- 6. UPSELLS Y CROSSSELLS ---
                        // Eliminar relaciones existentes
                        ProductoRelacionado::where('producto_id', $producto->id)->delete();

                        // Agregar nuevas relaciones
                        if ($request->has('upsells')) {
                            $upsellsIds = json_decode($request->input('upsells'), true);
                            if (is_array($upsellsIds)) {
                                foreach ($upsellsIds as $relId) {
                                    ProductoRelacionado::create([
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => 'upsell'
                                    ]);
                                }
                            }
                        }

                        if ($request->has('crosssells')) {
                            $crosssellsIds = json_decode($request->input('crosssells'), true);
                            if (is_array($crosssellsIds)) {
                                foreach ($crosssellsIds as $relId) {
                                    ProductoRelacionado::create([
                                        'producto_id' => $producto->id,
                                        'producto_relacionado_id' => $relId,
                                        'tipo' => 'crosssell'
                                    ]);
                                }
                            }
                        }

                        DB::commit();

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Producto variable actualizado correctamente';

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $data->respuesta = 'error';
                        $data->mensaje = 'Error al actualizar el producto: ' . $e->getMessage();
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

                        // 🔹 Eliminar imágenes del producto
                        foreach ($producto->imagenes as $img) {
                            $ruta = public_path($img->imagen_path);
                            if (File::exists($ruta)) {
                                File::delete($ruta);
                            }
                            $img->delete();
                        }

                        // 🔹 Eliminar miniatura si existe
                        if ($producto->imagen_miniatura && File::exists(public_path($producto->imagen_miniatura))) {
                            File::delete(public_path($producto->imagen_miniatura));
                        }

                        // 🔹 Eliminar variaciones e imágenes de cada una
                        foreach ($producto->variaciones as $variacion) {
                            foreach ($variacion->imagenes as $vimg) {
                                $ruta = public_path($vimg->imagen_path);
                                if (File::exists($ruta)) {
                                    File::delete($ruta);
                                }
                                $vimg->delete();
                            }
                            // limpiar atributos vinculados a esa variación
                            $variacion->atributos()->detach();
                            $variacion->delete();
                        }

                        // 🔹 Eliminar relaciones pivot
                        $producto->etiquetas()->detach();
                        $producto->atributos()->detach();
                        $producto->productosRelacionados()->detach();

                        // 🔹 Eliminar agrupados en los que actúe como padre
                        $producto->productosAgrupados()->delete();

                        // 🔹 Eliminar agrupados donde sea hijo
                        ProductoAgrupado::where('producto_hijo_id', $producto->id)->delete();

                        // 🔹 Eliminar relacionados donde sea hijo
                        ProductoRelacionado::where('producto_relacionado_id', $producto->id)->delete();

                        // 🔹 Eliminar valoraciones
                        $producto->valoraciones()->delete();

                        // 🔹 Finalmente, eliminar el producto
                        $producto->forceDelete();

                        DB::commit();

                        $data->respuesta = 'ok';
                        $data->mensaje = 'Producto eliminado correctamente.';
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
}
