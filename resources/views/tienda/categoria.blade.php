<div class="category-page">
    <div class="category-header">
        <div class="category-icon">
            <i class="bi {{ $categoria->icono ?? 'bi-folder-fill' }}"></i>
        </div>
        <h1>{{ $categoria->nombre }}</h1>
        <p class="category-description">
            Explora todas las subcategorías y productos disponibles en {{ $categoria->nombre }}.
        </p>
    </div>

    @if($categoria->subcategorias->count() > 0)
        <div class="subcategories-grid">
            @foreach($categoria->subcategorias as $sub)
                <a href="{{ route('productos.subcategoria', $sub->id) }}" class="subcategory-card">
                    <div class="subcategory-icon">
                        <i class="bi {{ $sub->icono ?? 'bi-tag-fill' }}"></i>
                    </div>
                    <h3>{{ $sub->nombre }}</h3>
                    <p class="subcategory-count">
                        {{ $sub->productos()->where('estado', 'publicado')->count() }} productos
                    </p>
                    <span class="subcategory-link">Ver productos <i class="bi bi-arrow-right"></i></span>
                </a>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <i class="bi bi-inbox-fill empty-icon"></i>
            <h3>No hay subcategorías disponibles</h3>
            <p>Actualmente no hemos clasificado productos en esta categoría. Vuelve más tarde.</p>
            <a href="{{ route('tienda.home') }}" class="btn btn-primary-custom">
                <i class="bi bi-house"></i> Ir al inicio
            </a>
        </div>
    @endif

    @if($productos->count() > 0)
        <div class="featured-products">
            <div class="section-header">
                <h2><i class="bi bi-stars me-2" style="color: var(--primary);"></i> Productos destacados en {{ $categoria->nombre }}</h2>
                <a href="{{ route('categoria.productos.completa', $categoria->id) }}" class="view-all-link">
                    Ver todos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="product-carousel-wrapper">
                <button class="carousel-btn carousel-prev" aria-label="Anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div class="product-carousel">
                    @foreach($productos as $producto)
                        <div class="carousel-slide">
                            @include('tienda.partials.product-card-enhanced', ['producto' => $producto])
                        </div>
                    @endforeach
                </div>
                <button class="carousel-btn carousel-next" aria-label="Siguiente">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    @endif
</div>