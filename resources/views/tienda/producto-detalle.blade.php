@php
    $puedeComprar = $producto->puede_comprar;
    $stockDisponible = $producto->stock ?? 0;
    $estadoInventario = $producto->estado_stock;
    $backordersPermitidos = $producto->backorders ?? false;
    $gestionaInventario = $producto->gestion_inventario ?? false;
    $vendidoIndividualmente = $producto->vendido_individualmente ?? false;
    $permiteValoraciones = $producto->permite_valoraciones ?? false;
    $esAgrupado = $producto->tipo_producto === 'agrupado';
    
    $maxCantidad = 99;
    if ($vendidoIndividualmente) {
        $maxCantidad = 1;
    } elseif ($gestionaInventario && $stockDisponible > 0) {
        $maxCantidad = $stockDisponible;
    }
    
    $productosHijos = $esAgrupado ? $producto->productosHijos()->where('estado', 'publicado')->get() : collect();
@endphp

<div class="product-detail-container" 
     data-producto-id="{{ $producto->id }}" 
     data-vendido-individualmente="{{ $vendidoIndividualmente ? 'true' : 'false' }}"
     data-gestion-inventario="{{ $gestionaInventario ? 'true' : 'false' }}"
     data-backorders="{{ $backordersPermitidos ? 'true' : 'false' }}">
    <div class="product-detail-grid">
        <div class="product-gallery">
            <div class="main-image">
                <img id="mainProductImage" 
                     src="{{ $producto->imagen_miniatura ? asset('/' . $producto->imagen_miniatura) : asset('img/no-image.png') }}" 
                     alt="{{ $producto->nombre }}">
            </div>
            @php
                $allImages = $producto->imagenes->pluck('imagen_path')->toArray();
                if ($producto->imagen_miniatura) {
                    if (!in_array($producto->imagen_miniatura, $allImages)) {
                        array_unshift($allImages, $producto->imagen_miniatura);
                    }
                }
                if (empty($allImages) && $producto->imagen_miniatura) {
                    $allImages = [$producto->imagen_miniatura];
                }
            @endphp
            <div class="thumbnail-list">
                @if(count($allImages) > 1 || (count($allImages) == 1 && $producto->imagenes->count() > 0))
                    @foreach($allImages as $index => $imagenPath)
                        <div class="thumbnail {{ $index === 0 ? 'active' : '' }}" 
                            data-image="{{ asset('/' . $imagenPath) }}">
                            <img src="{{ asset('/' . $imagenPath) }}" alt="{{ $producto->nombre }}">
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="product-info-detailed">
            <div class="product-brand-sku">
                <span class="product-brand">{{ $producto->marca ?? 'Marca' }}</span>
                <span class="product-sku" id="productSku">
                    @if($producto->sku)
                        <span class="sku-label">SKU:</span> <span class="sku-value">{{ $producto->sku }}</span>
                    @endif
                </span>
            </div>
            <h1 class="product-title">{{ $producto->nombre }}</h1>
            
            @if($permiteValoraciones)
                <div class="product-rating">
                    <div class="stars-wrapper" data-rating="{{ $producto->rating }}">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star star" data-value="{{ $i }}"></i>
                        @endfor
                    </div>
                    <span class="rating-count">({{ $producto->rating_count }} reseñas)</span>
                </div>
            @endif

            <div class="product-price-detailed" 
                data-precio-regular="{{ $producto->precio_regular }}"
                data-precio-rebajado="{{ $producto->precio_rebajado }}"
                data-descuento="{{ $producto->descuento_porcentaje }}">
                @if($producto->descuento_porcentaje)
                    <span class="old-price">{{ $producto->precio_regular_original }}</span>
                    <span class="sale-price">{{ $producto->precio_formateado }}</span>
                    <span class="discount-badge-large">-{{ $producto->descuento_porcentaje }}%</span>
                @else
                    <span class="regular-price">{{ $producto->precio_formateado }}</span>
                @endif
            </div>

            <div class="product-short-description">
                {!! nl2br(e(Str::limit($producto->descripcion, 200))) !!}
            </div>

            <!-- ============================================= -->
            <!-- VARIABLES: PRODUCTO VARIABLE                  -->
            <!-- ============================================= -->
            @if($producto->tipo_producto === 'variable')
                <div class="product-variations">
                    @foreach($variacionesAgrupadas as $atributoId => $data)
                        <div class="variation-attribute" data-atributo-id="{{ $atributoId }}">
                            <label>{{ $data->atributo->nombre }}:</label>
                            
                            @php
                                $terminosMostrar = $data->terminos_a_mostrar ?? [];
                                
                                if (empty($terminosMostrar)) {
                                    if (isset($data->atributo) && is_object($data->atributo)) {
                                        if (property_exists($data->atributo, 'terminos')) {
                                            if (is_array($data->atributo->terminos)) {
                                                $terminosMostrar = $data->atributo->terminos;
                                            } elseif ($data->atributo->terminos instanceof \Illuminate\Support\Collection) {
                                                $terminosMostrar = $data->atributo->terminos->toArray();
                                            }
                                        }
                                    }
                                }
                                
                                if (empty($terminosMostrar)) {
                                    $terminosMostrar = AtributoTerm::where('atributo_id', $atributoId)->get()->toArray();
                                }
                                
                                $terminosObjeto = [];
                                foreach ($terminosMostrar as $termino) {
                                    if (is_array($termino)) {
                                        $terminosObjeto[] = (object) $termino;
                                    } else {
                                        $terminosObjeto[] = $termino;
                                    }
                                }
                                
                                $terminosConVariacion = [];
                                foreach ($data->variaciones as $variacion) {
                                    foreach ($variacion->atributos as $t) {
                                        if ($t->atributo_id == $atributoId) {
                                            $terminosConVariacion[$t->id] = true;
                                        }
                                    }
                                }
                                
                                $tieneCualquier = $data->tiene_cualquier ?? false;
                            @endphp
                            
                            @if($data->tipo === 'Color')
                                <div class="variation-options color-options" data-tipo="color" data-shape="{{ $data->shape }}" data-atributo-id="{{ $atributoId }}">
                                    @foreach($terminosObjeto as $termino)
                                        @php
                                            $variacion = null;
                                            if (isset($terminosConVariacion[$termino->id])) {
                                                foreach ($data->variaciones as $v) {
                                                    foreach ($v->atributos as $t) {
                                                        if ($t->atributo_id == $atributoId && $t->id == $termino->id) {
                                                            $variacion = $v;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                            $disponible = $variacion !== null;
                                            $valorExtra = isset($valorExtraMap[$atributoId . '_' . $termino->id]) 
                                                ? $valorExtraMap[$atributoId . '_' . $termino->id] 
                                                : null;
                                            $colorValue = $valorExtra ?? '#cccccc';
                                        @endphp
                                        <div class="variation-option color-option {{ $disponible ? '' : 'disabled' }}" 
                                            data-variacion-id="{{ $variacion ? $variacion->id : '' }}" 
                                            data-atributo-id="{{ $atributoId }}"
                                            data-termino-id="{{ $termino->id }}"
                                            data-color="{{ $colorValue }}"
                                            data-disponible="{{ $disponible ? 1 : 0 }}"
                                            title="{{ $termino->nombre }}{{ !$disponible ? ' (No disponible)' : '' }}">
                                            <span class="color-swatch" style="background-color: {{ $colorValue }}; border-radius: {{ $data->shape === 'Circle' ? '50%' : ($data->shape === 'Rounded Corner' ? '4px' : '2px') }};"></span>
     
                                        </div>
                                    @endforeach
                                </div>
                                
                            @elseif($data->tipo === 'Image')
                                <div class="variation-options image-options" data-tipo="image" data-shape="{{ $data->shape }}" data-atributo-id="{{ $atributoId }}">
                                    @foreach($terminosObjeto as $termino)
                                        @php
                                            $variacion = null;
                                            if (isset($terminosConVariacion[$termino->id])) {
                                                foreach ($data->variaciones as $v) {
                                                    foreach ($v->atributos as $t) {
                                                        if ($t->atributo_id == $atributoId && $t->id == $termino->id) {
                                                            $variacion = $v;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            $disponible = $variacion !== null;
                                            $valorExtra = isset($valorExtraMap[$atributoId . '_' . $termino->id]) 
                                                ? $valorExtraMap[$atributoId . '_' . $termino->id] 
                                                : null;
                                            
                                            $imagenUrl = null;
                                            if ($valorExtra) {
                                                // Si valor_extra es una URL completa o relativa
                                                if (filter_var($valorExtra, FILTER_VALIDATE_URL)) {
                                                    $imagenUrl = $valorExtra;
                                                } else {
                                                    $imagenUrl = asset('/' . $valorExtra);
                                                }
                                            } elseif ($variacion && $variacion->imagenes->isNotEmpty()) {
                                                $imagenUrl = asset('/' . $variacion->imagenes->first()->imagen_path);
                                            }
                                        @endphp
                                        <div class="variation-option image-option" 
                                            style="border-radius: {{ $data->shape === 'Circle' ? '50%' : ($data->shape === 'Rounded Corner' ? '4px' : '2px') }};"
                                            data-variacion-id="{{ $variacion ? $variacion->id : '' }}"
                                            data-atributo-id="{{ $atributoId }}"
                                            data-termino-id="{{ $termino->id }}"
                                            data-imagen="{{ $imagenUrl ?? '' }}"
                                            data-stock="{{ $variacion ? $variacion->stock : 0 }}"
                                            data-precio="{{ $variacion ? $variacion->precio_regular : '' }}"
                                            data-precio-rebajado="{{ $variacion ? $variacion->precio_rebajado : '' }}"
                                            data-backorders="{{ $variacion ? $variacion->backorders : false }}"
                                            data-gestion-inventario="{{ $variacion ? $variacion->gestion_inventario : false }}"
                                            data-estado-inventario="{{ $variacion ? $variacion->estado_inventario : 'existe' }}"
                                            data-disponible="{{ $disponible ? 1 : 0 }}"
                                            title="{{ $termino->nombre }}{{ !$disponible ? ' (No disponible)' : '' }}">
                                            @if($imagenUrl)
                                                <img src="{{ $imagenUrl }}" alt="{{ $termino->nombre }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: {{ $data->shape === 'Circle' ? '50%' : ($data->shape === 'Rounded Corner' ? '4px' : '2px') }};">
                                            @else
                                                <span class="image-placeholder">{{ $termino->nombre[0] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                
                            @else
                                <div class="variation-options label-options" data-tipo="label" data-shape="{{ $data->shape }}" data-atributo-id="{{ $atributoId }}">
                                    @foreach($terminosObjeto as $termino)
                                        @php
                                            $variacion = null;
                                            if (isset($terminosConVariacion[$termino->id])) {
                                                foreach ($data->variaciones as $v) {
                                                    foreach ($v->atributos as $t) {
                                                        if ($t->atributo_id == $atributoId && $t->id == $termino->id) {
                                                            $variacion = $v;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                            $disponible = $variacion !== null;
                                        @endphp
                                        <div class="variation-option label-option" 
                                            data-variacion-id="{{ $variacion ? $variacion->id : '' }}"
                                            data-atributo-id="{{ $atributoId }}"
                                            data-termino-id="{{ $termino->id }}"
                                            data-stock="{{ $variacion ? $variacion->stock : 0 }}"
                                            data-precio="{{ $variacion ? $variacion->precio_regular : '' }}"
                                            data-precio-rebajado="{{ $variacion ? $variacion->precio_rebajado : '' }}"
                                            data-backorders="{{ $variacion ? $variacion->backorders : false }}"
                                            data-gestion-inventario="{{ $variacion ? $variacion->gestion_inventario : false }}"
                                            data-estado-inventario="{{ $variacion ? $variacion->estado_inventario : 'existe' }}"
                                            data-disponible="{{ $disponible ? 1 : 0 }}"
                                            title="{{ $termino->nombre }}{{ !$disponible ? ' (No disponible)' : '' }}">
                                            <span class="label-text">{{ $termino->nombre }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                <script id="variacionesData" type="application/json">
                    {!! json_encode($producto->variaciones->map(function($v) {
                        return [
                            'id' => $v->id,
                            'sku' => $v->sku,
                            'precio_regular' => $v->precio_regular,
                            'precio_rebajado' => $v->precio_rebajado,
                            'stock' => $v->stock,
                            'gestion_inventario' => $v->gestion_inventario,
                            'estado_inventario' => $v->estado_inventario,
                            'backorders' => $v->backorders,
                            'imagenes' => $v->imagenes->map(function($img) {
                                return ['imagen_path' => $img->imagen_path];
                            }),
                            'atributos' => $v->atributos->map(function($t) {
                                return [
                                    'id' => $t->id,
                                    'nombre' => $t->nombre,
                                    'atributo_id' => $t->atributo_id
                                ];
                            })
                        ];
                    })) !!}
                </script>
            @endif

            @if(!$esAgrupado)
                <div class="product-stock">
                    @if($producto->tipo_producto === 'variable')
                        {{-- Para productos variables, mostrar mensaje por defecto --}}
                        <span class="text-muted">Selecciona todas las opciones</span>
                    @elseif($gestionaInventario && $stockDisponible > 0)
                        <span class="in-stock">En stock ({{ $stockDisponible }} unidades)</span>
                    @elseif($gestionaInventario && $stockDisponible <= 0 && $backordersPermitidos)
                        <span class="on-backorder">Disponible por pedido</span>
                    @elseif(!$gestionaInventario && $estadoInventario === 'existe')
                        <span class="in-stock">Disponible</span>
                    @elseif(!$gestionaInventario && $estadoInventario === 'reservar')
                        <span class="on-backorder">Disponible por pedido</span>
                    @else
                        <span class="out-of-stock">Agotado</span>
                    @endif
                </div>
            @endif

            @if($esAgrupado && $productosHijos->count() > 0)
                <div class="grouped-products-section">
                    <h4 class="grouped-title">Productos incluidos en este paquete</h4>
                    <div class="grouped-products-list">
                        @foreach($productosHijos as $hijo)
                            @php
                                $hijoPuedeComprar = $hijo->puede_comprar;
                                $hijoStock = $hijo->stock ?? 0;
                                $hijoEstadoInventario = $hijo->estado_stock;
                                $hijoBackorders = $hijo->backorders ?? false;
                                $hijoGestionaInventario = $hijo->gestion_inventario ?? false;
                                $hijoVendidoIndividualmente = $hijo->vendido_individualmente ?? false;
                                
                                if ($hijoVendidoIndividualmente) {
                                    $hijoMax = 1;
                                    $hijoMin = 0;
                                    $hijoDefault = 0; 
                                } else {
                                    $hijoMax = ($hijoGestionaInventario && $hijoStock > 0) ? $hijoStock : 99;
                                    $hijoMin = 0;
                                    $hijoDefault = 0; 
                                }
                            @endphp
                            <div class="grouped-product-item" data-product-id="{{ $hijo->id }}">
                                <div class="grouped-product-info">
                                    <div class="grouped-product-image">
                                        @if($hijo->imagen_miniatura)
                                            <img src="{{ asset('/' . $hijo->imagen_miniatura) }}" alt="{{ $hijo->nombre }}">
                                        @else
                                            <i class="bi bi-box"></i>
                                        @endif
                                    </div>
                                    <div class="grouped-product-details">
                                        <div class="grouped-product-name">{{ $hijo->nombre }}</div>
                                        <div class="grouped-product-price">{{ $hijo->precio_formateado }}</div>
                                        <div class="grouped-product-stock">
                                            @if($hijoGestionaInventario && $hijoStock > 0)
                                                <span class="in-stock-small">En stock ({{ $hijoStock }})</span>
                                            @elseif($hijoGestionaInventario && $hijoStock <= 0 && $hijoBackorders)
                                                <span class="on-backorder-small">Por pedido</span>
                                            @elseif(!$hijoGestionaInventario && $hijoEstadoInventario === 'existe')
                                                <span class="in-stock-small">Disponible</span>
                                            @elseif(!$hijoGestionaInventario && $hijoEstadoInventario === 'reservar')
                                                <span class="on-backorder-small">Por pedido</span>
                                            @else
                                                <span class="out-of-stock-small">Agotado</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="grouped-product-quantity">
                                    @if($hijoPuedeComprar)
                                        <div class="quantity-selector grouped-qty">
                                            <button class="qty-btn minus grouped-minus" 
                                                    data-id="{{ $hijo->id }}" 
                                                    {{ $hijoMax <= 0 ? 'disabled' : '' }}>-</button>
                                            <input type="number" 
                                                value="{{ $hijoDefault }}" 
                                                min="{{ $hijoMin }}" 
                                                max="{{ $hijoMax }}" 
                                                step="1" 
                                                class="qty-input grouped-qty-input" 
                                                data-product-id="{{ $hijo->id }}"
                                                data-max="{{ $hijoMax }}"
                                                data-min="{{ $hijoMin }}"
                                                data-gestion-inventario="{{ $hijoGestionaInventario ? '1' : '0' }}"
                                                data-backorders="{{ $hijoBackorders ? '1' : '0' }}">
                                            <button class="qty-btn plus grouped-plus" 
                                                    data-id="{{ $hijo->id }}" 
                                                    {{ $hijoMax <= 0 ? 'disabled' : '' }}>+</button>
                                        </div>
                                        @if($hijoVendidoIndividualmente)
                                            <small class="text-muted grouped-limit">* Máx 1 unidad</small>
                                        @endif
                                    @else
                                        <span class="text-muted">No disponible</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($puedeComprar && $productosHijos->some(fn($h) => $h->puede_comprar))
                    <div class="cart-actions grouped-cart-actions">
                        <button class="add-grouped-to-cart-btn" id="addGroupedToCart">
                            <i class="bi bi-cart-plus"></i> Añadir seleccionados al carrito
                        </button>
                        <button class="add-grouped-to-cart-btn reserve-btn d-none" id="reserveGrouped">
                            <i class="bi bi-clock-history"></i> Reservar seleccionados
                        </button>
                    </div>
                    <div id="groupedCartMessage" class="grouped-cart-message"></div>
                @endif

            @elseif(!$esAgrupado && $puedeComprar)
                <div class="cart-actions" @if($producto->tipo_producto === 'variable') style="display: none;" @endif>
                    <div class="quantity-selector">
                        <button class="qty-btn minus" {{ $maxCantidad <= 1 ? 'disabled' : '' }}>-</button>
                        <input type="number" 
                               value="1" 
                               min="1" 
                               max="{{ $maxCantidad }}" 
                               step="1" 
                               class="qty-input"
                               id="qtyInput"
                               data-max="{{ $maxCantidad }}">
                        <button class="qty-btn plus" {{ $maxCantidad <= 1 ? 'disabled' : '' }}>+</button>
                    </div>
                    
                    @if($vendidoIndividualmente)
                        <small class="text-muted ms-2">* Solo se permite 1 unidad por pedido</small>
                    @endif

                    @if($backordersPermitidos || $estadoInventario === 'reservar')
                        <button class="add-to-cart-btn reserve-btn">
                            <i class="bi bi-clock-history"></i> Reservar producto
                        </button>
                    @else
                        <button class="add-to-cart-btn">
                            <i class="bi bi-cart-plus"></i> Añadir al carrito
                        </button>
                    @endif
                </div>
            @endif

            <div class="product-meta">
                <div class="meta-item">
                    <i class="bi bi-folder me-2"></i>
                    <span class="meta-label">Categoría:</span>
                    <a href="{{ route('categoria.productos.completa', $producto->subcategoria->categoria->id) }}" 
                       class="meta-link">
                        {{ $producto->subcategoria->categoria->nombre }}
                    </a>
                </div>
                <div class="meta-item">
                    <i class="bi bi-tags me-2"></i>
                    <span class="meta-label">Subcategoría:</span>
                    <a href="{{ route('productos.subcategoria', $producto->subcategoria->id) }}" 
                       class="meta-link">
                        {{ $producto->subcategoria->nombre }}
                    </a>
                </div>
                @if($producto->etiquetas->count())
                    <div class="meta-item meta-tags">
                        <i class="bi bi-tag me-2"></i>
                        <span class="meta-label">Etiquetas:</span>
                        <div class="tags-wrapper">
                            @foreach($producto->etiquetas as $etiqueta)
                                <span class="tag" style="background-color: #eee; color: #000000">
                                    <i class="bi bi-hash me-1"></i>{{ $etiqueta->nombre }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="product-description-tabs">
        <ul class="tabs-nav">
            <li class="active" data-tab="description">Descripción</li>
            <li data-tab="specifications">Especificaciones</li>
            @if($permiteValoraciones)
                <li data-tab="reviews">Valoraciones ({{ $producto->rating_count }})</li>
            @endif
        </ul>
        <div class="tab-content active" id="tab-description">
            <div class="full-description">
                {!! $producto->descripcion_completa !!}
            </div>
        </div>
        <div class="tab-content" id="tab-specifications">
            <div class="specifications-grid">
                @php
                    $atributosVisibles = $producto->atributos()
                        ->wherePivot('visible', true)
                        ->wherePivot('variacion', false)
                        ->with(['terminos' => function($query) use ($producto) {
                            $query->whereHas('productoAtributos', function($q) use ($producto) {
                                $q->where('producto_id', $producto->id)
                                ->where('visible', true)
                                ->where('variacion', false);
                            });
                        }])
                        ->get();
                @endphp

                @forelse($atributosVisibles as $atributo)
                    @php
                        $nombresTerminos = $atributo->terminos->pluck('nombre')->implode(', ');
                    @endphp
                    <div class="spec-item">
                        <span class="spec-label">{{ $atributo->nombre }}</span>
                        <span class="spec-value">
                            @if($nombresTerminos)
                                {{ $nombresTerminos }}
                            @else
                                <span class="text-muted">Sin especificar</span>
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="spec-item">
                        <span class="spec-label">Sin especificaciones</span>
                        <span class="spec-value text-muted">No hay atributos visibles</span>
                    </div>
                @endforelse
                
                @if($producto->peso && $producto->peso > 0)
                    <div class="spec-item">
                        <span class="spec-label">Peso</span>
                        <span class="spec-value">
                            {{ number_format($producto->peso, 2) }} 
                            {{ $producto->peso_unidad ? strtoupper($producto->peso_unidad) : 'kg' }}
                        </span>
                    </div>
                @endif
                
                @if(($producto->longitud && $producto->longitud > 0) || 
                    ($producto->anchura && $producto->anchura > 0) || 
                    ($producto->altura && $producto->altura > 0))
                    <div class="spec-item">
                        <span class="spec-label">Dimensiones</span>
                        <span class="spec-value">
                            @if($producto->longitud && $producto->longitud > 0)
                                {{ number_format($producto->longitud, 2) }} cm
                            @endif
                            @if($producto->anchura && $producto->anchura > 0)
                                × {{ number_format($producto->anchura, 2) }} cm
                            @endif
                            @if($producto->altura && $producto->altura > 0)
                                × {{ number_format($producto->altura, 2) }} cm
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </div>
        
        @if($permiteValoraciones)
            <div class="tab-content" id="tab-reviews">
                <div class="reviews-list">
                    @php
                        $valoraciones = $producto->valoraciones()->where('aprobado', true)->get();
                    @endphp
                    
                    @forelse($valoraciones as $review)
                        <div class="review-item">
                            <div class="review-header">
                                <strong>{{ $review->usuario->nombres ?? 'Anónimo' }}</strong>
                                <span class="review-date">
                                    @if($review->created_at == $review->updated_at)
                                        {{ $review->created_at->diffForHumans() }}
                                    @else
                                        {{ $review->updated_at->diffForHumans() }}
                                    @endif
                                </span>
                            </div>
                            <div class="review-stars">
                                @for($i=1;$i<=5;$i++)
                                    <i class="bi bi-star{{ $i <= $review->puntuacion ? '-fill' : '' }}"></i>
                                @endfor
                            </div>
                            @if($review->comentario)
                                <p class="review-comment">{{ $review->comentario }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="review-empty">
                            <i class="bi bi-chat-text"></i>
                            <p>No hay valoraciones aún. ¡Sé el primero en opinar!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    @if($producto->productosRelacionados->count())
        <div class="related-products">
            <h3>Productos relacionados</h3>
            <div class="products-grid">
                @foreach($producto->productosRelacionados->take(4) as $relacionado)
                    @include('tienda.partials.product-card-enhanced', ['producto' => $relacionado])
                @endforeach
            </div>
        </div>
    @endif
</div>