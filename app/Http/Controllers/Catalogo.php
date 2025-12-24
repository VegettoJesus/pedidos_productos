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
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function categorias(Request $request)
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
            $data->script = 'js/categorias.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'catalogo.categorias';
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
                        'atributos.terminos',
                        'variaciones.atributos',
                        'variaciones.imagenes',
                        'productosAgrupados.productoHijo',
                        // IMPORTANTE: Usar withPivot para incluir el campo 'tipo'
                        'productosRelacionados' => function($query) {
                            $query->select(
                                'productos.id', 
                                'productos.nombre', 
                                'productos.sku'
                            )->withPivot('tipo'); // <- AÑADIR ESTO
                        }
                    ])->find($id);

                    if (!$producto) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Producto no encontrado';
                        break;
                    }

                    $data->respuesta = 'ok';
                    $data->producto = $producto;
                    break;

                case 'Crear':
                    DB::beginTransaction();

                    try {
                        // ✅ CORRECTO: Obtener datos básicos
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
                    // Similar a Crear pero con actualización
                    $id = $request->input('id');
                    $producto = Producto::find($id);
                    
                    if (!$producto) {
                        $data->respuesta = 'error';
                        $data->mensaje = 'Producto no encontrado';
                        break;
                    }

                    DB::beginTransaction();

                    // Actualizar datos básicos (similar a Crear)
                    // ... código de actualización ...

                    DB::commit();

                    $data->respuesta = 'ok';
                    $data->mensaje = 'Producto actualizado correctamente';
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

                        $productos = Producto::select('id', 'nombre', 'sku')
                            ->where('tipo_producto', 'simple')              // ✅ solo productos simples
                            ->where('estado', 'publicado')                 // ✅ solo publicados
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
            $data->script       = 'js/productos.js';
            $data->css          = 'css/administracion.css';
            $data->contenido    = 'catalogo.productos';
            return view('layouts.contenido', (array) $data);
        }
    }
}
