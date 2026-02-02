
    <style>
        /* Variables CSS */
        :root {
            --header-height: 60px;
            --transition-speed: 0.3s;
        }
        
        /* Contenedor principal */
        .theme-editor-container {
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }
        
        /* Header */
        .theme-header {
            background: white;
            height: var(--header-height);
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            padding: 0 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
        }
        
        .theme-header h1 {
            font-size: 1.25rem;
            margin: 0;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Contenido principal */
        .theme-main-content {
            padding: 1rem;
            margin-left: revert;
            width: auto;
        }
        
        /* Grid responsivo */
        .theme-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            width: 100%;
        }
        
        @media (min-width: 992px) {
            .theme-grid {
                grid-template-columns: 300px 1fr;
            }
        }
        
        /* Panel lateral */
        .theme-sidebar-panel {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            height: fit-content;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        /* Panel principal */
        .theme-main-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        /* Previsualización */
        .preview-wrapper {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
        }
        
        .preview-header-bar {
            background: var(--color-header, #2271b1);
            color: var(--color-header-text, white);
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .preview-container {
            padding: 1rem;
            background: var(--color-bg, #f0f0f1);
            min-height: 300px;
            overflow: auto;
        }
        
        /* Controles de previsualización */
        .preview-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .view-toggle {
            display: flex;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            padding: 0.25rem;
        }
        
        .view-btn {
            background: transparent;
            border: none;
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .view-btn.active {
            background: rgba(255,255,255,0.2);
        }
        
        /* Panel de controles */
        .controls-panel {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
        }
        
        .controls-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .controls-body {
            padding: 1rem;
            max-height: 500px;
            overflow-y: auto;
        }
        
        /* Lista de temas */
        .tema-item {
            padding: 0.75rem 1rem;
            border-left: 4px solid transparent;
            cursor: pointer;
            transition: all var(--transition-speed);
            border-bottom: 1px solid #f0f0f1;
        }
        
        .tema-item:hover {
            background: #f8f9fa;
        }
        
        .tema-item.active {
            background: #e7f1ff;
            border-left-color: #2271b1;
        }
        
        .tema-item.predeterminado {
            background: #f0f9f0;
            border-left-color: #28a745;
        }
        
        /* Controles de color */
        .color-controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.75rem;
        }
        
        @media (max-width: 768px) {
            .color-controls-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .color-control {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            transition: all var(--transition-speed);
        }
        
        .color-control:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .color-info {
            flex: 1;
            min-width: 0;
        }
        
        .color-name {
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.125rem;
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .color-desc {
            font-size: 0.75rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .color-value {
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 0.75rem;
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            min-width: 80px;
            text-align: center;
        }
        
        /* Grupos de colores */
        .color-group {
            margin-bottom: 1.5rem;
        }
        
        .color-group-title {
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
            font-size: 1rem;
        }
        
        /* Botones */
        .btn-responsive {
            width: 100%;
        }
        
        @media (min-width: 576px) {
            .btn-responsive {
                width: auto;
            }
        }
        
        /* Previsualización de elementos */
        .preview-elements {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .preview-card {
            border: 1px solid var(--color-border-card, #dee2e6);
            border-radius: 4px;
            overflow: hidden;
            background: var(--color-bg-card, white);
        }
        
        .preview-card-header {
            background: var(--color-header, #2271b1);
            color: var(--color-header-text, white);
            padding: 0.75rem 1rem;
            font-weight: 600;
        }
        
        .preview-card-body {
            padding: 1rem;
        }
        
        .preview-btn {
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            border: none;
            margin: 0.25rem;
            font-size: 0.875rem;
        }
        
        .btn-primary-preview {
            background: var(--color-primary, #2271b1) !important;
            color: var(--color-text-primary, white) !important;
        }
        
        .btn-success-preview {
            background: var(--color-success, #28a745) !important;
            color: var(--color-text-success, white) !important;
        }
        
        .btn-danger-preview {
            background: var(--color-danger, #dc3545) !important;
            color: var(--color-text-danger, white) !important;
        }
        
        .preview-input {
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--color-border-card, #dee2e6);
            border-radius: 4px;
            background: var(--color-datatable-search-card, white);
            color: var(--color-datatable-search-card-text, #212529);
            width: 100%;
            margin: 0.25rem 0;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .preview-table th,
        .preview-table td {
            padding: 0.5rem;
            border: 1px solid var(--color-border-card, #dee2e6);
            text-align: left;
        }
        
        .preview-table thead {
            background: var(--color-table-thead, #2271b1);
            color: var(--color-table-text-thead, white);
        }
        
        /* Alertas */
        .alert-theme {
            border-left: 4px solid;
            background: white;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .alert-info {
            border-left-color: #2271b1;
            background: #e7f1ff;
        }
        
        .alert-warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        /* Modal responsivo */
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        /* Responsive adicional */
        @media (max-width: 576px) {
            .theme-header {
                padding: 0 0.75rem;
            }
            
            .theme-header h1 {
                font-size: 1rem;
            }
            
            .theme-main-content {
                padding: 0.75rem;
            }
            
            .preview-container {
                padding: 0.75rem;
            }
            
            .controls-header,
            .controls-body {
                padding: 0.75rem;
            }
            
            .color-control {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .color-preview {
                width: 100%;
                height: 40px;
            }
            
            .color-value {
                width: 100%;
            }
        }
        
        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Utilidades */
        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        
        .scrollable {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Eliminar scroll horizontal */
        body, .theme-editor-container, .preview-container, .controls-body {
            overflow-x: hidden;
        }
        
        /* Mejorar scrollbars */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
    <div class="theme-editor-container">
        <!-- Header -->
        <header class="theme-header">
            <h1 id="tituloTemaSeleccionado">
                <i class="bi bi-palette me-2"></i>Editor de Temas
            </h1>
            
            <div class="ms-auto d-flex align-items-center gap-2">
                <!-- Botón para guardar cambios -->
                <button class="btn btn-primary btn-sm btn-responsive" id="btnGuardarCambios" style="display: none;" onclick="guardarCambios()">
                    <i class="bi bi-save me-1"></i>Guardar
                </button>
                
                <!-- Botón nuevo tema -->
                <button class="btn btn-success btn-sm btn-responsive" data-bs-toggle="modal" data-bs-target="#modalNuevoTema">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo
                </button>
            </div>
        </header>
        
        <!-- Contenido principal -->
        <main class="theme-main-content">
            <div class="theme-grid">
                <!-- Panel lateral - Lista de temas -->
                <div class="theme-sidebar-panel">
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">
                                <i class="bi bi-list-ul me-2"></i>Temas
                            </h2>
                            <span class="badge bg-secondary">{{ count($temas) }}</span>
                        </div>
                        
                        <div class="scrollable" style="max-height: 300px;">
                            <div id="listaTemas">
                                @foreach($temas as $tema)
                                <div class="tema-item {{ $tema->activo ? 'active' : '' }} {{ $tema->es_predeterminado ? 'predeterminado' : '' }}"
                                     data-id="{{ $tema->id }}"
                                     onclick="cargarTema({{ $tema->id }}, this)">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1 me-2">
                                            <h6 class="mb-1">{{ $tema->nombre_tema }}</h6>
                                            <small class="text-muted d-block text-truncate">{{ $tema->descripcion ?: 'Sin descripción' }}</small>
                                        </div>
                                        <div class="d-flex gap-1">
                                            @if($tema->activo)
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i>
                                            </span>
                                            @endif
                                            @if($tema->es_predeterminado)
                                            <span class="badge bg-primary">
                                                <i class="bi bi-star"></i>
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acciones del tema -->
                    <div class="p-3" id="accionesTema" style="display: none;">
                        <h6 class="mb-3">Acciones</h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-sm btn-responsive" onclick="activarTema()">
                                <i class="bi bi-power me-1"></i>Activar
                            </button>
                            <button class="btn btn-warning btn-sm btn-responsive" onclick="mostrarPreconfiguraciones()">
                                <i class="bi bi-magic me-1"></i>Preconfigurar
                            </button>
                            <button class="btn btn-outline-danger btn-sm btn-responsive" onclick="eliminarTema()">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Panel principal -->
                <div class="theme-main-panel">
                    <!-- Previsualización -->
                    <div class="preview-wrapper">
                        <div class="preview-header-bar">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="preview-logo">
                                    <i class="bi bi-wordpress me-2"></i>
                                    <span>Mi Sitio</span>
                                </div>
                                <div class="preview-controls ms-auto">
                                    <div class="view-toggle">
                                        <button class="view-btn active" onclick="cambiarVista('desktop')" title="Desktop">
                                            <i class="bi bi-laptop"></i>
                                        </button>
                                        <button class="view-btn" onclick="cambiarVista('tablet')" title="Tablet">
                                            <i class="bi bi-tablet"></i>
                                        </button>
                                        <button class="view-btn" onclick="cambiarVista('mobile')" title="Móvil">
                                            <i class="bi bi-phone"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="preview-container" id="previewContent">
                            <!-- Mensaje inicial -->
                            <div id="previewPlaceholder" class="h-100 d-flex flex-column justify-content-center align-items-center text-muted">
                                <i class="bi bi-palette fs-1 mb-3"></i>
                                <h4>Seleccione un tema</h4>
                                <p class="text-center">Elija un tema de la lista para ver la previsualización</p>
                            </div>
                            
                            <!-- Previsualización dinámica -->
                            <div id="previewDynamic" style="display: none;">
                                <div class="preview-elements">
                                    <!-- Tarjeta de ejemplo -->
                                    <div class="preview-card">
                                        <div class="preview-card-header">
                                            <i class="bi bi-card-heading me-2"></i>
                                            Tarjeta de ejemplo
                                        </div>
                                        <div class="preview-card-body">
                                            <p>Está editando: <strong id="previewTemaNombre">Tema</strong></p>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Campo de texto:</label>
                                                <input type="text" class="preview-input" value="Texto de ejemplo" readonly>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Selector:</label>
                                                <select class="preview-input">
                                                    <option>Opción 1</option>
                                                    <option>Opción 2</option>
                                                    <option>Opción 3</option>
                                                </select>
                                            </div>
                                            
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="preview-btn btn-primary-preview">
                                                    <i class="bi bi-check me-1"></i>Primary
                                                </button>
                                                <button class="preview-btn btn-success-preview">
                                                    <i class="bi bi-check-circle me-1"></i>Success
                                                </button>
                                                <button class="preview-btn btn-danger-preview">
                                                    <i class="bi bi-x-circle me-1"></i>Danger
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Tabla de ejemplo -->
                                    <div class="preview-card">
                                        <div class="preview-card-header">
                                            <i class="bi bi-table me-2"></i>
                                            Tabla de ejemplo
                                        </div>
                                        <div class="preview-card-body">
                                            <table class="preview-table">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Nombre</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>1</td>
                                                        <td>Elemento 1</td>
                                                        <td><span class="badge bg-success">Activo</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>2</td>
                                                        <td>Elemento 2</td>
                                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>3</td>
                                                        <td>Elemento 3</td>
                                                        <td><span class="badge bg-danger">Inactivo</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel de controles -->
                    <div class="controls-panel">
                        <div class="controls-header">
                            <h3 class="h5 mb-0">
                                <i class="bi bi-sliders me-2"></i>Configuraciones de Color
                            </h3>
                            <p class="text-muted mb-0 small">Los cambios se aplican en tiempo real</p>
                        </div>
                        
                        <div class="controls-body">
                            <!-- Mensaje inicial -->
                            <div id="configPlaceholder" class="alert-theme alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Seleccione un tema de la lista para ver y editar sus configuraciones.
                            </div>
                            
                            <!-- Controles dinámicos -->
                            <div id="configDynamic" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal Nuevo Tema -->
    <div class="modal fade" id="modalNuevoTema" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Nuevo Tema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre del tema *</label>
                                <input type="text" class="form-control" id="nombre_tema" placeholder="Ej: Mi Tema Personalizado" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" rows="3" placeholder="Describe tu tema..."></textarea>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="es_predeterminado">
                                <label class="form-check-label" for="es_predeterminado">
                                    Marcar como tema predeterminado
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label mb-2">Plantillas predefinidas</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="card h-100 text-center border theme-template" onclick="seleccionarPlantilla('claro')">
                                        <div class="card-body">
                                            <i class="bi bi-sun-fill text-warning fs-2 mb-2"></i>
                                            <h6 class="card-title">Tema Claro</h6>
                                            <small class="text-muted">Colores claros</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card h-100 text-center border theme-template" onclick="seleccionarPlantilla('oscuro')">
                                        <div class="card-body">
                                            <i class="bi bi-moon-fill text-secondary fs-2 mb-2"></i>
                                            <h6 class="card-title">Tema Oscuro</h6>
                                            <small class="text-muted">Modo nocturno</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <label class="form-label">Duplicar de tema existente</label>
                                <select class="form-select" id="duplicar_tema_id">
                                    <option value="">-- Crear desde cero --</option>
                                    @foreach($temas as $tema)
                                    <option value="{{ $tema->id }}">{{ $tema->nombre_tema }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarNuevoTema()">
                        <i class="bi bi-plus-circle me-1"></i>Crear Tema
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Variables globales
        let temaActualId = null;
        let temaActualNombre = null;
        let cambiosPendientes = false;
        let configuracionesTemporales = {};
        let configuracionesOriginales = {};
        
        // CSRF Token para AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Cargar tema seleccionado
        function cargarTema(temaId, elemento) {
            temaActualId = temaId;
            temaActualNombre = elemento.querySelector('h6').textContent;
            
            // Actualizar UI
            document.querySelectorAll('.tema-item').forEach(item => {
                item.classList.remove('active');
            });
            elemento.classList.add('active');
            
            // Mostrar acciones
            document.getElementById('accionesTema').style.display = 'block';
            
            // Actualizar título
            document.getElementById('tituloTemaSeleccionado').innerHTML = 
                `<i class="bi bi-palette me-2"></i>Editando: ${temaActualNombre}`;
            
            // Mostrar botón de guardar
            document.getElementById('btnGuardarCambios').style.display = 'flex';
            
            // Mostrar previsualización
            document.getElementById('previewPlaceholder').style.display = 'none';
            document.getElementById('previewDynamic').style.display = 'block';
            document.getElementById('previewTemaNombre').textContent = temaActualNombre;
            
            // Mostrar controles
            document.getElementById('configPlaceholder').style.display = 'none';
            document.getElementById('configDynamic').style.display = 'block';
            
            // Cargar configuraciones
            cargarConfiguraciones(temaId);
        }
        
        // Cargar configuraciones del tema
        function cargarConfiguraciones(temaId) {
            fetch('/temas-colores', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    opcion: 'ObtenerConfiguraciones',
                    tema_id: temaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarControlesDeColor(data.data);
                    configuracionesOriginales = data.data;
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar configuraciones', 'error');
            });
        }
        
        // Mostrar controles de color
        function mostrarControlesDeColor(grupos) {
            const container = document.getElementById('configDynamic');
            let html = '';
            
            if (Object.keys(grupos).length === 0) {
                html = `
                    <div class="alert-theme alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Este tema no tiene configuraciones. 
                        <a href="javascript:void(0)" onclick="mostrarPreconfiguraciones()" class="alert-link">
                            Aplica una preconfiguración
                        </a> o agrega variables manualmente.
                    </div>
                `;
            } else {
                for (const [grupo, configs] of Object.entries(grupos)) {
                    html += `
                        <div class="color-group fade-in">
                            <h4 class="color-group-title">
                                <i class="bi bi-palette me-2"></i>
                                ${grupo.charAt(0).toUpperCase() + grupo.slice(1)}
                            </h4>
                            <div class="color-controls-grid">
                    `;
                    
                    configs.forEach(config => {
                        html += `
                            <div class="color-control">
                                <div class="color-preview" 
                                     style="background-color: ${config.variable_valor}"
                                     onclick="mostrarSelectorColor(this, '${config.variable_nombre}', '${config.id}')"
                                     data-value="${config.variable_valor}"
                                     id="preview_${config.id}"
                                     title="Haz clic para seleccionar color">
                                </div>
                                <div class="color-info">
                                    <div class="color-name">${config.variable_nombre}</div>
                                    <div class="color-desc">${config.descripcion || 'Sin descripción'}</div>
                                </div>
                                <input type="text" 
                                       class="color-value form-control form-control-sm" 
                                       value="${config.variable_valor}"
                                       onchange="actualizarColor('${config.variable_nombre}', this.value, ${config.id})"
                                       data-variable="${config.variable_nombre}"
                                       id="input_${config.id}"
                                       title="Valor hexadecimal">
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                        </div>
                    `;
                }
            }
            
            container.innerHTML = html;
            
            // Inicializar cambios pendientes como false
            cambiosPendientes = false;
            configuracionesTemporales = {};
            
            // Actualizar botón de guardar
            document.getElementById('btnGuardarCambios').innerHTML = 
                `<i class="bi bi-save me-1"></i>Guardar`;
            
            // Aplicar estilos iniciales
            aplicarEstilosTemporales();
        }
        
        // Actualizar color en tiempo real
        function actualizarColor(variable, valor, configId) {
            // Validar color
            if (!valor || !valor.trim()) {
                Swal.fire('Error', 'El color no puede estar vacío', 'error');
                return;
            }
            
            // Actualizar preview
            const preview = document.getElementById(`preview_${configId}`);
            if (preview) {
                preview.style.backgroundColor = valor;
                preview.dataset.value = valor;
            }
            
            // Guardar cambio temporal
            configuracionesTemporales[variable] = valor;
            cambiosPendientes = true;
            
            // Aplicar estilos en tiempo real
            aplicarEstilosTemporales();
            
            // Mostrar indicador de cambios
            const cambiosCount = Object.keys(configuracionesTemporales).length;
            document.getElementById('btnGuardarCambios').innerHTML = 
                `<i class="bi bi-save me-1"></i>Guardar <span class="badge bg-warning ms-1">${cambiosCount}</span>`;
        }
        
        // Aplicar estilos temporales a la previsualización
        function aplicarEstilosTemporales() {
            const styleId = 'tema-estilos-temporales';
            let styleElement = document.getElementById(styleId);
            
            if (!styleElement) {
                styleElement = document.createElement('style');
                styleElement.id = styleId;
                document.head.appendChild(styleElement);
            }
            
            // Crear CSS con las variables temporales
            let css = ':root {';
            for (const [variable, valor] of Object.entries(configuracionesTemporales)) {
                css += `${variable}: ${valor}; `;
            }
            css += '}';
            
            styleElement.textContent = css;
        }
        
        // Mostrar selector de color
        function mostrarSelectorColor(elemento, variable, configId) {
            // Crear input de color oculto
            const colorPicker = document.createElement('input');
            colorPicker.type = 'color';
            colorPicker.value = elemento.dataset.value || '#000000';
            colorPicker.style.position = 'fixed';
            colorPicker.style.opacity = '0';
            colorPicker.style.pointerEvents = 'none';
            colorPicker.style.top = '0';
            colorPicker.style.left = '0';
            
            // Agregar al DOM
            document.body.appendChild(colorPicker);
            
            // Mostrar picker nativo
            colorPicker.click();
            
            // Escuchar cambios
            colorPicker.addEventListener('change', function() {
                const nuevoColor = this.value.toUpperCase();
                
                // Actualizar input
                const input = document.getElementById(`input_${configId}`);
                if (input) {
                    input.value = nuevoColor;
                }
                
                // Llamar a actualizarColor
                actualizarColor(variable, nuevoColor, configId);
                
                // Eliminar el input temporal
                if (document.body.contains(colorPicker)) {
                    document.body.removeChild(colorPicker);
                }
            });
            
            // Eliminar si se hace clic fuera
            colorPicker.addEventListener('blur', () => {
                setTimeout(() => {
                    if (document.body.contains(colorPicker)) {
                        document.body.removeChild(colorPicker);
                    }
                }, 100);
            });
        }
        
        // Guardar cambios
        function guardarCambios() {
            if (!cambiosPendientes || !temaActualId) {
                Swal.fire('Info', 'No hay cambios pendientes para guardar', 'info');
                return;
            }
            
            const cambiosCount = Object.keys(configuracionesTemporales).length;
            
            Swal.fire({
                title: '¿Guardar cambios?',
                html: `Se guardarán <b>${cambiosCount}</b> cambios en el tema <b>${temaActualNombre}</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    // Guardar cada cambio
                    const promises = [];
                    
                    for (const [variable, valor] of Object.entries(configuracionesTemporales)) {
                        // Buscar el configId en las configuraciones originales
                        let configId = null;
                        for (const grupo in configuracionesOriginales) {
                            const config = configuracionesOriginales[grupo].find(c => c.variable_nombre === variable);
                            if (config) {
                                configId = config.id;
                                break;
                            }
                        }
                        
                        if (configId) {
                            const promise = fetch('/temas-colores', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    opcion: 'GuardarConfiguracion',
                                    id: configId,
                                    tema_id: temaActualId,
                                    variable_nombre: variable,
                                    variable_valor: valor,
                                    grupo: 'bordes' // Esto debería venir de la configuración original
                                })
                            }).then(r => r.json());
                            promises.push(promise);
                        }
                    }
                    
                    return Promise.all(promises);
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const todosExitosos = result.value.every(r => r.success);
                    
                    if (todosExitosos) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Guardado!',
                            text: 'Todos los cambios se guardaron correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Resetear estado
                        cambiosPendientes = false;
                        configuracionesTemporales = {};
                        document.getElementById('btnGuardarCambios').innerHTML = 
                            `<i class="bi bi-save me-1"></i>Guardar`;
                            
                        // Recargar configuraciones para obtener valores actualizados
                        cargarConfiguraciones(temaActualId);
                    } else {
                        Swal.fire('Error', 'Algunos cambios no se pudieron guardar', 'error');
                    }
                }
            });
        }
        
        // Funciones auxiliares
        function cambiarVista(vista) {
            const btns = document.querySelectorAll('.view-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            const previewContent = document.getElementById('previewContent');
            switch(vista) {
                case 'desktop':
                    previewContent.style.maxWidth = '100%';
                    break;
                case 'tablet':
                    previewContent.style.maxWidth = '768px';
                    previewContent.style.margin = '0 auto';
                    break;
                case 'mobile':
                    previewContent.style.maxWidth = '375px';
                    previewContent.style.margin = '0 auto';
                    break;
            }
        }
        
        function seleccionarPlantilla(tipo) {
            const templates = document.querySelectorAll('.theme-template');
            templates.forEach(t => {
                t.classList.remove('border-primary');
                t.classList.remove('bg-primary-subtle');
            });
            event.target.closest('.theme-template').classList.add('border-primary', 'bg-primary-subtle');
        }
        
        function mostrarPreconfiguraciones() {
            Swal.fire({
                title: 'Preconfiguraciones',
                text: 'Esta funcionalidad estará disponible próximamente',
                icon: 'info'
            });
        }
        
        function guardarNuevoTema() {
            const nombre = document.getElementById('nombre_tema').value;
            if (!nombre) {
                Swal.fire('Error', 'El nombre del tema es requerido', 'error');
                return;
            }
            
            Swal.fire({
                title: 'Creando tema...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('/temas-colores', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    opcion: 'Crear',
                    nombre_tema: nombre,
                    descripcion: document.getElementById('descripcion').value,
                    es_predeterminado: document.getElementById('es_predeterminado').checked,
                    duplicar_tema_id: document.getElementById('duplicar_tema_id').value || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Creado!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al crear el tema', 'error');
            });
        }
        
        function activarTema() {
            if (!temaActualId) return;
            
            Swal.fire({
                title: '¿Activar este tema?',
                text: 'El tema actual se reemplazará por este',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, activar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/temas-colores', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            opcion: 'Activar',
                            id: temaActualId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Éxito', 'Tema activado correctamente', 'success');
                            location.reload();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Error al activar el tema', 'error');
                    });
                }
            });
        }
        
        function eliminarTema() {
            if (!temaActualId) return;
            
            Swal.fire({
                title: '¿Eliminar este tema?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/temas-colores', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            opcion: 'Eliminar',
                            id: temaActualId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Éxito', 'Tema eliminado correctamente', 'success');
                            location.reload();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Error al eliminar el tema', 'error');
                    });
                }
            });
        }
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Editor de temas cargado - Versión responsiva');
            
            // Si hay un tema activo, seleccionarlo automáticamente
            const temaActivo = document.querySelector('.tema-item.active');
            if (temaActivo) {
                const temaId = temaActivo.dataset.id;
                cargarTema(parseInt(temaId), temaActivo);
            }
            
            // Prevenir scroll horizontal
            document.body.style.overflowX = 'hidden';
        });
    </script>