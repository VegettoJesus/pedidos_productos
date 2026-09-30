<div class="container-fluid px-2 pb-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-bell-fill me-2"></i>
                Centro de Notificaciones
            </h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefrescarNotificaciones">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refrescar
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="btnMarcarTodasLeidas">
                    <i class="bi bi-check-all me-1"></i> Marcar todas como leídas
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted d-block">No leídas</small>
                                    <h3 class="mb-0 fw-bold text-danger" id="statNoLeidas">
                                        {{ $noLeidas }}
                                    </h3>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                    <i class="bi bi-envelope-fill fs-3 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted d-block">Total</small>
                                    <h3 class="mb-0 fw-bold text-primary" id="statTotal">
                                        {{ $notificaciones->total() }} 
                                    </h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                    <i class="bi bi-bell-fill fs-3 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-light border-0 mb-3">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Buscar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="filtroBusqueda" placeholder="Buscar título o mensaje...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Tipo</label>
                            <select class="form-select form-select-sm" id="filtroTipo">
                                <option value="">Todos los tipos</option>
                                @foreach($tiposNotificacion as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Estado</label>
                            <select class="form-select form-select-sm" id="filtroEstado">
                                <option value="">Todas</option>
                                <option value="no_leidas">Solo no leídas</option>
                                <option value="leidas">Solo leídas</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Prioridad</label>
                            <select class="form-select form-select-sm" id="filtroPrioridad">
                                <option value="">Todas</option>
                                <option value="critica">Crítica</option>
                                <option value="alta">Alta</option>
                                <option value="media">Media</option>
                                <option value="baja">Baja</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="contenedorNotificaciones">
                @if($notificaciones->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No tienes notificaciones</h5>
                        <p class="text-muted small">Cuando recibas notificaciones aparecerán aquí.</p>
                    </div>
                @else
                    @foreach($notificaciones as $notif)
                        <div class="notificacion-item {{ $notif->leida ? 'leida' : 'no-leida' }}"
                             data-id="{{ $notif->id }}"
                             data-tipo="{{ $notif->tipo_notificacion_id }}"
                             data-prioridad="{{ $notif->prioridad }}"
                             data-leida="{{ $notif->leida ? '1' : '0' }}">
                            
                            {{-- Indicador de color lateral según prioridad --}}
                            <div class="notificacion-prioridad prioridad-{{ $notif->prioridad }}"></div>
                            
                            {{-- Ícono del tipo --}}
                            <div class="notificacion-icono">
                                <span class="badge-tipo"
                                      style="background: {{ $notif->tipo->color ?? '#6c757d' }}20; color: {{ $notif->tipo->color ?? '#6c757d' }};">
                                    <i class="{{ $notif->tipo->icono ?? 'bi bi-bell' }}"></i>
                                </span>
                            </div>
                            
                            {{-- Contenido --}}
                            <div class="notificacion-contenido">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="mb-0 fw-bold {{ !$notif->leida ? 'text-dark' : 'text-muted' }}">
                                        {{ $notif->titulo }}
                                        @if(!$notif->leida)
                                            <span class="badge bg-danger ms-2" style="font-size: 0.6rem;">NUEVA</span>
                                        @endif
                                    </h6>
                                    <small class="text-muted flex-shrink-0 ms-2">
                                        <i class="bi bi-clock"></i> {{ $notif->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                
                                <p class="mb-2 text-muted small">{{ $notif->mensaje }}</p>
                                
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex gap-2 flex-wrap">
                                        {{-- Tipo --}}
                                        <span class="badge" style="background: {{ $notif->tipo->color ?? '#6c757d' }}; font-size: 0.7rem;">
                                            {{ $notif->tipo->nombre ?? 'Sin tipo' }}
                                        </span>
                                        
                                        {{-- Prioridad --}}
                                        @php
                                            $prioridadColors = [
                                                'critica' => 'danger',
                                                'alta' => 'warning',
                                                'media' => 'info',
                                                'baja' => 'secondary'
                                            ];
                                            $prioridadLabels = [
                                                'critica' => 'Crítica',
                                                'alta' => 'Alta',
                                                'media' => 'Media',
                                                'baja' => 'Baja'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $prioridadColors[$notif->prioridad] ?? 'secondary' }}" style="font-size: 0.7rem;">
                                            {{ $prioridadLabels[$notif->prioridad] ?? $notif->prioridad }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex gap-1">
                                        @if($notif->url)
                                            <a href="{{ $notif->url }}" 
                                               class="btn btn-sm btn-primary"
                                               data-marcar-leida="{{ $notif->id }}">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                                {{ $notif->boton_texto ?? 'Ver más' }}
                                            </a>
                                        @endif
                                        
                                        @if(!$notif->leida)
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-success btn-marcar-leida"
                                                    data-id="{{ $notif->id }}"
                                                    title="Marcar como leída">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        @else
                                            <span class="badge bg-light text-success align-self-center">
                                                <i class="bi bi-check2-all"></i> Leída
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <button type="button" 
                    class="btn btn-outline-primary" 
                    id="btnCargarMas"
                    data-has-more="{{ $notificaciones->hasMorePages() ? '1' : '0' }}"
                    data-current-page="{{ $notificaciones->currentPage() }}"
                    data-last-page="{{ $notificaciones->lastPage() }}"
                    data-per-page="{{ $notificaciones->perPage() }}"
                    style="{{ $notificaciones->hasMorePages() ? '' : 'display:none;' }}">
                <i class="bi bi-arrow-down-circle me-1"></i> Cargar más
            </button>

        </div>
    </div>
</div>