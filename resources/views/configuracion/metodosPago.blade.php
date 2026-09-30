<div class="container-fluid px-2 pb-4 metodos-pago-page">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card metodos-sidebar-card">
                <div class="card-header metodos-sidebar-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-list-check"></i>
                        <span class="fw-semibold">Métodos disponibles</span>
                    </div>
                    <span class="badge-count">{{ $metodos->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="metodos-list" id="listaMetodos">
                        @forelse($metodos as $metodo)
                            <div class="metodo-item-modern"
                                 data-id="{{ $metodo->id }}"
                                 data-slug="{{ $metodo->slug }}">

                                <div class="metodo-icono-modern"
                                     style="background: {{ $metodo->activo ? 'linear-gradient(135deg, #F4AB28 0%, #f35b08 100%)' : 'linear-gradient(135deg, #707275 0%, #abacae 100%)' }};">
                                    <i class="{{ $metodo->icono ?? 'bi bi-credit-card' }}"></i>
                                </div>

                                <div class="metodo-info-modern">
                                    <div class="metodo-nombre-modern">
                                        {{ $metodo->nombre }}
                                        @if($metodo->activo)
                                            <span class="dot-active" title="Activo"></span>
                                        @endif
                                    </div>
                                    <div class="metodo-desc-modern">
                                        {{ $metodo->descripcion }}
                                    </div>
                                </div>

                                <div class="metodo-toggle-modern" onclick="event.stopPropagation();">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input toggle-activo"
                                               type="checkbox"
                                               role="switch"
                                               data-id="{{ $metodo->id }}"
                                               {{ $metodo->activo ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-metodos">
                                <i class="bi bi-inbox"></i>
                                <p>No hay métodos de pago registrados</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card panel-config-card">
                <div class="card-header panel-config-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-sliders2"></i>
                        <span class="fw-semibold">Configuración</span>
                    </div>
                </div>
                <div class="card-body panel-config-body">
                    <div id="panelConfiguracion">
                        <div class="empty-state-config">
                            <div class="empty-state-icon">
                                <i class="bi bi-mouse2"></i>
                            </div>
                            <h5 class="empty-state-title">Selecciona un método de pago</h5>
                            <p class="empty-state-desc">
                                Haz clic en cualquiera de los métodos de la izquierda para configurar sus opciones,
                                activarlo o desactivarlo según tus necesidades.
                            </p>
                            <div class="empty-state-hints">
                                <div class="hint-item">
                                    <i class="bi bi-1-circle-fill"></i>
                                    <span>Elige un método</span>
                                </div>
                                <div class="hint-item">
                                    <i class="bi bi-2-circle-fill"></i>
                                    <span>Configura sus opciones</span>
                                </div>
                                <div class="hint-item">
                                    <i class="bi bi-3-circle-fill"></i>
                                    <span>Actívalo y guarda</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('configuracion.partials.pago-contra-entrega')
@include('configuracion.partials.pago-transferencia')
@include('configuracion.partials.pago-qr')