<div class="container-fluid px-2 pb-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-bell-fill me-2"></i>
                Permisos de Notificaciones
            </h5>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" id="permisosTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-por-rol" data-bs-toggle="tab" data-bs-target="#panel-por-rol" type="button">
                        <i class="bi bi-people-fill me-1"></i> Por Rol
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-por-usuario" data-bs-toggle="tab" data-bs-target="#panel-por-usuario" type="button">
                        <i class="bi bi-person-badge me-1"></i> Excepciones por Usuario
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                {{-- ============================================
                     TAB 1: MATRIZ POR ROL
                ============================================ --}}
                <div class="tab-pane fade show active" id="panel-por-rol" role="tabpanel">
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>
                            <strong>Permisos por rol:</strong> Define qué tipos de notificación puede ver cada rol por defecto.
                            Los <strong>admins siempre ven todo</strong>.
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="tablaMatrizPermisos">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th class="sticky-left bg-dark text-white align-content-center" style="min-width: 150px;">Rol</th>
                                    @foreach($tiposNotificacion as $tipo)
                                        <th class="text-center" style="min-width: 100px;">
                                            <i class="{{ $tipo->icono }}" style="color: {{ $tipo->color }}; font-size: 1.3rem;"></i>
                                            <br>
                                            <small class="fw-semibold">{{ $tipo->nombre }}</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $rol)
                                    <tr>
                                        <td class="sticky-left fw-bold">
                                            {{ $rol->name }}
                                            @if($rol->name === 'admin')
                                                <span class="badge bg-danger ms-1" title="Acceso total">Total</span>
                                            @endif
                                        </td>
                                        @foreach($tiposNotificacion as $tipo)
                                            @php
                                                $permiso = $permisosRoles[$rol->id][$tipo->id] ?? false;
                                                $esAdmin = $rol->name === 'admin';
                                            @endphp
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input 
                                                        type="checkbox" 
                                                        class="form-check-input chk-permiso-rol"
                                                        data-rol-id="{{ $rol->id }}"
                                                        data-tipo-id="{{ $tipo->id }}"
                                                        data-rol-nombre="{{ $rol->name }}"
                                                        data-tipo-nombre="{{ $tipo->nombre }}"
                                                        {{ $permiso ? 'checked' : '' }}
                                                        {{ $esAdmin ? 'disabled' : '' }}
                                                    >
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============================================
                     TAB 2: EXCEPCIONES POR USUARIO
                ============================================ --}}
                <div class="tab-pane fade" id="panel-por-usuario" role="tabpanel">
                    <div class="alert alert-warning d-flex align-items-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>
                            <strong>Excepciones individuales:</strong> 
                            Sobrescriben el permiso del rol solo para ese usuario específico.
                            <br>
                            <small>
                                <strong class="text-success">Permitir</strong> = el usuario ve el tipo aunque su rol no lo tenga.
                                <strong class="text-danger">Denegar</strong> = el usuario NO ve el tipo aunque su rol sí lo tenga.
                            </small>
                        </div>
                    </div>

                    <!-- Selector de usuario -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-person-search me-1"></i> Buscar Usuario
                            </label>
                            <div class="position-relative">
                                <input 
                                    type="text" 
                                    id="buscarUsuarioInput" 
                                    class="form-control"
                                    placeholder="Escribe nombre, apellido o email..."
                                    autocomplete="off"
                                >
                                <div id="resultadosBusqueda" class="list-group position-absolute w-100" style="z-index: 100; display: none; max-height: 300px; overflow-y: auto;"></div>
                            </div>
                            <small class="text-muted">Mínimo 2 caracteres. Los admins no aparecen.</small>
                        </div>
                    </div>

                    <!-- Panel de excepciones del usuario seleccionado -->
                    <div id="panelExcepcionesUsuario" style="display: none;">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-person-circle me-2"></i>
                                    <strong id="usuarioSeleccionadoNombre"></strong>
                                    <span class="badge bg-light text-dark ms-2" id="usuarioSeleccionadoRol"></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-light" id="btnCerrarPanelUsuario">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">
                                    <i class="bi bi-list-check me-1"></i>
                                    Permisos Efectivos
                                </h6>
                                
                                <div id="matrizExcepcionesUsuario" class="table-responsive">
                                    <!-- Se llena vía JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de todas las excepciones existentes -->
                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-list-ul me-1"></i>
                            Excepciones Registradas
                            <span class="badge bg-secondary ms-2" id="totalExcepciones">{{ $excepciones->count() }}</span>
                        </h6>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="tablaExcepciones">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Tipo de Notificación</th>
                                        <th class="text-center">Excepción</th>
                                        <th>Motivo</th>
                                        <th class="text-center">Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyExcepciones">
                                    @forelse($excepciones as $excepcion)
                                        <tr data-excepcion-id="{{ $excepcion->id }}">
                                            <td>
                                                <i class="bi bi-person-fill me-1"></i>
                                                <strong>{{ $excepcion->usuario->nombres }} {{ $excepcion->usuario->apellidos }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $excepcion->usuario->email }}</small>
                                            </td>
                                            <td>
                                                <i class="{{ $excepcion->tipoNotificacion->icono }}" 
                                                   style="color: {{ $excepcion->tipoNotificacion->color }};"></i>
                                                {{ $excepcion->tipoNotificacion->nombre }}
                                            </td>
                                            <td class="text-center">
                                                @if($excepcion->tipo_excepcion === 'permitir')
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i> Permitir
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle me-1"></i> Denegar
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $excepcion->motivo ?? '—' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <small>{{ $excepcion->created_at->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger btn-eliminar-excepcion"
                                                        data-excepcion-id="{{ $excepcion->id }}"
                                                        title="Eliminar excepción">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                                No hay excepciones registradas
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>