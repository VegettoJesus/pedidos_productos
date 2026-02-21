<div class="container-fluid px-2 pb-4" style="display: inline-grid">
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Gestión de Ubicaciones</h5>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" id="ubicacionesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="departamentos-tab" data-bs-toggle="tab" data-bs-target="#departamentos" type="button">
                        <i class="bi bi-buildings me-1"></i> Departamentos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="provincias-tab" data-bs-toggle="tab" data-bs-target="#provincias" type="button">
                        <i class="bi bi-map me-1"></i> Provincias
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="distritos-tab" data-bs-toggle="tab" data-bs-target="#distritos" type="button">
                        <i class="bi bi-geo-alt me-1"></i> Distritos
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="ubicacionesTabContent">
                <div class="tab-pane fade show active" id="departamentos" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-lg-6 col-md-6 col-sm-12 d-flex align-items-end mt-2">
                            @permiso('Ubicaciones/sitio', 'crear')
                            <button id="btnNuevoDepartamento" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-plus-circle me-2"></i> Nuevo Departamento
                            </button>
                            @endpermiso
                        </div>
                    </div>
                    <div class="table-responsive mt-4">
                        <table id="tablaDepartamentos" class="table table-orange-personality" style="width: 100% !important;">
                            <thead>
                                <tr>
                                    <th class="text-center">Id</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Fecha Creación</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="provincias" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <label for="filtroDepartamentoProvincia" class="form-label">Filtrar por Departamento:</label>
                            <select id="filtroDepartamentoProvincia" class="form-select">
                                <option value="">Todos los Departamentos</option>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 d-flex align-items-end mt-2">
                            @permiso('Ubicaciones/sitio', 'crear')
                            <button id="btnNuevaProvincia" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-plus-circle me-2"></i> Nueva Provincia
                            </button>
                            @endpermiso
                        </div>
                    </div>
                    <div class="table-responsive mt-4">
                        <table id="tablaProvincias" class="table table-orange-personality" style="width: 100% !important;">
                            <thead>
                                <tr>
                                    <th class="text-center">Id</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">Departamento</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Fecha Creación</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="distritos" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="filtroDepartamentoDistrito" class="form-label">Departamento:</label>
                            <select id="filtroDepartamentoDistrito" class="form-select">
                                <option value="">Seleccionar Departamento</option>
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="filtroProvinciaDistrito" class="form-label">Provincia:</label>
                            <select id="filtroProvinciaDistrito" class="form-select" disabled>
                                <option value="">Seleccionar Provincia</option>
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12 d-flex align-items-end mt-2">
                            @permiso('Ubicaciones/sitio', 'crear')
                            <button id="btnNuevoDistrito" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-plus-circle me-2"></i> Nuevo Distrito
                            </button>
                            @endpermiso
                        </div>
                    </div>
                    <div class="table-responsive mt-4">
                        <table id="tablaDistritos" class="table table-orange-personality" style="width: 100% !important;">
                            <thead>
                                <tr>
                                    <th class="text-center">Id</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">Departamento</th>
                                    <th class="text-center">Provincia</th>
                                    <th class="text-center">Costo Envío (S/)</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUbicacion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="tituloModal">Nuevo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formUbicacion">
                    <input type="hidden" id="tipoEntidad" name="tipo_entidad">
                    <input type="hidden" id="idRegistro" name="id">
                    
                    <div id="camposDepartamento">
                        <div class="mb-3">
                            <label for="nombreDepartamento" class="form-label">Nombre del Departamento</label>
                            <input type="text" class="form-control" id="nombreDepartamento" name="nombre" required>
                        </div>
                    </div>
                    
                    <div id="camposProvincia" style="display: none;">
                        <div class="mb-3">
                            <label for="nombreProvincia" class="form-label">Nombre de la Provincia</label>
                            <input type="text" class="form-control" id="nombreProvincia" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="departamentoProvincia" class="form-label">Departamento</label>
                            <select class="form-select" id="departamentoProvincia" name="departamento_id" required>
                                <option value="">Seleccionar Departamento</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="camposDistrito" style="display: none;">
                        <div class="mb-3">
                            <label for="nombreDistrito" class="form-label">Nombre del Distrito</label>
                            <input type="text" class="form-control" id="nombreDistrito" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="costoEnvio" class="form-label">Costo de Envío (S/)</label>
                            <input type="number" class="form-control" id="costoEnvio" name="costo_envio" step="0.01" min="0" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="departamentoDistrito" class="form-label">Departamento</label>
                                <select class="form-select" id="departamentoDistrito" name="departamento_id" required>
                                    <option value="">Seleccionar Departamento</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="provinciaDistrito" class="form-label">Provincia</label>
                                <select class="form-select" id="provinciaDistrito" name="provincia_id" required disabled>
                                    <option value="">Seleccionar Provincia</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarUbicacion">Guardar</button>
            </div>
        </div>
    </div>
</div>