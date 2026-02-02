<div class="container-fluid pb-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h3 class="mb-0">
                        <i class="bi bi-envelope me-2"></i>Configuración del Servidor de Correo
                    </h3>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-info" id="btnProbarConexion">
                            <i class="bi bi-plug me-1"></i>Probar Conexión
                        </button>
                        <button type="button" class="btn btn-success" id="btnGuardar">
                            <i class="bi bi-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario Principal -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form id="formServidorCorreo" novalidate>
                        @csrf
                        
                        <!-- Sección 1: Configuraciones Predefinidas -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-lightning-charge me-2"></i>Configuraciones Rápidas
                                </h4>
                                <p class="text-muted mb-3">Seleccione una configuración predefinida para llenar automáticamente los campos.</p>
                                
                                <div class="row g-3" id="configuracionesRapidas">
                                    <!-- Se llenará dinámicamente con JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Configuración SMTP -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-gear me-2"></i>Configuración SMTP
                                </h4>
                            </div>
                            
                            <!-- Servidor de Correo -->
                            <div class="col-md-6 mb-3">
                                <label for="servidor_correo" class="form-label fw-bold">
                                    <i class="bi bi-server me-1"></i>Servidor SMTP
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-hdd-network"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="servidor_correo" 
                                           name="servidor_correo"
                                           placeholder="Ej: smtp.gmail.com"
                                           maxlength="255"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="btnAyudaServidor">
                                        <i class="bi bi-question-circle"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor ingrese el servidor SMTP.
                                </div>
                                <small class="text-muted">Dirección del servidor de correo saliente (SMTP).</small>
                            </div>

                            <!-- Puerto -->
                            <div class="col-md-6 mb-3">
                                <label for="puerto" class="form-label fw-bold">
                                    <i class="bi bi-signpost me-1"></i>Puerto
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-123"></i>
                                    </span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="puerto" 
                                           name="puerto"
                                           min="1"
                                           max="65535"
                                           required>
                                    <span class="input-group-text">#</span>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor ingrese un puerto válido (1-65535).
                                </div>
                                <small class="text-muted">Puerto de conexión SMTP (587-TLS, 465-SSL, 25-Sin encriptar).</small>
                            </div>

                            <!-- Seguridad -->
                            <div class="col-md-6 mb-3">
                                <label for="seguridad" class="form-label fw-bold">
                                    <i class="bi bi-shield-check me-1"></i>Seguridad
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <select class="form-select" id="seguridad" name="seguridad" required>
                                        <option value="tls">TLS/STARTTLS (Recomendado)</option>
                                        <option value="ssl">SSL</option>
                                        <option value="ninguna">Sin encriptación</option>
                                    </select>
                                </div>
                                <small class="text-muted" id="infoSeguridad">
                                    Conexión cifrada después del handshake (puerto 587).
                                </small>
                            </div>

                            <!-- Activo -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-power me-1"></i>Estado
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="activo" name="activo" checked>
                                    <label class="form-check-label" for="activo">
                                        Configuración activa
                                    </label>
                                </div>
                                <small class="text-muted">Solo una configuración puede estar activa a la vez.</small>
                            </div>
                        </div>

                        <!-- Sección 3: Credenciales de Acceso -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-key me-2"></i>Credenciales de Acceso
                                </h4>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Importante:</strong> Estas credenciales se almacenan encriptadas en la base de datos.
                                </div>
                            </div>
                            
                            <!-- Nombre de Acceso -->
                            <div class="col-md-6 mb-3">
                                <label for="nombre_acceso" class="form-label fw-bold">
                                    <i class="bi bi-person-badge me-1"></i>Nombre de Acceso / Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-at"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nombre_acceso" 
                                           name="nombre_acceso"
                                           placeholder="Ej: notificaciones@mitienda.com"
                                           maxlength="255"
                                           required>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor ingrese el nombre de acceso.
                                </div>
                                <small class="text-muted">Usuario o email para autenticación SMTP.</small>
                            </div>

                            <!-- Contraseña -->
                            <div class="col-md-6 mb-3">
                                <label for="contraseña" class="form-label fw-bold">
                                    <i class="bi bi-key-fill me-1"></i>Contraseña
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-shield-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="contraseña" 
                                           name="contraseña"
                                           placeholder="Ingrese la contraseña"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" id="btnMostrarContraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="errorContraseña">
                                    La contraseña es requerida.
                                </div>
                                <small class="text-muted">
                                    Dejar en blanco para mantener la contraseña actual.
                                    <span class="fw-bold" style='color:#7c7c19;' id="infoContraseñaActual"></span>
                                </small>
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div class="col-md-6 mb-3" id="confirmarContraseñaContainer" style="display: none;">
                                <label for="confirmar_contraseña" class="form-label fw-bold">
                                    <i class="bi bi-key me-1"></i>Confirmar Contraseña
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-shield-check"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirmar_contraseña" 
                                           name="confirmar_contraseña"
                                           placeholder="Confirme la contraseña"
                                           autocomplete="new-password">
                                </div>
                                <div class="invalid-feedback" id="errorConfirmarContraseña">
                                    Las contraseñas no coinciden.
                                </div>
                            </div>

                            <!-- Tipo de Contraseña -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-info-circle me-1"></i>Nota sobre Contraseñas
                                </label>
                                <div class="alert alert-info small">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    <strong>Para Gmail:</strong> Use "Contraseña de aplicación" si tiene 2FA activado.<br>
                                    <strong>Para Outlook:</strong> Use su contraseña normal o contraseña de aplicación.<br>
                                    <strong>Para Hosting:</strong> Use la contraseña proporcionada por su proveedor.
                                </div>
                            </div>
                        </div>

                        <!-- Sección 4: Información de Conexión -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="bi bi-info-circle me-1"></i>Información de Conexión
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <i class="bi bi-check-circle me-1"></i> Estado Actual
                                                    </div>
                                                    <div class="card-body">
                                                        <p id="estadoConexion" class="mb-0">
                                                            <span class="badge bg-secondary">No verificado</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <i class="bi bi-clock me-1"></i> Última Actualización
                                                    </div>
                                                    <div class="card-body">
                                                        <p id="ultimaActualizacion" class="mb-0 text-muted">Cargando...</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <i class="bi bi-gear me-1"></i> Configuración
                                                    </div>
                                                    <div class="card-body">
                                                        <p id="tipoConfiguracion" class="mb-0 text-muted">Personalizada</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Detalles Técnicos -->
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#detallesTecnicos">
                                                <i class="bi bi-code-slash me-1"></i> Ver detalles técnicos
                                            </button>
                                            
                                            <div class="collapse mt-2" id="detallesTecnicos">
                                                <div class="card card-body">
                                                    <pre class="mb-0 small" id="detallesConfiguracion"></pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="btnRestablecer">
                        <i class="bi bi-arrow-clockwise me-1"></i>Restablecer
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning" id="btnProbarCorreo">
                            <i class="bi bi-envelope-check me-1"></i>Probar Envío
                        </button>
                        <button type="button" class="btn btn-success" id="btnGuardarFooter">
                            <i class="bi bi-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para probar conexión -->
<div class="modal fade" id="modalProbarConexion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plug me-2"></i>Probar Conexión SMTP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Email de prueba:</label>
                    <input type="email" class="form-control" id="emailPrueba" 
                           placeholder="ejemplo@email.com" value="">
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Se enviará un email de prueba para verificar la configuración.
                </div>
                <div id="resultadoPrueba" class="mt-3" style="display: none;">
                    <!-- Resultado se mostrará aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnIniciarPrueba">
                    <i class="bi bi-play-circle me-1"></i>Iniciar Prueba
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal ayuda servidores -->
<div class="modal fade" id="modalAyudaServidores" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-question-circle me-2"></i>Servidores SMTP Comunes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>Servidor SMTP</th>
                                <th>Puerto</th>
                                <th>Seguridad</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Gmail</strong></td>
                                <td>smtp.gmail.com</td>
                                <td>587</td>
                                <td>TLS</td>
                                <td>Requerir contraseña de aplicación si tiene 2FA</td>
                            </tr>
                            <tr>
                                <td><strong>Outlook</strong></td>
                                <td>smtp.office365.com</td>
                                <td>587</td>
                                <td>TLS</td>
                                <td>Usar contraseña normal</td>
                            </tr>
                            <tr>
                                <td><strong>Yahoo</strong></td>
                                <td>smtp.mail.yahoo.com</td>
                                <td>465</td>
                                <td>SSL</td>
                                <td>Puerto 587 también funciona</td>
                            </tr>
                            <tr>
                                <td><strong>Hosting</strong></td>
                                <td>mail.tudominio.com</td>
                                <td>465</td>
                                <td>SSL</td>
                                <td>Verificar con tu proveedor de hosting</td>
                            </tr>
                            <tr>
                                <td><strong>SendGrid</strong></td>
                                <td>smtp.sendgrid.net</td>
                                <td>587</td>
                                <td>TLS</td>
                                <td>Requiere API Key como contraseña</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-check-circle me-1"></i>Entendido
                </button>
            </div>
        </div>
    </div>
</div>