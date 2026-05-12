<div class="product-detail-container">
    <div class="product-detail-grid">
        <!-- Columna izquierda: Galería de imágenes -->
        <div class="product-gallery">
            <div class="main-image">
                <img id="mainProductImage" 
                     src="{{ $producto->imagen_miniatura ? asset('/' . $producto->imagen_miniatura) : asset('img/no-image.png') }}" 
                     alt="{{ $producto->nombre }}">
            </div>
            @if($producto->imagenes->count())
                <div class="thumbnail-list">
                    @foreach($producto->imagenes as $imagen)
                        <div class="thumbnail" data-image="{{ asset('/' . $imagen->imagen_path) }}">
                            <img src="{{ asset('/' . $imagen->imagen_path) }}" alt="{{ $imagen->alt_text ?? $producto->nombre }}">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Columna derecha: Info del producto -->
        <div class="product-info-detailed">
            <div class="product-brand">{{ $producto->marca ?? 'Marca' }}</div>
            <h1 class="product-title">{{ $producto->nombre }}</h1>
            
            <!-- Rating -->
            <div class="product-rating">
                <div class="stars-wrapper" data-rating="{{ $producto->rating }}">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star star" data-value="{{ $i }}"></i>
                    @endfor
                </div>
                <span class="rating-count">({{ $producto->rating_count }} reseñas)</span>
            </div>

            <!-- Precio -->
            <div class="product-price-detailed">
                @if($producto->descuento_porcentaje)
                    <span class="old-price">{{ $producto->precio_regular_original }}</span>
                    <span class="sale-price">{{ $producto->precio_formateado }}</span>
                    <span class="discount-badge-large">-{{ $producto->descuento_porcentaje }}%</span>
                @else
                    <span class="regular-price">{{ $producto->precio_formateado }}</span>
                @endif
            </div>

            <!-- Descripción corta / extracto -->
            <div class="product-short-description">
                {!! nl2br(e(Str::limit($producto->descripcion, 200))) !!}
            </div>

            <!-- Variables: solo si es producto variable -->
            @if($producto->tipo_producto === 'variable')
                <div class="product-variations">
                    <h4>Variaciones disponibles</h4>
                    @foreach($producto->variaciones->groupBy('atributos.first.atributo_id') as $atributoId => $variacionesGrupo)
                        @php
                            $atributo = $variacionesGrupo->first()->atributos->first()->atributo ?? null;
                        @endphp
                        @if($atributo)
                            <div class="variation-attribute">
                                <label>{{ $atributo->nombre }}:</label>
                                <select class="variation-select" data-attribute="{{ $atributo->id }}">
                                    <option value="">Seleccionar {{ $atributo->nombre }}</option>
                                    @foreach($variacionesGrupo as $variacion)
                                        @php $termino = $variacion->atributos->first(); @endphp
                                        <option value="{{ $variacion->id }}" data-price="{{ $variacion->precio_regular }}">
                                            {{ $termino->nombre ?? 'Opción' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="selected-variation-price"></div>
            @endif

            <!-- Stock y cantidad -->
            <div class="product-stock">
                @if($producto->estado_inventario === 'existe' && $producto->stock > 0)
                    <span class="in-stock">En stock ({{ $producto->stock }} unidades)</span>
                @elseif($producto->estado_inventario === 'reservar')
                    <span class="on-backorder">Disponible por pedido</span>
                @else
                    <span class="out-of-stock">Agotado</span>
                @endif
            </div>

            <!-- Botón añadir al carrito -->
            @if($producto->estado_inventario !== 'agotado')
                <div class="cart-actions">
                    <div class="quantity-selector">
                        <button class="qty-btn minus">-</button>
                        <input type="number" value="1" min="1" max="{{ $producto->stock }}" step="1" class="qty-input">
                        <button class="qty-btn plus">+</button>
                    </div>
                    <button class="add-to-cart-btn" data-product-id="{{ $producto->id }}">
                        <i class="bi bi-cart-plus"></i> Añadir al carrito
                    </button>
                </div>
            @endif

            <!-- Categorías y etiquetas -->
            <div class="product-meta">
                <div>Categoría: 
                    <a href="{{ route('categoria.productos.completa', $producto->subcategoria->categoria->id) }}">
                        {{ $producto->subcategoria->categoria->nombre }}
                    </a>
                </div>
                <div>Subcategoría: 
                    <a href="{{ route('productos.subcategoria', $producto->subcategoria->id) }}">
                        {{ $producto->subcategoria->nombre }}
                    </a>
                </div>
                @if($producto->etiquetas->count())
                    <div>Etiquetas: 
                        @foreach($producto->etiquetas as $etiqueta)
                            <span class="tag">{{ $etiqueta->nombre }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Descripción completa -->
    <div class="product-description-tabs">
        <ul class="tabs-nav">
            <li class="active" data-tab="description">Descripción</li>
            <li data-tab="reviews">Valoraciones ({{ $producto->rating_count }})</li>
        </ul>
        <div class="tab-content active" id="tab-description">
            <div class="full-description">
                {!! nl2br(e($producto->descripcion)) !!}
            </div>
        </div>
        <div class="tab-content" id="tab-reviews">
            <div class="reviews-list">
                @forelse($producto->valoraciones()->where('aprobado', true)->get() as $review)
                    <div class="review-item">
                        <strong>{{ $review->usuario->nombres ?? 'Anónimo' }}</strong>
                        <div class="review-stars">
                            @for($i=1;$i<=5;$i++)
                                <i class="bi bi-star{{ $i <= $review->puntuacion ? '-fill' : '' }}"></i>
                            @endfor
                        </div>
                        <p>{{ $review->comentario }}</p>
                        <small>{{ $review->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p>No hay valoraciones aún. ¡Sé el primero en opinar!</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Productos relacionados (opcional) -->
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

<style>
/* Estilos específicos para detalle (complementa los que ya tienes) */
.product-detail-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
.product-detail-grid { display: flex; gap: 3rem; flex-wrap: wrap; }
.product-gallery { flex: 1; min-width: 300px; }
.main-image img { width: 100%; border-radius: 24px; border: 1px solid #eee; padding: 1rem; background: white; }
.thumbnail-list { display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap; }
.thumbnail { width: 80px; height: 80px; border-radius: 12px; overflow: hidden; cursor: pointer; border: 2px solid transparent; }
.thumbnail.active { border-color: var(--primary); }
.thumbnail img { width: 100%; height: 100%; object-fit: cover; }
.product-info-detailed { flex: 1.5; }
.product-title { font-size: 2rem; font-weight: 700; margin-bottom: 1rem; }
.product-price-detailed { margin: 1rem 0; font-size: 1.8rem; }
.old-price { text-decoration: line-through; color: #999; font-size: 1.2rem; margin-right: 1rem; }
.sale-price { color: #e74c3c; font-weight: bold; }
.discount-badge-large { background: var(--primary); display: inline-block; padding: 0.2rem 0.8rem; border-radius: 30px; margin-left: 1rem; font-size: 0.9rem; }
.product-stock { margin: 1rem 0; }
.in-stock { color: green; font-weight: 600; }
.out-of-stock { color: red; }
.cart-actions { display: flex; gap: 1rem; margin: 1.5rem 0; align-items: center; }
.quantity-selector { display: flex; border: 1px solid #ddd; border-radius: 40px; overflow: hidden; }
.qty-btn { background: #f5f5f5; border: none; width: 40px; font-size: 1.2rem; cursor: pointer; }
.qty-input { width: 60px; text-align: center; border: none; outline: none; }
.add-to-cart-btn { background: var(--primary); border: none; padding: 0.8rem 2rem; border-radius: 40px; font-weight: 600; transition: all 0.2s; }
.add-to-cart-btn:hover { background: #e69c1f; transform: scale(1.02); }
.product-meta { border-top: 1px solid #eee; padding-top: 1.5rem; margin-top: 1rem; font-size: 0.9rem; }
.tag { background: #f0f0f0; padding: 0.2rem 0.6rem; border-radius: 20px; margin: 0 0.2rem; display: inline-block; }
.tabs-nav { display: flex; gap: 2rem; border-bottom: 1px solid #eee; margin: 2rem 0 1rem; }
.tabs-nav li { cursor: pointer; padding: 0.5rem 0; font-weight: 500; position: relative; }
.tabs-nav li.active { color: var(--primary); }
.tabs-nav li.active::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background: var(--primary); }
.tab-content { display: none; padding: 1rem 0; }
.tab-content.active { display: block; }
.review-item { border-bottom: 1px solid #eee; padding: 1rem 0; }
@media (max-width: 768px) { .product-detail-grid { flex-direction: column; } }
</style>

<script>
    // Cambio de imagen principal al hacer clic en thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.addEventListener('click', function() {
            document.getElementById('mainProductImage').src = this.dataset.image;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
    // Selector de cantidad
    const minusBtn = document.querySelector('.qty-btn.minus');
    const plusBtn = document.querySelector('.qty-btn.plus');
    const qtyInput = document.querySelector('.qty-input');
    if (minusBtn && plusBtn) {
        minusBtn.addEventListener('click', () => {
            let val = parseInt(qtyInput.value);
            if (val > 1) qtyInput.value = val - 1;
        });
        plusBtn.addEventListener('click', () => {
            let val = parseInt(qtyInput.value);
            let max = parseInt(qtyInput.max);
            if (val < max) qtyInput.value = val + 1;
        });
    }
    // Pestañas (descripción / valoraciones)
    document.querySelectorAll('.tabs-nav li').forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.tab;
            document.querySelectorAll('.tabs-nav li').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(`tab-${targetTab}`).classList.add('active');
        });
    });
    
</script>