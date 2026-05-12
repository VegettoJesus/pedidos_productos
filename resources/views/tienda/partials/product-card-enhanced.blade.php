<div class="product-card-enhanced" data-product-id="{{ $producto->id }}">
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

        <div class="product-price-wrapper">
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
            <button class="btn-add-to-cart-quick" data-id="{{ $producto->id }}" title="Agregar al carrito">
                <i class="bi bi-cart-plus"></i>
            </button>
        </div>
    </div>
</div>