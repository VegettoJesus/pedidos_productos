<div class="container-fluid px-2" style="display: inline-grid">
    <div class="card text-center text-white bg-dark menu-management-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Gestión de Menús</h5>
            @permiso('AdministracionDelSistema/administrarMenu', 'crear')
                <button id="btnNuevo" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Nuevo Menú
                </button>
            @endpermiso
        </div>
        <div class="card-body">
            <div class="row mb-4 g-3">
                <div class="col-lg-8 col-md-6">
                    <div class="form-group">
                        <label for="filtro_permiso" class="form-label text-dark fw-bold">
                            <i class="bi bi-shield-lock me-1"></i>Filtrar por Rol:
                        </label>
                        <select class="form-select form-select-sm" id="filtro_permiso" name="filtro_permiso">
                            <option value="">TODOS LOS ROLES</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}">{{ strtoupper($rol->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 d-flex align-items-end">
                    <button id="btnAplicarFiltro" class="btn btn-success w-100">
                        <i class="bi bi-funnel me-1"></i>Aplicar
                    </button>
                </div>
                <div class="col-lg-2 col-md-3 d-flex align-items-end">
                    <button id="btnGuardarOrden" class="btn btn-warning w-100" style="display: none;">
                        <i class="bi bi-save me-1"></i>Guardar Orden
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <ul class="nav nav-tabs" id="menuTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="main-tab" data-bs-toggle="tab" 
                                data-bs-target="#main-tab-pane" type="button" role="tab">
                            <i class="bi bi-house-door me-1"></i>Menús Principales
                        </button>
                    </li>
                    @foreach($padres as $padre)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-{{ $padre->id }}" 
                                    data-bs-toggle="tab" data-bs-target="#pane-{{ $padre->id }}" 
                                    type="button" role="tab" data-menu-id="{{ $padre->id }}">
                                <i class="bi {{ $padre->icono }} me-1"></i>{{ $padre->nombre }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="tab-content" id="menuTabContent">
                <div class="tab-pane fade show active" id="main-tab-pane" role="tabpanel">
                    <div class="alert alert-info mb-3 d-flex align-items-center">
                        <i class="bi bi-info-circle me-2 fs-4"></i>
                        <div>
                            <strong>Menús Principales</strong> 
                            <span id="filtroInfoMain" class="ms-2"></span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tablaMenusPrincipales" class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="5%"><i class="bi bi-grip-vertical"></i></th>
                                    <th width="10%">ID</th>
                                    <th>Nombre</th>
                                    <th>Icono</th>
                                    <th>URL</th>
                                    <th width="8%" class="text-center">Orden</th>
                                    <th width="15%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="mainMenusBody" class="sortable-container">
                            </tbody>
                        </table>
                    </div>
                </div>

                @foreach($padres as $padre)
                    <div class="tab-pane fade" id="pane-{{ $padre->id }}" role="tabpanel">
                        <div class="alert alert-info mb-3 d-flex align-items-center">
                            <i class="bi bi-folder2 me-2 fs-4"></i>
                            <div>
                                <strong>Submenús de {{ $padre->nombre }}</strong>
                                <span id="filtroInfo{{ $padre->id }}" class="ms-2"></span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="5%"><i class="bi bi-grip-vertical"></i></th>
                                        <th width="10%">ID</th>
                                        <th>Nombre</th>
                                        <th>URL</th>
                                        <th width="8%" class="text-center">Orden</th>
                                        <th width="15%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="submenus-{{ $padre->id }}" 
                                       class="sortable-container" 
                                       data-padre-id="{{ $padre->id }}">
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer">
            <small><i class="bi bi-info-circle me-1"></i>Arrastra y suelta para reordenar los menús</small>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPermisos" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="height: auto;min-width: min-content;">
    <div class="modal-content border-0 shadow-lg  bg-dark text-white">
      <div class="modal-header bg-gradient-primary text-white border-0 rounded-top-3 py-3 px-3 px-md-4">
        <div class="d-flex align-items-center w-100">
          <div class="icon-container p-2">
            <i class="bi bi-shield-lock fs-4"></i>
          </div>
          <div class="flex-grow-1 text-truncate">
            <h5 class="modal-title mb-0 fw-bold text-truncate">Gestión de Permisos</h5>
            <small class="opacity-75 text-truncate d-block" id="menuNombrePermisos"></small>
          </div>
          <button type="button" class="btn-close btn-close-white shadow-none flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      <!-- Body moderno -->
      <div class="modal-body p-0 bg-white">
        <div class="bg-light p-3 border-bottom">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3">
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 w-100">
              <span class="badge bg-info fs-6 d-none d-md-inline-flex align-items-center">
                <i class="bi bi-menu-button-wide me-1"></i>
                Permisos
              </span>
              <span class="badge bg-info fs-6 d-inline-flex d-md-none align-items-center">
                <i class="bi bi-menu-button-wide me-1"></i> Permisos
              </span>
              <div class="input-group input-group-sm flex-grow-1" style="min-width: 150px;">
                <span class="input-group-text bg-white">
                  <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control" id="buscarRol" placeholder="Buscar rol...">
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
              <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1 flex-md-grow-0" id="btnSeleccionarTodos">
                <i class="bi bi-check-square me-1 d-none d-sm-inline"></i>
                <span class="d-inline d-sm-none">✓ Todos</span>
                <span class="d-none d-sm-inline">Seleccionar todos</span>
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 flex-md-grow-0" id="btnDeseleccionarTodos">
                <i class="bi bi-square me-1 d-none d-sm-inline"></i>
                <span class="d-inline d-sm-none">✗ Ninguno</span>
                <span class="d-none d-sm-inline">Limpiar todos</span>
              </button>
            </div>
          </div>
        </div>

        <div class="table-responsive-vertical" id="permisosTableContainer">
          <div class="table-wrapper">
            <div class="d-md-none bg-white" id="mobilePermisosView">
            </div>
            
            <div class="d-none d-md-block bg-white">
              <div class="table-container p-3">
                <table class="table table-hover align-middle mb-0" id="tablaPermisos">
                  <thead class="sticky-top bg-white shadow-sm">
                    <tr>
                      <th class="bg-white" style="min-width: 200px;">
                        <div class="d-flex align-items-center">
                          <i class="bi bi-person-badge me-2 text-primary"></i>
                          <span class="fw-semibold">Rol</span>
                        </div>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-light p-3 border-top">
          <div class="row g-2 g-md-3">
            <div class="col-4 col-md-4">
              <div class="d-flex flex-column align-items-center text-center">
                <div class="icon-stat bg-success bg-opacity-10 p-2 rounded mb-1">
                  <i class="bi bi-check-circle text-success fs-4"></i>
                </div>
                <small class="text-muted d-none d-md-block">Permisos activos</small>
                <small class="text-muted d-block d-md-none">Activos</small>
                <h6 class="mb-0 fw-bold" id="contadorActivos">0</h6>
              </div>
            </div>
            <div class="col-4 col-md-4">
              <div class="d-flex flex-column align-items-center text-center">
                <div class="icon-stat bg-warning bg-opacity-10 p-2 rounded mb-1">
                  <i class="bi bi-people text-warning fs-4"></i>
                </div>
                <small class="text-muted d-none d-md-block">Roles con acceso</small>
                <small class="text-muted d-block d-md-none">Roles</small>
                <h6 class="mb-0 fw-bold" id="contadorRoles">0</h6>
              </div>
            </div>
            <div class="col-4 col-md-4">
              <div class="d-flex flex-column align-items-center text-center">
                <div class="icon-stat bg-info bg-opacity-10 p-2 rounded mb-1">
                  <i class="bi bi-shield-check text-info fs-4"></i>
                </div>
                <small class="text-muted d-none d-md-block">Total permisos</small>
                <small class="text-muted d-block d-md-none">Total</small>
                <h6 class="mb-0 fw-bold" id="contadorTotal">0</h6>
              </div>
            </div>
          </div>
          <div class="text-center mt-2">
            <small class="text-muted" id="infoPermisos"></small>
          </div>
        </div>
      </div>

      <!-- Footer responsive - MODIFICADO -->
      <div class="modal-footer border-0 bg-light rounded-bottom-3 py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between w-100 align-items-center gap-3">
          <div class="text-muted small text-center text-md-start">
            <i class="bi bi-info-circle me-1"></i>
            <span id="infoPermisosFooter">Los cambios se guardan automáticamente al hacer clic</span>
          </div>
          <div class="d-flex flex-wrap gap-2 justify-content-center w-100 w-md-auto">
            <button type="button" class="btn btn-outline-secondary flex-grow-1 flex-md-grow-0" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-1"></i>Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}

.table-container::-webkit-scrollbar {
    width: 6px !important;
    height: 6px !important;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1 !important;
    border-radius: 3px !important;
}

.table-container::-webkit-scrollbar-thumb {
    background: #c1c1c1 !important;
    border-radius: 3px !important;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8 !important;
}

.rol-card,
.rol-row,
.btn,
.form-check-input {
    transition: all 0.2s ease-in-out !important;
}

.alert .badge {
    font-size: 0.85em;
    padding: 0.35em 0.65em;
    margin: 0 2px;
}

@media (max-width: 768px) {
    .table-responsive {
        margin: 0 -10px !important;
        padding: 0 10px !important;
    }
    .form-switch .form-check-input {
        width: 2.2em !important;
        height: 1em !important;
    }
    
    .icon-stat i {
        font-size: 1.5rem !important;
    }
    
    .form-check-input:checked {
        background-position: right center !important;
    }
}

@media (max-width: 576px) {
    .btn {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
    }
    
    .table-container {
        max-height: 250px !important;
    }
}

@media (min-width: 769px) {
    .icon-stat:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    }
}

@media (max-width: 767.98px) {
    .form-switch .form-check-input {
        width: 2.5em !important;
        height: 1.2em !important;
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    .form-switch .form-check-input {
        width: 2.8em !important;
        height: 1.4em !important;
    }
}

@media (max-width: 576px) {
    .btn {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.8rem !important;
    }
}

@media (min-width: 768px) {
    .icon-stat:hover {
        transform: translateY(-2px) !important;
        transition: transform 0.3s !important;
    }
    
    .avatar-rol:hover {
        transform: scale(1.05) !important;
        transition: transform 0.3s !important;
    }
    
    .rol-row:hover td:not(.sticky-left) {
        background-color: rgba(0,0,0,0.02) !important;
    }
    
    .sticky-left:hover {
        background-color: #f8f9fa !important;
    }
}

@media (max-width: 768px) {
    .row.g-3 {
        margin-bottom: 1rem !important;
    }
    .form-label {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    .alert {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
    
    .alert .badge {
        font-size: 0.8em;
        padding: 0.25em 0.5em;
    }
}
</style>

<script>
    window.iconosDisponibles = @json($iconos);
    window.padres = @json($padres);
</script>