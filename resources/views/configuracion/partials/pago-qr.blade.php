<template id="template-qr">
    <form id="formMetodo" data-slug="qr">
        @csrf
        <input type="hidden" name="id" value="">
        <input type="hidden" name="opcion" value="Actualizar">

        <div class="row g-3">
            <div class="col-12">
                <div class="config-header-qr">
                    <div class="config-header-row">
                        <div class="config-header-icon">
                            <i class="bi bi-qr-code"></i>
                        </div>
                        <div class="config-header-text">
                            <h5 class="mb-0 text-white">Código QR de pago</h5>
                            <small class="text-white-50">Configura el pago mediante escaneo de código QR</small>
                        </div>
                        <div class="config-header-switch">
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" name="activo" id="activoQR">
                                <label class="form-check-label text-white" for="activoQR">
                                    <span class="estado-badge">Inactivo</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="config-section">
                    <div class="config-section-header">
                        <div class="config-section-icon bg-primary-soft">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Información básica</h6>
                            <small class="text-muted">Datos que verá el cliente al elegir este método</small>
                        </div>
                    </div>
                    <div class="config-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Título
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Nombre que verá el cliente al elegir el método de pago"></i>
                                </label>
                                <input type="text" class="form-control" name="titulo" required
                                       placeholder="Ej: Pago con código QR">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Icono del método
                                    <i class="bi bi-question-circle text-muted ms-1"
                                    data-bs-toggle="tooltip"
                                    title="Sube una imagen pequeña (PNG, JPG o SVG) que se mostrará junto al método de pago"></i>
                                </label>
                                <div class="upload-area-icon" id="uploadIcono">
                                    <div class="upload-empty-icono">
                                        <i class="bi bi-image"></i>
                                        <div class="upload-title-icono">Sube el icono</div>
                                        <div class="upload-hint-icono">PNG, JPG, SVG — Máx 1MB</div>
                                    </div>
                                    <input type="file" name="icono_imagen_file" accept="image/*" hidden>
                                    <input type="hidden" name="icono_imagen">
                                </div>
                                <div id="previewIcono" class="mt-2"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Descripción
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Texto breve que se muestra debajo del título"></i>
                                </label>
                                <textarea class="form-control" name="descripcion_cfg" rows="2"
                                          placeholder="Ej: Escanea el código QR desde tu app bancaria y realiza el pago"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Mensaje emergente
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Mensaje que se muestra al pasar el mouse por encima o al hacer clic en el método"></i>
                                </label>
                                <textarea class="form-control" name="mensaje_emergente" rows="2"
                                          placeholder="Ej: Abre tu app, escanea el QR y realiza el pago para confirmar el pedido"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="config-section">
                    <div class="config-section-header">
                        <div class="config-section-icon bg-info-soft">
                            <i class="bi bi-phone"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Datos del QR</h6>
                            <small class="text-muted">Imagen y teléfono del afiliado para el pago</small>
                        </div>
                    </div>
                    <div class="config-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Número de teléfono del afiliado
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Número asociado al QR para confirmar el pago"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control" name="telefono_afiliado"
                                           placeholder="Ej: 999888777">
                                </div>
                                <small class="text-muted">Número que verá el cliente para validar el pago</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Importe límite
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Monto máximo permitido para pagar con QR. Si se supera, se muestra un mensaje"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" class="form-control" name="importe_limite"
                                           placeholder="0.00">
                                </div>
                                <small class="text-muted">Dejar vacío para no aplicar límite</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Mensaje cuando se excede el límite
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Texto que verá el cliente si el monto supera el importe límite"></i>
                                </label>
                                <input type="text" class="form-control" name="mensaje_limite"
                                       placeholder="Ej: El monto supera el límite permitido para pago con QR. Usa otro método.">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="config-section">
                    <div class="config-section-header">
                        <div class="config-section-icon bg-warning-soft">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Imagen del código QR</h6>
                            <small class="text-muted">Sube la imagen del QR que escaneará el cliente</small>
                        </div>
                    </div>
                    <div class="config-section-body">
                        <div class="upload-area-qr" id="uploadQR">
                            {{-- Estado vacío --}}
                            <div class="upload-empty">
                                <div class="upload-icon-wrapper">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                </div>
                                <div class="upload-title">Arrastra tu QR aquí o haz clic para subir</div>
                                <div class="upload-hint">JPG, PNG, WEBP o SVG — Máximo 2MB</div>
                                <div class="upload-recommendation">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Recomendado: 400×400 px o superior para buena legibilidad
                                </div>
                            </div>
                            <input type="file" name="imagen_qr_file" accept="image/*" hidden>
                            <input type="hidden" name="imagen_qr">
                        </div>
                        <div id="previewQR" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="config-footer-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-lg me-1"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </form>
</template>