<div class="container-fluid pb-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h3 class="mb-0">
                        <i class="bi bi-pc-display me-2"></i>Configuración del Sitio
                    </h3>
                    @permiso('Empresa/configuracionSitio', 'crear')
                        <button type="button" class="btn btn-success" id="btnGuardar">
                            <i class="bi bi-save me-1"></i>Guardar Cambios
                        </button>
                    @endpermiso
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario Principal -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form id="formConfiguracionSitio" novalidate>
                        @csrf
                        
                        <!-- Sección 1: Información Básica -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-info-circle me-2"></i>Información Básica
                                </h4>
                            </div>
                            
                            <!-- Título del Sitio -->
                            <div class="col-md-6 mb-3">
                                <label for="titulo_site" class="form-label fw-bold">
                                    <i class="bi bi-type me-1"></i>Título del Sitio
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-globe"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="titulo_site" 
                                           name="titulo_site"
                                           maxlength="255"
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese el título del sitio.
                                    </div>
                                </div>
                                <small class="text-muted">Aparece en la pestaña del navegador y como nombre principal.</small>
                            </div>

                            <!-- Abreviatura -->
                            <div class="col-md-6 mb-3">
                                <label for="abreviatura_titulo" class="form-label fw-bold">
                                    <i class="bi bi-type-h1 me-1"></i>Abreviatura
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-textarea-t"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="abreviatura_titulo" 
                                           name="abreviatura_titulo"
                                           maxlength="50"
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese una abreviatura.
                                    </div>
                                </div>
                                <small class="text-muted">Versión corta para sidebar y espacios reducidos.</small>
                            </div>

                            <!-- Descripción Corta -->
                            <div class="col-12 mb-3">
                                <label for="descripcion_corta" class="form-label fw-bold">
                                    <i class="bi bi-chat-left-text me-1"></i>Descripción Corta
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-card-text"></i>
                                    </span>
                                    <textarea class="form-control" 
                                              id="descripcion_corta" 
                                              name="descripcion_corta" 
                                              rows="3"
                                              maxlength="500"
                                              required></textarea>
                                    <div class="invalid-feedback">
                                        Por favor ingrese una descripción del sitio.
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Descripción que aparece en buscadores y redes sociales.</small>
                                    <small id="contadorDescripcion" class="text-muted">0/500</small>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Identidad Visual -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-palette me-2"></i>Identidad Visual
                                </h4>
                            </div>

                            <!-- Icono del Sitio -->
                            <div class="col-md-6 mb-3">
                                <label for="icono_site" class="form-label fw-bold">
                                    <i class="bi bi-image me-1"></i>Icono/Favicon
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-file-earmark-image"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="icono_site" 
                                           name="icono_site"
                                           placeholder="Ej: /assets/img/favicon.ico"
                                           maxlength="255">
                                    <button class="btn btn-outline-secondary" type="button" id="btnExaminarIcono">
                                        <i class="bi bi-folder2-open"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Ruta del archivo de icono (favicon.ico, logo.png, etc.)</small>
                                
                                <!-- Vista previa del icono -->
                                <div class="mt-3" id="previewIconoContainer" style="display: none;">
                                    <label class="form-label fw-bold">Vista Previa:</label>
                                    <div class="d-flex align-items-center">
                                        <img id="previewIcono" src="" alt="Vista previa" 
                                             class="img-thumbnail me-3" style="width: 64px; height: 64px;">
                                        <div>
                                            <p class="mb-1" id="iconoInfo"></p>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoverIcono">
                                                <i class="bi bi-trash"></i> Remover
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Email de Administración -->
                            <div class="col-md-6 mb-3">
                                <label for="email_admin" class="form-label fw-bold">
                                    <i class="bi bi-envelope me-1"></i>Email de Administración
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-at"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email_admin" 
                                           name="email_admin"
                                           maxlength="255"
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese un email válido.
                                    </div>
                                </div>
                                <small class="text-muted">Email principal para notificaciones del sistema.</small>
                            </div>
                        </div>

                        <!-- Sección 3: Contenido y Layout -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-layout-text-sidebar-reverse me-2"></i>Contenido y Layout
                                </h4>
                            </div>

                            <!-- Máximo de Entradas en Home -->
                            <div class="col-md-6 mb-3">
                                <label for="max_entradas_home" class="form-label fw-bold">
                                    <i class="bi bi-list-columns me-1"></i>Máximo de Productos en Inicio
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-123"></i>
                                    </span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="max_entradas_home" 
                                           name="max_entradas_home"
                                           min="1"
                                           max="50"
                                           required>
                                    <span class="input-group-text">productos</span>
                                </div>
                                <small class="text-muted">Número máximo de productos a mostrar en la página principal.</small>
                            </div>

                            <div class="col-12 mb-4">
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Organiza las secciones que aparecen en la página principal. Arrastra para cambiar el orden.
                                    </div>

                                    <!-- Lista de secciones (se llena dinámicamente) -->
                                    <div class="row">
                                        <div class="col-12">
                                            <div id="listaSeccionesHome" class="sortable-container">
                                                <!-- Las secciones se cargarán aquí dinámicamente -->
                                                <div class="text-center py-4">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Cargando...</span>
                                                    </div>
                                                    <p class="mt-2">Cargando secciones...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Text -->
                            <div class="col-12 mb-3">
                                <label for="footer_text" class="form-label fw-bold">
                                    <i class="bi bi-text-paragraph me-1"></i>Texto del Pie de Página
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-justify-left"></i>
                                    </span>
                                    <textarea class="form-control" 
                                              id="footer_text" 
                                              name="footer_text" 
                                              rows="3"
                                              maxlength="1000"></textarea>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Texto que aparece en el footer (HTML permitido).</small>
                                    <small id="contadorFooter" class="text-muted">0/1000</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="btnRestablecer">
                        <i class="bi bi-arrow-clockwise me-1"></i>Restablecer
                    </button>
                    @permiso('Empresa/configuracionSitio', 'crear')
                    <button type="button" class="btn btn-success" id="btnGuardarFooter">
                        <i class="bi bi-save me-1"></i>Guardar Cambios
                    </button>
                    @endpermiso
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cargar icono -->
<div class="modal fade" id="modalIcono" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title">
                    <i class="bi bi-folder2-open me-2"></i>Seleccionar Icono
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Upload de archivo -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="bi bi-upload me-1"></i>Subir Nuevo Icono
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Seleccionar archivo:</label>
                                    <input type="file" class="form-control" id="fileIcono" accept=".ico,.png,.jpg,.jpeg,.svg,.gif,.webp">
                                </div>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Formatos recomendados: .ico (16x16, 32x32), .png (transparente)
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Iconos existentes -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="bi bi-folder me-1"></i>Iconos Existentes
                            </div>
                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                <div class="row g-2" id="listaIconos">
                                    <!-- Iconos se cargarán dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnSeleccionarIcono">
                    <i class="bi bi-check-circle me-1"></i>Seleccionar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal para configurar sección -->
<div class="modal fade" id="modalConfigSeccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title" id="modalConfigSeccionTitle">
                    <i class="bi bi-gear me-2"></i>Configurar Sección
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formConfigSeccion">
                    @csrf
                    <input type="hidden" id="seccion_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-list-ul me-1"></i>Número de Elementos
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="seccion_numero_elementos" 
                                   name="numero_elementos"
                                   min="1" 
                                   max="50"
                                   required>
                            <small class="text-muted">Recomendado: múltiplos de 2, 3 o 4 para diseño responsivo</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-sort-numeric-down me-1"></i>Orden
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="seccion_orden" 
                                   name="orden"
                                   min="0"
                                   required>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       role="switch" 
                                       id="seccion_mostrar" 
                                       name="mostrar" 
                                       value="1" 
                                       checked>
                                <label class="form-check-label fw-bold" for="seccion_mostrar">
                                    <i class="bi bi-eye me-1"></i>Mostrar esta sección
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración específica por tipo de sección -->
                    <div class="config-especifica" id="configEspecifica">
                        <!-- Se carga dinámicamente según el tipo de sección -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btnGuardarConfigSeccion">
                    <i class="bi bi-check-circle me-1"></i>Guardar Configuración
                </button>
            </div>
        </div>
    </div>
</div>