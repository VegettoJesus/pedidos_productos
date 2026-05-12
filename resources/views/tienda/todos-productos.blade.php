<div class="category-full-container">
    <div class="category-header">
        <div class="category-icon">
            <i class="bi bi-grid-3x3-gap-fill"></i>
        </div>
        <h1>Todos los productos</h1>
        <p class="category-description">Descubre nuestra colección completa de tecnología e innovación</p>
    </div>

    <div class="category-content">
        <!-- Sidebar de filtros -->
        <aside class="filters-sidebar">
            <form method="GET" action="{{ route('tienda.todos-productos') }}" id="filterForm">
                
                <!-- ===== Categorías (acordeón) ===== -->
                <div class="filter-accordion-item" data-accordion="categorias">
                    <div class="filter-accordion-header">
                        <i class="bi bi-tags me-2"></i> Categorías
                        <i class="bi bi-chevron-down accordion-icon"></i>
                    </div>
                    <div class="filter-accordion-body">
                        @foreach($categorias as $cat)
                            <label class="filter-checkbox">
                                <input type="checkbox" name="categoria[]" value="{{ $cat->id }}"
                                    {{ in_array($cat->id, (array)($filtros['categoria'] ?? [])) ? 'checked' : '' }}>
                                {{ $cat->nombre }}
                                <span class="count">({{ $cat->productos_count ?? 0 }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- ===== Subcategorías (acordeón, se muestra si hay alguna categoría seleccionada) ===== -->
                @if($subcategoriasDisponibles->count())
                    <div class="filter-accordion-item" data-accordion="subcategorias">
                        <div class="filter-accordion-header">
                            <i class="bi bi-diagram-2 me-2"></i> Subcategorías
                            <i class="bi bi-chevron-down accordion-icon"></i>
                        </div>
                        <div class="filter-accordion-body">
                            @foreach($subcategoriasDisponibles as $sub)
                                <label class="filter-checkbox">
                                    <input type="checkbox" name="subcategoria[]" value="{{ $sub->id }}"
                                        {{ in_array($sub->id, (array)($filtros['subcategoria'] ?? [])) ? 'checked' : '' }}>
                                    {{ $sub->nombre }}
                                    <span class="count">({{ $sub->productos_count }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- ===== Atributos (acordeones dinámicos) ===== -->
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
                <a href="{{ route('tienda.todos-productos') }}" class="btn-clear-filters">Limpiar</a>
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
                    <p>Prueba con otros filtros o explora otras categorías.</p>
                    <a href="{{ route('tienda.todos-productos') }}" class="btn-primary-custom">Ver todos</a>
                </div>
            @endif
        </div>
    </div>
</div>