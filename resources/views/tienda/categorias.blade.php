<div class="categories-page">
    <div class="page-header">
        <h1>Todas las Categorías</h1>
        <p>Explora nuestras categorías y descubre productos increíbles</p>
    </div>

    <div class="categories-grid">
        @forelse($categorias as $categoria)
            <div class="category-card">
                <a href="{{ route('productos.categoria', $categoria->id) }}" class="category-link">
                    <div class="category-icon">
                        <i class="bi {{ $categoria->icono ?? 'bi-folder-fill' }}"></i>
                    </div>
                    <h2>{{ $categoria->nombre }}</h2>
                    <p class="category-stats">
                        {{ $categoria->subcategorias->count() }} subcategorías
                    </p>
                    @if($categoria->subcategorias->count() > 0)
                        <ul class="subcategory-preview">
                            @foreach($categoria->subcategorias->take(3) as $sub)
                                <li>{{ $sub->nombre }}</li>
                            @endforeach
                            @if($categoria->subcategorias->count() > 3)
                                <li>+{{ $categoria->subcategorias->count() - 3 }} más</li>
                            @endif
                        </ul>
                    @endif
                    <span class="view-category">Ver categoría <i class="bi bi-arrow-right"></i></span>
                </a>
            </div>
        @empty
            <div class="empty-state">
                <i class="bi bi-folder-x empty-icon"></i>
                <h3>No hay categorías disponibles</h3>
                <p>Pronto agregaremos categorías emocionantes.</p>
                <a href="{{ route('tienda.home') }}" class="btn-primary-custom">Volver al inicio</a>
            </div>
        @endforelse
    </div>
</div>