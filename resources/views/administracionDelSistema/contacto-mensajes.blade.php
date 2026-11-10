<div class="container-fluid py-4 contacto-mensajes-page">
<div class="cm-header mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="cm-header-icon">
                    <i class="bi bi-envelope-paper-fill"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold cm-title">Mensajes de Contacto</h3>
                    <small class="cm-subtitle">Bandeja de entrada del formulario de contacto</small>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <div class="cm-stat-pill cm-pill-nuevo">
                    <span class="cm-stat-value">{{ $stats['nuevos'] }}</span>
                    <span class="cm-stat-label">Nuevos</span>
                </div>
                <div class="cm-stat-pill cm-pill-pendiente">
                    <span class="cm-stat-value">{{ $stats['pendientes'] }}</span>
                    <span class="cm-stat-label">Pendientes</span>
                </div>
                <div class="cm-stat-pill cm-pill-respondido">
                    <span class="cm-stat-value">{{ $stats['respondidos'] }}</span>
                    <span class="cm-stat-label">Respondidos</span>
                </div>
                <div class="cm-stat-pill cm-pill-total">
                    <span class="cm-stat-value">{{ $stats['total'] }}</span>
                    <span class="cm-stat-label">Total</span>
                </div>
            </div>
        </div>
    </div>
    <div class="cm-filtros-card mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="cm-label">Estado</label>
                <select name="estado" class="form-select cm-input">
                    <option value="">Todos</option>
                    <option value="nuevo"      @selected(request('estado') === 'nuevo')>Nuevo</option>
                    <option value="leido"      @selected(request('estado') === 'leido')>Leído</option>
                    <option value="respondido" @selected(request('estado') === 'respondido')>Respondido</option>
                    <option value="archivado"  @selected(request('estado') === 'archivado')>Archivado</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-7">
                <label class="cm-label">Buscar</label>
                <div class="cm-search-wrapper">
                    <i class="bi bi-search cm-search-icon"></i>
                    <input type="text" name="q" class="form-control cm-input cm-search-input"
                           placeholder="Nombre, email, asunto o mensaje..."
                           value="{{ request('q') }}">
                </div>
            </div>
            <div class="col-md-3 col-lg-3 d-flex gap-2">
                <button class="btn cm-btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                @if(request('estado') || request('q'))
                    <a href="{{ url()->current() }}" class="btn cm-btn-ghost" title="Limpiar">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
    <div class="cm-tabla-card">
        <div class="table-responsive">
            <table class="w-100 table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 60px;">#</th>
                        <th style="width: 120px;">Fecha</th>
                        <th>Remitente</th>
                        <th>Asunto</th>
                        <th>Mensaje</th>
                        <th class="text-center" style="width: 120px;">Estado</th>
                        <th class="text-end" style="width: 160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($mensajes as $m)
                    <tr data-id="{{ $m->id }}" class="{{ $m->estado === 'nuevo' ? 'cm-row-nuevo' : '' }}">
                        <td class="text-center">
                            <span class="cm-id-badge">#{{ $m->id }}</span>
                        </td>

                        <td>
                            <div class="cm-fecha">
                                <i class="bi bi-calendar3"></i>
                                {{ $m->created_at->format('d/m/Y') }}
                            </div>
                            <small class="cm-fecha-hora">
                                <i class="bi bi-clock"></i>
                                {{ $m->created_at->format('H:i') }}
                            </small>
                        </td>

                        <td>
                            <div class="cm-remitente">
                                <div class="cm-avatar">
                                    {{ strtoupper(substr($m->nombre, 0, 1)) }}
                                </div>
                                <div class="cm-remitente-info">
                                    <div class="cm-remitente-nombre">{{ $m->nombre }}</div>
                                    <a href="mailto:{{ $m->email }}" class="cm-remitente-email">
                                        <i class="bi bi-envelope"></i> {{ $m->email }}
                                    </a>
                                    @if($m->telefono)
                                        <div class="cm-remitente-tel">
                                            <i class="bi bi-telephone"></i> {{ $m->telefono }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="cm-asunto">{{ $m->asunto }}</span>
                        </td>

                        <td>
                            <span class="cm-mensaje-resumen mb-0">{{ $m->resumen_mensaje }}</span>
                        </td>

                        <td class="text-center">
                            <span class="cm-badge-estado cm-badge-{{ $m->estado }}">
                                <i class="bi bi-circle-fill"></i>
                                {{ $m->estado_badge['label'] }}
                            </span>
                        </td>

                        <td class="text-end">
                            <div class="cm-acciones">
                                <button class="cm-btn-accion cm-btn-ver btn-ver-detalle"
                                        data-id="{{ $m->id }}" title="Ver detalle">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                                <a href="#"
                                data-id="{{ $m->id }}"
                                data-mailto="mailto:{{ $m->email }}?subject=Re: {{ urlencode($m->asunto) }}"
                                class="cm-btn-accion cm-btn-email btn-responder-email"
                                title="Responder por email">
                                    <i class="bi bi-reply-fill"></i>
                                </a>
                                <button class="cm-btn-accion cm-btn-ok btn-marcar-respondido"
                                        data-id="{{ $m->id }}" title="Marcar como respondido">
                                    <i class="bi bi-check2-circle"></i>
                                </button>
                                <button class="cm-btn-accion cm-btn-danger btn-eliminar"
                                        data-id="{{ $m->id }}" title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="cm-empty-state">
                                <div class="cm-empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <h5 class="cm-empty-title">No hay mensajes</h5>
                                <p class="cm-empty-desc mb-0">
                                    Cuando alguien envíe el formulario de contacto, aparecerá aquí.
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($mensajes->hasPages())
            <div class="cm-paginacion">
                {{ $mensajes->links() }}
            </div>
        @endif
    </div>
</div>
<div class="modal fade" id="modalDetalleMensaje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content cm-modal-detalle">
            <div class="modal-header cm-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-envelope-open-fill"></i>
                    <h5 class="modal-title mb-0">Detalle del mensaje</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Skeleton mientras carga --}}
                <div id="cmDetalleLoading" class="text-center py-4">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">Cargando detalle...</p>
                </div>

                {{-- Contenido real --}}
                <div id="cmDetalleContenido" class="d-none">
                    <div class="cm-detalle-grid mb-3">
                        <div class="cm-detalle-field">
                            <span class="cm-detalle-label">Remitente</span>
                            <strong id="cmDetalleNombre">—</strong>
                        </div>
                        <div class="cm-detalle-field">
                            <span class="cm-detalle-label">Email</span>
                            <a id="cmDetalleEmail" href="#" class="cm-detalle-link">—</a>
                        </div>
                        <div class="cm-detalle-field">
                            <span class="cm-detalle-label">Teléfono</span>
                            <strong id="cmDetalleTelefono">—</strong>
                        </div>
                        <div class="cm-detalle-field">
                            <span class="cm-detalle-label">Fecha de envío</span>
                            <strong id="cmDetalleFecha">—</strong>
                        </div>
                        <div class="cm-detalle-field">
                            <span class="cm-detalle-label">Estado</span>
                            <span id="cmDetalleEstado" class="cm-badge-estado">—</span>
                        </div>
                        <div class="cm-detalle-field">
                            <span class="cm-detalle-label">Respondido</span>
                            <strong id="cmDetalleRespondidoEn">—</strong>
                        </div>
                    </div>

                    <div class="cm-detalle-asunto mb-3">
                        <span class="cm-detalle-label">Asunto</span>
                        <h6 id="cmDetalleAsunto" class="mb-0">—</h6>
                    </div>

                    <div class="cm-detalle-mensaje">
                        <span class="cm-detalle-label">Mensaje</span>
                        <div id="cmDetalleMensaje" class="cm-mensaje-completo">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer cm-modal-footer">
                <button type="button" class="btn cm-btn-ghost" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Cerrar
                </button>
                <a id="cmDetalleBtnResponderEmail" href="#"
                data-id=""
                data-mailto=""
                class="btn cm-btn-primary btn-responder-email">
                    <i class="bi bi-reply-fill me-1"></i> Responder por email
                </a>
                <button id="cmDetalleBtnRespondido" type="button"
                        class="btn cm-btn-success" data-id="">
                    <i class="bi bi-check2-circle me-1"></i> Marcar respondido
                </button>
            </div>
        </div>
    </div>
</div>