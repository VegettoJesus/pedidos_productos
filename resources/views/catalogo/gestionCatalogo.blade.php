<style>
.nav-tabs .nav-link {
    color: #495057;
    font-weight: 500;
    border: 1px solid transparent;
    border-top-left-radius: 0.25rem;
    border-top-right-radius: 0.25rem;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
    transform: translateY(-2px);
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
    font-weight: 600;
}

.nav-tabs .nav-link i {
    margin-right: 0.5rem;
}

.icon-picker-container {
    background: #f8f9fa;
    border-radius: 5px;
    padding: 10px;
    max-height: 200px;
    overflow-y: auto;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    border: 1px solid #ddd;
}

.icon-picker-container::-webkit-scrollbar {
    width: 8px;
}

.icon-picker-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.icon-picker-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.icon-picker-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.input-group-text {
    background-color: #f8f9fa;
}

.input-group-text i {
    font-size: 1.2rem;
}

.badge-subcategoria {
    background-color: #17a2b8;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}
</style>
<div class="container-fluid px-2 pb-4" style="display: inline-grid">
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Gestión de Categorías y Subcategorías</h5>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" id="categoriasTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="categorias-tab" data-bs-toggle="tab" data-bs-target="#categorias" type="button">
                        <i class="bi bi-tags me-1"></i> Categorías
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="subcategorias-tab" data-bs-toggle="tab" data-bs-target="#subcategorias" type="button">
                        <i class="bi bi-diagram-2 me-1"></i> Subcategorías
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="categoriasTabContent">
                <!-- TAB DE CATEGORÍAS -->
                <div class="tab-pane fade show active" id="categorias" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-lg-3 col-md-6 col-sm-12 d-flex align-items-end mt-2">
                            @permiso('Catalogo/gestionCatalogo', 'crear')
                            <button id="btnNuevaCategoria" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-plus-circle me-2"></i> Nueva Categoría
                            </button>
                            @endpermiso
                        </div>
                    </div>
                    <div class="table-responsive mt-4">
                        <table id="tablaCategorias" class="table table-orange-personality" style="width: 100% !important;">
                            <thead>
                                <tr>
                                    <th class="text-center">Id</th>
                                    <th class="text-center">Icono</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">Subcategorías</th>
                                    <th class="text-center">Fecha Creación</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB DE SUBCATEGORÍAS -->
                <div class="tab-pane fade" id="subcategorias" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="filtroCategoria" class="form-label">Filtrar por Categoría:</label>
                            <select id="filtroCategoria" class="form-select">
                                <option value="">Todas las Categorías</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 d-flex align-items-end mt-2">
                            @permiso('Catalogo/gestionCatalogo', 'subcategoria')
                            <button id="btnNuevaSubcategoria" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-plus-circle me-2"></i> Nueva Subcategoría
                            </button>
                            @endpermiso
                        </div>
                    </div>
                    <div class="table-responsive mt-4">
                        <table id="tablaSubcategorias" class="table table-orange-personality" style="width: 100% !important;">
                            <thead>
                                <tr>
                                    <th class="text-center">Id</th>
                                    <th class="text-center">Icono</th>
                                    <th class="text-center">Nombre</th>
                                    <th class="text-center">Categoría</th>
                                    <th class="text-center">Fecha Creación</th>
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

<!-- Modal para Categorías y Subcategorías -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="tituloModal"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCategoria">
                    <input type="hidden" id="tipoEntidad" name="tipo_entidad">
                    <input type="hidden" id="idRegistro" name="id">
                    
                    <!-- Campos para Categoría -->
                    <div id="camposCategoria">
                        <div class="mb-3">
                            <label for="nombreCategoria" class="form-label">Nombre de la Categoría</label>
                            <input type="text" class="form-control" id="nombreCategoria" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Icono</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text" id="iconoPreviewCategoria">
                                    <i class="bi bi-question-circle"></i>
                                </span>
                                <input type="text" class="form-control" id="iconoBuscarCategoria" placeholder="Buscar icono...">
                                <button class="btn btn-outline-secondary" type="button" id="btnLimpiarIconoCategoria">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <div id="iconPickerCategoria" class="icon-picker-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px; display: flex; flex-wrap: wrap; gap: 5px;">
                                <!-- Los iconos se cargarán aquí dinámicamente -->
                            </div>
                            <input type="hidden" id="iconoCategoria" name="icono" value="">
                        </div>
                    </div>
                    
                    <!-- Campos para Subcategoría -->
                    <div id="camposSubcategoria" style="display: none;">
                        <div class="mb-3">
                            <label for="nombreSubcategoria" class="form-label">Nombre de la Subcategoría</label>
                            <input type="text" class="form-control" id="nombreSubcategoria" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="categoriaSubcategoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoriaSubcategoria" name="id_categoria" required>
                                <option value="">Seleccionar Categoría</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Icono</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text" id="iconoPreviewSubcategoria">
                                    <i class="bi bi-question-circle"></i>
                                </span>
                                <input type="text" class="form-control" id="iconoBuscarSubcategoria" placeholder="Buscar icono...">
                                <button class="btn btn-outline-secondary" type="button" id="btnLimpiarIconoSubcategoria">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <div id="iconPickerSubcategoria" class="icon-picker-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px; display: flex; flex-wrap: wrap; gap: 5px;">
                                <!-- Los iconos se cargarán aquí dinámicamente -->
                            </div>
                            <input type="hidden" id="iconoSubcategoria" name="icono" value="">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardar">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.iconosDisponibles = @json($iconos);
</script>