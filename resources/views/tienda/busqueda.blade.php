<div class="search-results-container">
    <div class="container py-4">
        <!-- Header -->
        <div class="search-header">
            <h1 class="search-title">
                <i class="bi bi-search me-2"></i>
                Resultados para: <span class="text-primary">{{ $query }}</span>
            </h1>
            <p class="search-subtitle text-muted">
                Se encontraron <strong>{{ $productos->total() }}</strong> productos
                @if($categorias->isNotEmpty() || $subcategorias->isNotEmpty())
                    y {{ $categorias->count() + $subcategorias->count() }} categorías relacionadas
                @endif
            </p>
        </div>

        <!-- Categorías y Subcategorías coincidentes -->
        @if($categorias->isNotEmpty() || $subcategorias->isNotEmpty())
        <div class="search-categories-section mb-4">
            <h5 class="section-label">
                <i class="bi bi-grid me-1"></i> Categorías encontradas
            </h5>
            <div class="d-flex flex-wrap gap-2">
                @foreach($categorias as $categoria)
                    <a href="{{ route('categoria.productos.completa', $categoria->id) }}" 
                       class="search-category-tag">
                        <i class="bi {{ $categoria->icono ?? 'bi-folder' }} me-1"></i>
                        {{ $categoria->nombre }}
                        <span class="badge bg-light text-dark ms-1">
                            <i class="bi bi-arrow-right"></i>
                        </span>
                    </a>
                @endforeach
                
                @foreach($subcategorias as $subcategoria)
                    <a href="{{ route('productos.subcategoria', $subcategoria->id) }}" 
                       class="search-category-tag">
                        <i class="bi {{ $subcategoria->icono ?? 'bi-tag' }} me-1"></i>
                        {{ $subcategoria->nombre }}
                        <span class="text-muted small">
                            en {{ $subcategoria->categoria->nombre }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Productos -->
        @if($productos->count() > 0)
            <div class="products-grid-enhanced">
                @foreach($productos as $producto)
                    @include('tienda.partials.product-card-enhanced', ['producto' => $producto])
                @endforeach
            </div>
            
            <div class="pagination-wrapper">
                {{ $productos->links('vendor.pagination.bootstrap-5-simple') }}
            </div>
        @else
            <!-- Estado vacío -->
            <div class="search-empty-state text-center py-5">
                <div class="empty-icon-wrapper">
                    <i class="bi bi-search-empty"></i>
                </div>
                <h3 class="mt-3">No encontramos resultados para "{{ $query }}"</h3>
                <p class="text-muted">
                    No tenemos productos que coincidan con tu búsqueda. 
                    Prueba con otras palabras o explora nuestras categorías.
                </p>
                <a href="{{ route('tienda.home') }}" class="btn-primary-custom mt-3">
                    <i class="bi bi-house me-2"></i> Volver al inicio
                </a>
            </div>
        @endif
    </div>
</div>