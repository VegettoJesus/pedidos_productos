<div class="container-fluid pb-4" style="display: inline-grid">
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
                                    <i class="bi bi-chat-left-text me-1"></i>Introducción de la Página de Inicio
                                </label>
                                <div class="">
                                    <textarea id="descripcion_corta" name="descripcion_corta" class="form-control" rows="10" maxlength="500" placeholder="Escribir aqui.." required></textarea>
                                    <div class="invalid-feedback">
                                        Por favor ingrese una descripción del sitio.
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Este texto se mostrará como introducción principal en la portada de tu sitio. También se usa para SEO y redes sociales.</small>
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
                    </form>
                    <div class="col-12 mt-2">
                        <div class="">
                            <div class="m-4">
                                <ul class="nav nav-tabs card-header-tabs" id="footerTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="columns-tab" data-bs-toggle="tab" data-bs-target="#columns" type="button" role="tab">
                                            <i class="bi bi-grid-3x3-gap-fill me-1"></i> Columnas
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="links-tab" data-bs-toggle="tab" data-bs-target="#links" type="button" role="tab">
                                            <i class="bi bi-link-45deg me-1"></i> Enlaces
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">
                                            <i class="bi bi-envelope-paper me-1"></i> Contacto
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                                            <i class="bi bi-share me-1"></i> Redes Sociales
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body p-0 p-sm-3">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="columns" role="tabpanel">
                                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                                            <h5 class="mb-0">Columnas del Footer</h5>
                                            <button type="button" class="btn btn-primary w-sm-auto" id="btnNuevaColumna">
                                                <i class="bi bi-plus-circle me-1"></i> Nueva Columna
                                            </button>
                                        </div>
                                        <div id="listaColumnasFooter" class="sortable-container">
                                            <div class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                                <p class="mt-2">Cargando columnas...</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="links" role="tabpanel">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Selecciona una columna de tipo <strong>"links"</strong> para gestionar sus enlaces.
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12 col-md-4">
                                                <label class="form-label fw-bold">Columna</label>
                                                <select id="selectColumnaLinks" class="form-select">
                                                    <option value="">-- Seleccionar columna --</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                                                    <h6 class="mb-0">Enlaces de la columna</h6>
                                                    <button type="button" class="btn btn-success w-sm-auto" id="btnNuevoEnlace" disabled>
                                                        <i class="bi bi-plus-circle me-1"></i> Agregar Enlace
                                                    </button>
                                                </div>
                                                <div id="listaEnlacesFooter" class="list-group">
                                                    <div class="text-center py-3 text-muted">
                                                        Seleccione una columna para ver sus enlaces.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="contact" role="tabpanel">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Selecciona una columna de tipo <strong>"mixed"</strong> para editar su información de contacto.
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12 col-md-4">
                                                <label class="form-label fw-bold">Columna</label>
                                                <select id="selectColumnaContact" class="form-select">
                                                    <option value="">-- Seleccionar columna --</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <form id="formContactoFooter">
                                                    <!-- TELÉFONO -->
                                                    <div class="card mb-3 border">
                                                        <div class="card-header bg-light">
                                                            <i class="bi bi-telephone me-2"></i> Teléfono
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-2">
                                                                <label class="form-label">Número</label>
                                                                <input type="text" class="form-control" id="contact_phone" placeholder="+34 123 456 789">
                                                            </div>
                                                            <div class="mb-0">
                                                                <label class="form-label">Ícono (opcional)</label>
                                                                <div class="d-flex gap-3 mb-2">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="tipoIconoPhone" id="tipoPhoneLibreria" value="libreria" checked>
                                                                        <label class="form-check-label">Ícono de librería</label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="tipoIconoPhone" id="tipoPhoneImagen" value="imagen">
                                                                        <label class="form-check-label">Imagen .ico</label>
                                                                    </div>
                                                                </div>
                                                                <div id="panelPhoneLibreria">
                                                                    <div style="margin-bottom:10px;">
                                                                        <input id="buscadorPhone" class="form-control form-control-sm" placeholder="Buscar icono...">
                                                                    </div>
                                                                    <div id="iconPickerPhone" class="icon-grid-modern" style="max-height:180px;"></div>
                                                                    <input type="hidden" id="iconoPhoneHidden" value="">
                                                                </div>
                                                                <div id="panelPhoneImagen" style="display: none;">
                                                                    <div class="upload-area" id="uploadAreaPhone" style="border:1px dashed #ccc; padding:10px; text-align:center; cursor:pointer;">
                                                                        <i class="bi bi-cloud-upload"></i> Haz clic o arrastra un archivo .ico
                                                                        <input type="file" id="uploadIconoPhone" accept=".ico" style="display: none;">
                                                                    </div>
                                                                    <div id="previewPhoneImagen" style="display: none; margin-top:10px;">
                                                                        <img id="imgPreviewPhone" style="width:32px; height:32px;">
                                                                        <span id="nombreArchivoPhone"></span>
                                                                        <button type="button" id="btnQuitarPhone" class="btn btn-sm btn-outline-danger ms-2">Quitar</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- CORREO -->
                                                    <div class="card mb-3 border">
                                                        <div class="card-header bg-light">
                                                            <i class="bi bi-envelope me-2"></i> Correo electrónico
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-2">
                                                                <label class="form-label">Correo</label>
                                                                <input type="email" class="form-control" id="contact_email" placeholder="info@ejemplo.com">
                                                            </div>
                                                            <div class="mb-0">
                                                                <label class="form-label">Ícono (opcional)</label>
                                                                <div class="d-flex gap-3 mb-2">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="tipoIconoEmail" id="tipoEmailLibreria" value="libreria" checked>
                                                                        <label class="form-check-label">Ícono de librería</label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="tipoIconoEmail" id="tipoEmailImagen" value="imagen">
                                                                        <label class="form-check-label">Imagen .ico</label>
                                                                    </div>
                                                                </div>
                                                                <div id="panelEmailLibreria">
                                                                    <div style="margin-bottom:10px;">
                                                                        <input id="buscadorEmail" class="form-control form-control-sm" placeholder="Buscar icono...">
                                                                    </div>
                                                                    <div id="iconPickerEmail" class="icon-grid-modern" style="max-height:180px;"></div>
                                                                    <input type="hidden" id="iconoEmailHidden" value="">
                                                                </div>
                                                                <div id="panelEmailImagen" style="display: none;">
                                                                    <div class="upload-area" id="uploadAreaEmail" style="border:1px dashed #ccc; padding:10px; text-align:center; cursor:pointer;">
                                                                        <i class="bi bi-cloud-upload"></i> Haz clic o arrastra un archivo .ico
                                                                        <input type="file" id="uploadIconoEmail" accept=".ico" style="display: none;">
                                                                    </div>
                                                                    <div id="previewEmailImagen" style="display: none; margin-top:10px;">
                                                                        <img id="imgPreviewEmail" style="width:32px; height:32px;">
                                                                        <span id="nombreArchivoEmail"></span>
                                                                        <button type="button" id="btnQuitarEmail" class="btn btn-sm btn-outline-danger ms-2">Quitar</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- DIRECCIÓN -->
                                                    <div class="card mb-3 border">
                                                        <div class="card-header bg-light">
                                                            <i class="bi bi-geo-alt me-2"></i> Dirección
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-2">
                                                                <label class="form-label">Dirección</label>
                                                                <textarea class="form-control" id="contact_address" rows="2" placeholder="Calle Falsa 123, Ciudad"></textarea>
                                                            </div>
                                                            <div class="mb-0">
                                                                <label class="form-label">Ícono (opcional)</label>
                                                                <div class="d-flex gap-3 mb-2">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="tipoIconoAddress" id="tipoAddressLibreria" value="libreria" checked>
                                                                        <label class="form-check-label">Ícono de librería</label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="tipoIconoAddress" id="tipoAddressImagen" value="imagen">
                                                                        <label class="form-check-label">Imagen .ico</label>
                                                                    </div>
                                                                </div>
                                                                <div id="panelAddressLibreria">
                                                                    <div style="margin-bottom:10px;">
                                                                        <input id="buscadorAddress" class="form-control form-control-sm" placeholder="Buscar icono...">
                                                                    </div>
                                                                    <div id="iconPickerAddress" class="icon-grid-modern" style="max-height:180px;"></div>
                                                                    <input type="hidden" id="iconoAddressHidden" value="">
                                                                </div>
                                                                <div id="panelAddressImagen" style="display: none;">
                                                                    <div class="upload-area" id="uploadAreaAddress" style="border:1px dashed #ccc; padding:10px; text-align:center; cursor:pointer;">
                                                                        <i class="bi bi-cloud-upload"></i> Haz clic o arrastra un archivo .ico
                                                                        <input type="file" id="uploadIconoAddress" accept=".ico" style="display: none;">
                                                                    </div>
                                                                    <div id="previewAddressImagen" style="display: none; margin-top:10px;">
                                                                        <img id="imgPreviewAddress" style="width:32px; height:32px;">
                                                                        <span id="nombreArchivoAddress"></span>
                                                                        <button type="button" id="btnQuitarAddress" class="btn btn-sm btn-outline-danger ms-2">Quitar</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <button type="button" class="btn btn-primary w-sm-auto mt-2" id="btnGuardarContacto">
                                                        <i class="bi bi-save me-1"></i> Guardar contacto
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="social" role="tabpanel">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Selecciona una columna de tipo <strong>"mixed"</strong> para gestionar sus redes sociales.
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12 col-md-4">
                                                <label class="form-label fw-bold">Columna</label>
                                                <select id="selectColumnaSocial" class="form-select">
                                                    <option value="">-- Seleccionar columna --</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                                                    <h6 class="mb-0">Redes Sociales</h6>
                                                    <button type="button" class="btn btn-success w-sm-auto" id="btnNuevaRedSocial" disabled>
                                                        <i class="bi bi-plus-circle me-1"></i> Agregar Red Social
                                                    </button>
                                                </div>
                                                <div id="listaRedesSociales" class="list-group">
                                                    <div class="text-center py-3 text-muted">
                                                        Seleccione una columna para ver sus redes.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
<script>
    window.iconosDisponibles = @json($iconos);
</script>