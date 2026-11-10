<div class="category-full-container">
    <div class="category-header">
        <div class="category-info">
            <div class="category-icon">
                <i class="bi bi-percent"></i>
            </div>
            <h1>Ofertas Especiales</h1>
            <p class="category-description">
                Aprovecha los mejores descuentos en productos seleccionados.
            </p>
        </div>
    </div>

    <div class="category-content">
        <aside class="filters-sidebar">
            <form method="GET" action="{{ route('ofertas') }}" id="filterForm">

                {{-- Categorías (acordeón) --}}
                <div class="filter-accordion-item" data-accordion="categorias">
                    <div class="filter-accordion-header">
                        <i class="bi bi-tags me-2"></i> Categorías
                        <i class="bi bi-chevron-down accordion-icon"></i>
                    </div>
                    <div class="filter-accordion-body">
                        @forelse($categorias as $cat)
                            <label class="filter-checkbox">
                                <input type="checkbox" name="categoria[]" value="{{ $cat->id }}"
                                    {{ in_array($cat->id, (array)($filtros['categoria'] ?? [])) ? 'checked' : '' }}>
                                {{ $cat->nombre }}
                                <span class="count">({{ $cat->productos_count ?? 0 }})</span>
                            </label>
                        @empty
                            <p class="text-muted small mb-0">Sin categorías</p>
                        @endforelse
                    </div>
                </div>

                {{-- Subcategorías (solo si hay categorías seleccionadas) --}}
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

                {{-- Valoración mínima --}}
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
                            <input type="radio" name="rating_min" value="0"
                                {{ !isset($filtros['rating_min']) || $filtros['rating_min'] == 0 ? 'checked' : '' }}>
                            <span>Todas</span>
                        </label>
                    </div>
                </div>

                {{-- Ordenar --}}
                <div class="filter-group">
                    <h4><i class="bi bi-sort-down"></i> Ordenar por</h4>
                    <select name="orden" class="form-select" onchange="this.form.submit()">
                        <option value="descuento"   {{ ($filtros['orden'] ?? 'descuento') == 'descuento' ? 'selected' : '' }}>Mayor descuento</option>
                        <option value="novedad"     {{ ($filtros['orden'] ?? '') == 'novedad' ? 'selected' : '' }}>Novedades</option>
                        <option value="precio_asc"  {{ ($filtros['orden'] ?? '') == 'precio_asc' ? 'selected' : '' }}>Precio: menor a mayor</option>
                        <option value="precio_desc" {{ ($filtros['orden'] ?? '') == 'precio_desc' ? 'selected' : '' }}>Precio: mayor a menor</option>
                        <option value="nombre"      {{ ($filtros['orden'] ?? '') == 'nombre' ? 'selected' : '' }}>Nombre A-Z</option>
                    </select>
                </div>

                <button type="submit" class="btn-apply-filters">Aplicar filtros</button>
                <a href="{{ route('ofertas') }}" class="btn-clear-filters">Limpiar</a>
            </form>
        </aside>
        <div class="products-results">
            <div class="results-header">
                <span class="results-count">
                    {{ $productos->total() }} {{ $productos->total() === 1 ? 'oferta encontrada' : 'ofertas encontradas' }}
                </span>
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
                    <h3>No hay ofertas disponibles</h3>
                    <p>Prueba con otros filtros o vuelve más tarde.</p>
                    <a href="{{ route('ofertas') }}" class="btn-primary-custom">Ver todas las ofertas</a>
                </div>
            @endif
        </div>
    </div>
</div>