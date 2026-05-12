<div class="category-full-container">
    <div class="category-header">
        <a href="{{ route('categoria.productos.completa', $categoria->id) }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Volver a {{ $categoria->nombre }}
        </a>
        <div class="category-info">
            <div class="category-icon">
                <i class="bi {{ $subcategoria->icono ?? 'bi-tag-fill' }}"></i>
            </div>
            <h1>{{ $subcategoria->nombre }}</h1>
            <p class="category-description">Todos los productos de esta subcategoría</p>
        </div>
    </div>

    <div class="category-content">
        <!-- Sidebar de filtros (idéntico al de categoría, pero solo con atributos, rating y orden) -->
        <aside class="filters-sidebar">
            <form method="GET" action="{{ route('productos.subcategoria', $subcategoria->id) }}" id="filterForm">

                <!-- ===== Atributos (acordeón) ===== -->
                @foreach($atributosConTerminos as $atributo)
                    <div class="filter-accordion-item" data-accordion="attr_{{ $atributo->id }}">
                        <div class="filter-accordion-header">
                            <i class="bi bi-sliders2 me-2"></i> {{ $atributo->nombre }}
                            <i class="bi bi-chevron-down accordion-icon"></i>
                        </div>
                        <div class="filter-accordion-body">
                            @foreach($atributo->terminos as $termino)
                                <label class="filter-checkbox">
                                    <input type="checkbox" name="atributo_termino[]" value="{{ $termino->id }}"
                                        {{ in_array($termino->id, (array)($filtros['atributo_termino'] ?? [])) ? 'checked' : '' }}>
                                    {{ $termino->nombre }}
                                    <span class="count">({{ $termino->producto_atributos_count }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- ===== Valoración mínima (acordeón) ===== -->
                <div class="filter-accordion-item" data-accordion="rating">
                    <div class="filter-accordion-header">
                        <i class="bi bi-star-fill me-2"></i> Valoración mínima
                        <i class="bi bi-chevron-down accordion-icon"></i>
                    </div>
                    <div class="filter-accordion-body">
                        @for($i = 5; $i >= 1; $i--)
                            <label class="filter-radio">
                                <input type="radio" name="rating_min" value="{{ $i }}"
                                    {{ ($filtros['rating_min'] ?? 0) == $i ? 'checked' : '' }}>
                                <span class="stars-filter">
                                    @for($s = 1; $s <= $i; $s++) <i class="bi bi-star-fill"></i> @endfor
                                    @for($s = $i+1; $s <= 5; $s++) <i class="bi bi-star"></i> @endfor
                                </span>
                            </label>
                        @endfor
                        <label class="filter-radio">
                            <input type="radio" name="rating_min" value="0" {{ !isset($filtros['rating_min']) || $filtros['rating_min'] == 0 ? 'checked' : '' }}>
                            <span>Todas</span>
                        </label>
                    </div>
                </div>

                <!-- ===== Ordenar (siempre visible) ===== -->
                <div class="filter-group">
                    <h4><i class="bi bi-sort-down"></i> Ordenar por</h4>
                    <select name="orden" class="form-select" onchange="this.form.submit()">
                        <option value="novedad" {{ ($filtros['orden'] ?? 'novedad') == 'novedad' ? 'selected' : '' }}>Novedades</option>
                        <option value="precio_asc" {{ ($filtros['orden'] ?? '') == 'precio_asc' ? 'selected' : '' }}>Precio: menor a mayor</option>
                        <option value="precio_desc" {{ ($filtros['orden'] ?? '') == 'precio_desc' ? 'selected' : '' }}>Precio: mayor a menor</option>
                        <option value="nombre" {{ ($filtros['orden'] ?? '') == 'nombre' ? 'selected' : '' }}>Nombre A-Z</option>
                    </select>
                </div>

                <button type="submit" class="btn-apply-filters">Aplicar filtros</button>
                <a href="{{ route('productos.subcategoria', $subcategoria->id) }}" class="btn-clear-filters">Limpiar</a>
            </form>
        </aside>

        <!-- Listado de productos -->
        <div class="products-results">
            <div class="results-header">
                <span class="results-count">{{ $productos->total() }} productos encontrados</span>
            </div>

            @if($productos->count())
                <div class="products-grid">
                    @foreach($productos as $producto)
                        @include('tienda.partials.product-card-enhanced', ['producto' => $producto])
                    @endforeach
                </div>
                <div class="pagination-wrapper">
                    {{ $productos->links('vendor.pagination.bootstrap-5-simple') }}
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-emoji-frown empty-icon"></i>
                    <h3>No se encontraron productos</h3>
                    <p>Prueba con otros filtros o explora otras subcategorías.</p>
                    <a href="{{ route('categoria.productos.completa', $categoria->id) }}" class="btn-primary-custom">Ver todos los productos de la categoría</a>
                </div>
            @endif
        </div>
    </div>
</div>