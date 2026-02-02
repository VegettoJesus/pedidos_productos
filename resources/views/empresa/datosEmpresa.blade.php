<style>
    /* Estilos específicos para datos empresa */

/* Vista previa animada */
.card-preview {
    transition: all 0.3s ease;
    border-left: 4px solid var(--color-primary);
}

.card-preview:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Validación RUC */
.ruc-valido {
    border-color: var(--color-success) !important;
}

.ruc-invalido {
    border-color: var(--color-danger) !important;
}

/* Indicador de campo requerido */
.required-field::after {
    content: " *";
    color: var(--color-danger);
}

/* Badges para estados */
.badge-ruc {
    font-size: 0.7em;
    padding: 0.25em 0.6em;
}

/* Botones de acción flotantes */
.btn-action-floating {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 1000;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.btn-action-floating:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
}

/* Animación de carga para RUC */
.loading-ruc {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid #f3f3f3;
    border-top: 2px solid var(--color-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Estilos para modales de datos empresa */
.modal-empresa .modal-header {
    background: linear-gradient(135deg, var(--color-primary), #0b5ed7);
}

/* Responsive para vista previa */
@media (max-width: 768px) {
    .card-preview {
        margin-bottom: 1rem;
    }
    
    .btn-action-floating {
        bottom: 1rem;
        right: 1rem;
        width: 3rem;
        height: 3rem;
    }
}
</style>
<div class="container-fluid pb-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h3 class="mb-0">
                        <i class="bi bi-building me-2"></i>Datos Legales y de Contacto
                    </h3>
                    <div class="d-flex gap-2">
                        <!-- <button type="button" class="btn btn-info" id="btnVerificarRUC">
                            <i class="bi bi-search me-1"></i>Verificar RUC
                        </button> -->
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
                    <form id="formDatosEmpresa" novalidate>
                        @csrf
                        
                        <!-- Sección 1: Información Fiscal -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-file-earmark-text me-2"></i>Información Fiscal
                                </h4>
                            </div>
                            
                            <!-- RUC -->
                            <div class="col-md-6 mb-3">
                                <label for="ruc" class="form-label fw-bold">
                                    <i class="bi bi-card-checklist me-1"></i>RUC
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-123"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="ruc" 
                                           name="ruc"
                                           placeholder="20123456789"
                                           maxlength="11"
                                           pattern="[0-9]{11}"
                                           required>
                                </div>
                                <div class="invalid-feedback" id="errorRUC">
                                    Ingrese un RUC válido (11 dígitos numéricos).
                                </div>
                                <small class="text-muted">Registro Único de Contribuyente (11 dígitos).</small>
                                <div id="rucFeedback" class="mt-1" style="display: none;"></div>
                            </div>

                            <!-- Razón Social -->
                            <div class="col-md-6 mb-3">
                                <label for="razon_social" class="form-label fw-bold">
                                    <i class="bi bi-building me-1"></i>Razón Social
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-briefcase"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="razon_social" 
                                           name="razon_social"
                                           placeholder="EMPRESA SAC"
                                           maxlength="255"
                                           required>
                                </div>
                                <div class="invalid-feedback">
                                    La razón social es obligatoria.
                                </div>
                                <small class="text-muted">Nombre legal completo de la empresa.</small>
                            </div>

                            <!-- Nombre Comercial -->
                            <div class="col-md-6 mb-3">
                                <label for="nombre_comercial" class="form-label fw-bold">
                                    <i class="bi bi-shop me-1"></i>Nombre Comercial
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-tag"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nombre_comercial" 
                                           name="nombre_comercial"
                                           placeholder="Mi Marca Comercial"
                                           maxlength="255">
                                </div>
                                <small class="text-muted">Nombre público/marca comercial (opcional).</small>
                            </div>

                            <!-- Propietario -->
                            <div class="col-md-3 mb-3">
                                <label for="propietario_nombre" class="form-label fw-bold">
                                    <i class="bi bi-person me-1"></i>Nombre Propietario
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="propietario_nombre" 
                                       name="propietario_nombre"
                                       placeholder="Juan"
                                       maxlength="255"
                                       required>
                                <div class="invalid-feedback">
                                    Nombre del propietario requerido.
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="propietario_apellido" class="form-label fw-bold">
                                    <i class="bi bi-person-badge me-1"></i>Apellido Propietario
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="propietario_apellido" 
                                       name="propietario_apellido"
                                       placeholder="Pérez"
                                       maxlength="255"
                                       required>
                                <div class="invalid-feedback">
                                    Apellido del propietario requerido.
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Ubicación -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-geo-alt me-2"></i>Ubicación
                                </h4>
                            </div>
                            
                            <!-- Dirección -->
                            <div class="col-md-8 mb-3">
                                <label for="direccion" class="form-label fw-bold">
                                    <i class="bi bi-geo me-1"></i>Dirección Completa
                                </label>
                                <textarea class="form-control" 
                                          id="direccion" 
                                          name="direccion"
                                          rows="2"
                                          placeholder="Av. Principal 123, Piso 4, Oficina 401"
                                          required></textarea>
                                <div class="invalid-feedback">
                                    La dirección es obligatoria.
                                </div>
                                <small class="text-muted">Calle, número, piso, oficina, referencia.</small>
                            </div>

                            <!-- UBIGEO -->
                            <div class="col-md-4 mb-3">
                                <label for="ubigeo" class="form-label fw-bold">
                                    <i class="bi bi-pin-map me-1"></i>Código UBIGEO
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-123"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="ubigeo" 
                                           name="ubigeo"
                                           placeholder="150101"
                                           maxlength="6"
                                           pattern="[0-9]{6}"
                                           required>
                                </div>
                                <div class="invalid-feedback">
                                    El código UBIGEO debe tener 6 dígitos.
                                </div>
                                <small class="text-muted">Código de ubicación geográfica (6 dígitos).</small>
                            </div>

                            <!-- Departamento -->
                            <div class="col-md-4 mb-3">
                                <label for="departamento" class="form-label fw-bold">
                                    <i class="bi bi-building me-1"></i>Departamento
                                </label>
                                <select class="form-select" id="departamento_id" name="departamento_id" required>
                                    <option value="">Seleccione departamento</option>
                                    @foreach($departamentos as $dep)
                                        <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    Seleccione un departamento.
                                </div>
                            </div>

                            <!-- Provincia -->
                            <div class="col-md-4 mb-3">
                                <label for="provincia" class="form-label fw-bold">
                                    <i class="bi bi-geo me-1"></i>Provincia
                                </label>
                                <select class="form-select" id="provincia_id" name="provincia_id" required disabled>
                                    <option value="">Seleccione provincia</option>
                                </select>
                            </div>

                            <!-- Distrito -->
                            <div class="col-md-4 mb-3">
                                <label for="distrito" class="form-label fw-bold">
                                    <i class="bi bi-signpost me-1"></i>Distrito
                                </label>
                                <select class="form-select" id="distrito_id" name="distrito_id" required disabled>
                                    <option value="">Seleccione distrito</option>
                                </select>
                            </div>

                            <!-- Google Maps URL -->
                            <div class="col-md-12 mb-3">
                                <label for="maps_url" class="form-label fw-bold">
                                    <i class="bi bi-google me-1"></i>Google Maps
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-link-45deg"></i>
                                    </span>
                                    <input type="url" 
                                           class="form-control" 
                                           id="maps_url" 
                                           name="maps_url"
                                           placeholder="https://goo.gl/maps/ABC123DEF456"
                                           maxlength="500">
                                    <button class="btn btn-outline-success" type="button" id="btnAbrirMaps">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Enlace a Google Maps (opcional).</small>
                            </div>
                        </div>

                        <!-- Sección 3: Contacto -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="bi bi-telephone me-2"></i>Contacto
                                </h4>
                            </div>
                            
                            <!-- Teléfono -->
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label fw-bold">
                                    <i class="bi bi-telephone-fill me-1"></i>Teléfono Fijo
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-telephone"></i>
                                    </span>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="telefono" 
                                           name="telefono"
                                           placeholder="+51 1 2345678"
                                           maxlength="20">
                                </div>
                                <small class="text-muted">Teléfono fijo para contacto formal.</small>
                            </div>

                            <!-- Celular/WhatsApp -->
                            <div class="col-md-6 mb-3">
                                <label for="celular" class="form-label fw-bold">
                                    <i class="bi bi-whatsapp me-1"></i>Celular (WhatsApp)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-phone"></i>
                                    </span>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="celular" 
                                           name="celular"
                                           placeholder="+51 987654321"
                                           maxlength="20">
                                    <button class="btn btn-outline-success" type="button" id="btnWhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Celular para WhatsApp/llamadas rápidas.</small>
                            </div>
                        </div>

                        <!-- Sección 4: Vista Previa -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="bi bi-eye me-1"></i>Vista Previa
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <i class="bi bi-building me-1"></i> Información Fiscal
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="mb-1"><strong>RUC:</strong> <span id="previewRUC" class="text-muted">-</span></p>
                                                        <p class="mb-1"><strong>Razón Social:</strong> <span id="previewRazonSocial" class="text-muted">-</span></p>
                                                        <p class="mb-0"><strong>Nombre Comercial:</strong> <span id="previewNombreComercial" class="text-muted">-</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <i class="bi bi-geo-alt me-1"></i> Ubicación
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="mb-1"><strong>Dirección:</strong> <span id="previewDireccion" class="text-muted">-</span></p>
                                                        <p class="mb-1"><strong>Ubicación:</strong> <span id="previewUbicacion" class="text-muted">-</span></p>
                                                        <p class="mb-0"><strong>UBIGEO:</strong> <span id="previewUbigeo" class="text-muted">-</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <i class="bi bi-person me-1"></i> Propietario
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="mb-1"><strong>Nombre:</strong> <span id="previewPropietario" class="text-muted">-</span></p>
                                                        <p class="mb-1"><strong>Teléfono:</strong> <span id="previewTelefono" class="text-muted">-</span></p>
                                                        <p class="mb-0"><strong>Celular:</strong> <span id="previewCelular" class="text-muted">-</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Timestamps -->
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between small text-muted">
                                                <div>
                                                    <i class="bi bi-calendar-plus me-1"></i>
                                                    <span id="previewCreado">Creado: -</span>
                                                </div>
                                                <div>
                                                    <i class="bi bi-calendar-check me-1"></i>
                                                    <span id="previewActualizado">Actualizado: -</span>
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
                        <!-- <button type="button" class="btn btn-warning" id="btnExportarPDF">
                            <i class="bi bi-file-pdf me-1"></i>Exportar PDF
                        </button> -->
                        <button type="button" class="btn btn-success" id="btnGuardarFooter">
                            <i class="bi bi-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ayuda UBIGEO -->
<div class="modal fade" id="modalAyudaUbigeo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>¿Qué es el UBIGEO?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>UBIGEO</strong> es el código de ubicación geográfica utilizado en Perú.</p>
                <p><strong>Formato:</strong> 6 dígitos</p>
                <ul>
                    <li><strong>Primeros 2 dígitos:</strong> Departamento</li>
                    <li><strong>Siguientes 2 dígitos:</strong> Provincia</li>
                    <li><strong>Últimos 2 dígitos:</strong> Distrito</li>
                </ul>
                <p><strong>Ejemplo para Lima, Lima, San Isidro:</strong> <code>150131</code></p>
                <div class="alert alert-info">
                    <i class="bi bi-lightbulb me-2"></i>
                    Puedes consultar códigos UBIGEO en la página del INEI.
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