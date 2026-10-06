<div class="product-card-enhanced"  data-product-id="{{ $producto->id }}"
     @if(isset($producto->variaciones_imagenes))
     data-variacion-imagenes='{{ json_encode($producto->variaciones_imagenes) }}'
     @endif>    
     <div class="product-image-slider">
        <div class="slider-container">
            <div class="slider-images">
                @php
                    $allImages = [];
                    if ($producto->imagen_miniatura) {
                        $allImages[] = asset($producto->imagen_miniatura);
                    }
                    foreach ($producto->imagenes as $img) {
                        if ($img->imagen_path) {
                            $allImages[] = asset($img->imagen_path);
                        }
                    }
                    if (empty($allImages)) {
                        $allImages[] = asset('img/default-product.png');
                    }
                @endphp
                @foreach($allImages as $index => $imgUrl)
                    <div class="slide {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ $imgUrl }}" alt="{{ $producto->nombre }}" loading="lazy">
                    </div>
                @endforeach
            </div>
            @if(count($allImages) > 1)
                <button class="slider-prev" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
                <button class="slider-next" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
                <div class="slider-dots">
                    @foreach($allImages as $idx => $img)
                        <span class="dot {{ $idx === 0 ? 'active' : '' }}" data-index="{{ $idx }}"></span>
                    @endforeach
                </div>
            @endif
        </div>
        @if($producto->descuento_porcentaje)
            <span class="discount-badge">
                -{{ $producto->descuento_porcentaje }}%
            </span>
        @endif
    </div>

    <div class="product-info">
        <div class="product-brand">
            @if($producto->marca)
                <i class="bi bi-tag"></i> {{ $producto->marca }}
            @else
                <span class="no-brand">Sin marca</span>
            @endif
        </div>
        
        <h3 class="product-title">
            <a href="{{ route('producto.detalle', $producto->id) }}">{{ $producto->nombre }}</a>
        </h3>

        <div class="product-rating" data-product-id="{{ $producto->id }}">
            <div class="stars-wrapper" data-rating="{{ $producto->rating }}">
                @for($i = 1; $i <= 5; $i++)
                    <i class="star bi bi-star{{ $i <= round($producto->rating) ? '-fill' : '' }}" 
                       data-value="{{ $i }}"></i>
                @endfor
            </div>
            <span class="rating-count">({{ $producto->rating_count }} reseñas)</span>
        </div>

        @if($producto->tipo_producto === 'variable' && isset($producto->variaciones_tarjeta) && !empty($producto->variaciones_tarjeta['terminos']))
            <div class="product-variations-card">
                @php
                    $variacionesData = $producto->variaciones_tarjeta;
                @endphp
                
                <div class="variation-options-card" 
                    data-atributo-id="{{ $variacionesData['atributo_id'] }}"
                    data-tipo="{{ $variacionesData['tipo'] }}"
                    data-shape="{{ $variacionesData['shape'] }}"
                    data-product-id="{{ $producto->id }}">
                    
                    @foreach($variacionesData['terminos'] as $termino)
                        @php
                            $imagenUrl = $termino['imagen_url'] 
                                ? asset('/' . $termino['imagen_url']) 
                                : null;
                            $colorValue = $termino['valor_extra'] ?? '#cccccc';
                            $nombre = $termino['nombre'];
                            $terminoId = $termino['id'];
                            $variacionId = $termino['variacion_id'];
                            $stock = $termino['stock'] ?? 0;
                            $tieneStock = $stock > 0;
                            $precioRegular = $termino['precio_regular'] ?? null;
                            $precioRebajado = $termino['precio_rebajado'] ?? null;
                        @endphp
                        
                        @if($variacionesData['tipo'] === 'Color')
                            <div class="variation-option-card color-option-card {{ $tieneStock ? '' : 'disabled' }}"
                                data-termino-id="{{ $terminoId }}"
                                data-variacion-id="{{ $variacionId }}"
                                data-color="{{ $colorValue }}"
                                data-stock="{{ $stock }}"
                                data-precio="{{ $precioRegular }}"
                                data-precio-rebajado="{{ $precioRebajado }}"
                                title="{{ $nombre }}{{ !$tieneStock ? ' (Sin stock)' : '' }}">
                                <span class="color-swatch-card" 
                                    style="background-color: {{ $colorValue }}; 
                                            border-radius: {{ $variacionesData['shape'] === 'Circle' ? '50%' : ($variacionesData['shape'] === 'Rounded Corner' ? '4px' : '2px') }};">
                                </span>
                            </div>
                            
                        @elseif($variacionesData['tipo'] === 'Image')
                            <div class="variation-option-card image-option-card {{ $tieneStock ? '' : 'disabled' }}"
                                data-termino-id="{{ $terminoId }}"
                                data-variacion-id="{{ $variacionId }}"
                                data-imagen="{{ $imagenUrl ?? '' }}"
                                data-stock="{{ $stock }}"
                                data-precio="{{ $precioRegular }}"
                                data-precio-rebajado="{{ $precioRebajado }}"
                                title="{{ $nombre }}{{ !$tieneStock ? ' (Sin stock)' : '' }}">
                                @if($imagenUrl)
                                    <img src="{{ $imagenUrl }}" alt="{{ $nombre }}" 
                                        style="width: 32px; height: 32px; object-fit: cover; 
                                                border-radius: {{ $variacionesData['shape'] === 'Circle' ? '50%' : ($variacionesData['shape'] === 'Rounded Corner' ? '4px' : '2px') }};">
                                @else
                                    <span class="image-placeholder-card">{{ substr($nombre, 0, 1) }}</span>
                                @endif
                            </div>
                            
                        @else
                            <!-- Label o Default -->
                            <div class="variation-option-card label-option-card {{ $tieneStock ? '' : 'disabled' }}"
                                data-termino-id="{{ $terminoId }}"
                                data-variacion-id="{{ $variacionId }}"
                                data-stock="{{ $stock }}"
                                data-precio="{{ $precioRegular }}"
                                data-precio-rebajado="{{ $precioRebajado }}"
                                title="{{ $nombre }}{{ !$tieneStock ? ' (Sin stock)' : '' }}">
                                <span class="label-text-card">{{ $nombre }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <div class="product-price-wrapper" id="productPriceWrapper{{ $producto->id }}">
            @if($producto->tipo_producto === 'variable')
                @if($producto->rango_precios->tiene_rebaja && $producto->precio_regular_original)
                    <span class="old-price">{{ $producto->precio_regular_original }}</span>
                    <span class="sale-price">{{ $producto->precio_formateado }}</span>
                @else
                    <span class="regular-price">{{ $producto->precio_formateado }}</span>
                @endif
                
            @elseif($producto->tipo_producto === 'agrupado')
                @if($producto->rango_precios->tiene_rebaja && $producto->precio_regular_original)
                    <span class="old-price">{{ $producto->precio_regular_original }}</span>
                    <span class="sale-price">{{ $producto->precio_formateado }}</span>
                @else
                    <span class="regular-price">{{ $producto->precio_formateado }}</span>
                @endif
                
            @else
                @if($producto->precio_rebajado && $producto->precio_rebajado > 0 && 
                    (is_null($producto->fecha_fin_rebaja) || $producto->fecha_fin_rebaja >= now()))
                    @if($producto->precio_regular > 0)
                        <span class="old-price">S/.{{ number_format($producto->precio_regular, 2) }}</span>
                    @endif
                    <span class="sale-price">S/.{{ number_format($producto->precio_rebajado, 2) }}</span>
                @elseif($producto->precio_regular > 0)
                    <span class="regular-price">S/.{{ number_format($producto->precio_regular, 2) }}</span>
                @elseif($producto->precio_rebajado > 0)
                    <span class="regular-price">S/.{{ number_format($producto->precio_rebajado, 2) }}</span>
                @else
                    <span class="regular-price">Precio no disponible</span>
                @endif
            @endif
        </div>

        <div class="product-actions">
            <a href="{{ route('producto.detalle', $producto->id) }}" class="btn-view">
                Ver detalles <i class="bi bi-eye"></i>
            </a>
            <!-- <button class="btn-add-to-cart-quick" data-id="{{ $producto->id }}" title="Agregar al carrito">
                <i class="bi bi-cart-plus"></i>
            </button> -->
        </div>
    </div>
</div>