<!-- resources/views/tienda/mis-valoraciones.blade.php -->
<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="bi bi-star-fill text-warning me-2"></i>
                Mis Valoraciones
            </h1>
            <p class="text-muted mb-4">
                Aquí puedes ver todos los productos que has calificado.
            </p>
        </div>
    </div>

    @if(isset($show_auth_modal) && $show_auth_modal)
        <!-- Mostrar mensaje con botón para abrir modal -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="text-center py-5">
                    <i class="bi bi-star display-1 text-muted"></i>
                    <h3 class="mt-3">{{ $auth_modal_message ?? 'Debes iniciar sesión para ver tus valoraciones.' }}</h3>
                    <button class="btn btn-primary mt-3" id="openAuthFromValoraciones">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar sesión / Registrarse
                    </button>
                </div>
            </div>
        </div>
    @else
        <!-- Mostrar valoraciones normalmente -->
        <div class="row" id="valoracionesContainer">
            @forelse($valoraciones as $valoracion)
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 shadow-sm hover-shadow transition-all">
                        <a href="{{ route('producto.detalle', $valoracion->producto_id) }}" class="text-decoration-none">
                            <div class="card-img-top" style="height: 200px; overflow: hidden; background: #f8f9fa;">
                                <img src="{{ $valoracion->producto_imagen }}" 
                                     alt="{{ $valoracion->producto->nombre ?? 'Producto' }}" 
                                     class="w-100 h-100 object-fit-cover"
                                     style="object-fit: contain;"
                                     loading="lazy">
                            </div>
                        </a>
                        <div class="card-body">
                            <a href="{{ route('producto.detalle', $valoracion->producto_id) }}" class="text-decoration-none text-dark">
                                <h5 class="card-title text-truncate" title="{{ $valoracion->producto->nombre ?? 'Producto eliminado' }}">
                                    {{ $valoracion->producto->nombre ?? 'Producto eliminado' }}
                                </h5>
                            </a>
                            
                            <!-- Estrellas -->
                            <div class="my-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star{{ $i <= $valoracion->puntuacion ? '-fill' : '' }} text-warning" 
                                       style="font-size: 1.1rem;"></i>
                                @endfor
                                <span class="ms-2 text-muted small">{{ number_format($valoracion->puntuacion, 1) }}</span>
                            </div>
                            
                            <!-- Precio -->
                            <div class="mt-2">
                                <span class="fw-bold text-primary">{{ $valoracion->producto_precio ?? 'Precio no disponible' }}</span>
                            </div>
                            
                            <!-- Comentario -->
                            @if($valoracion->comentario)
                                <p class="card-text small text-muted mt-2 text-truncate" style="max-width: 100%;">
                                    <i class="bi bi-chat-quote me-1"></i>
                                    {{ Str::limit($valoracion->comentario, 60) }}
                                </p>
                            @endif
                            
                            <!-- Fecha -->
                            <div class="mt-2">
                                <small class="text-muted">
                                    @if($valoracion->created_at == $valoracion->updated_at)
                                        <i class="bi bi-calendar3 me-1"></i>
                                        Calificado {{ $valoracion->created_at->diffForHumans() }}
                                    @else
                                        <i class="bi bi-pencil-square me-1"></i>
                                        Editado {{ $valoracion->updated_at->diffForHumans() }}
                                    @endif
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0">
                            <a href="{{ route('producto.detalle', $valoracion->producto_id) }}" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-eye me-1"></i> Ver producto
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-star display-1 text-muted"></i>
                        <h3 class="mt-3">No has calificado ningún producto</h3>
                        <p class="text-muted">Explora nuestros productos y califica los que más te gusten.</p>
                        <a href="{{ route('tienda.todos-productos') }}" class="btn btn-primary mt-3">
                            <i class="bi bi-shop me-2"></i> Ver productos
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($valoraciones->hasPages())
            <div class="row mt-4">
                <div class="col-12">
                    {{ $valoraciones->links() }}
                </div>
            </div>
        @endif
    @endif
</div>

<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
    }
    .object-fit-cover {
        object-fit: cover;
    }
    .text-truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>