<div class="inicio-full-container">
    <h1>Bienvenido a <span class="highlight">{{ $titulo_site ?: 'EMPRESA' }}</span></h1>

    @if(!empty($descripcion_corta))
        <div class="intro-descripcion">
            {!! $descripcion_corta !!}
        </div>
    @else
        <p>Tu destino preferido para la tecnología más avanzada e innovadora.</p>
        <p>Explora nuestro catálogo y descubre las últimas novedades.</p>
    @endif

    @forelse($secciones as $sec)

        @php
            $config = $sec['configuracion'] ?? [];
            $mostrarPrecio = $config['mostrar_precio'] ?? true;
            $mostrarTemporizador = !empty($config['mostrar_temporizador']);
            $esOferta = $sec['seccion'] === 'ofertas';
        @endphp

        <section class="home-seccion" data-seccion="{{ $sec['seccion'] }}">
            {{-- Header de la sección --}}
            <div class="home-seccion-header">
                <h2>
                    <i class="bi {{ $sec['icono'] }}"></i>
                    {{ $sec['nombre'] }}
                </h2>

                @if($esOferta)
                    <span class="home-seccion-badge">¡Descuentos activos!</span>
                @endif
            </div>

            <div class="product-carousel-wrapper">

                <button class="carousel-btn carousel-prev" type="button" aria-label="Anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>

                <div class="product-carousel"
                     data-mostrar-precio="{{ $mostrarPrecio ? '1' : '0' }}"
                     data-mostrar-temporizador="{{ $mostrarTemporizador ? '1' : '0' }}">

                    @foreach($sec['productos'] as $producto)
                        <div class="carousel-slide">
                            @include('tienda.partials.product-card-enhanced', ['producto' => $producto])

                            {{-- Temporizador de oferta --}}
                            @if($esOferta && $mostrarTemporizador && $producto->fecha_fin_rebaja)
                                <div class="oferta-timer"
                                     data-fin="{{ $producto->fecha_fin_rebaja->toIso8601String() }}">
                                    <i class="bi bi-clock"></i>
                                    <span class="timer-text">--:--:--</span>
                                </div>
                            @endif
                        </div>
                    @endforeach

                </div>

                <button class="carousel-btn carousel-next" type="button" aria-label="Siguiente">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </section>

    @empty
        <div class="empty-state mt-4">
            <i class="bi bi-inbox empty-icon"></i>
            <h3>Pronto tendremos productos para ti</h3>
            <p>Vuelve más tarde o explora las categorías disponibles.</p>
            <a href="{{ route('categorias.todas') }}" class="btn-primary-custom">
                Ver categorías <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    @endforelse

    @if(count($secciones))
        <div class="home-cta mt-5">
            <a href="{{ route('tienda.todos-productos') }}" class="btn-primary-custom">
                Ver todos los productos <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    @endif

</div>