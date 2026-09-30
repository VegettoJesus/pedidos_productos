<template id="template-transferencia">
    <form id="formMetodo" data-slug="transferencia">
        @csrf
        <input type="hidden" name="id" value="">
        <input type="hidden" name="opcion" value="Actualizar">

        <div class="row g-3">
            <div class="col-12">
                <div class="config-header-transferencia">
                    <div class="config-header-row">
                        <div class="config-header-icon">
                            <i class="bi bi-bank"></i>
                        </div>
                        <div class="config-header-text">
                            <h5 class="mb-0 text-white">Transferencia bancaria directa</h5>
                            <small class="text-white-50">Configura las cuentas bancarias para recibir pagos</small>
                        </div>
                        <div class="config-header-switch">
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" name="activo" id="activoTransf">
                                <label class="form-check-label text-white" for="activoTransf">
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
                                    <span class="text-danger">*</span>
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Nombre que verá el cliente al elegir el método de pago"></i>
                                </label>
                                <input type="text" class="form-control" name="titulo" required
                                       placeholder="Ej: Transferencia bancaria directa">
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Descripción
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Texto breve que se muestra debajo del título en el checkout"></i>
                                </label>
                                <textarea class="form-control" name="descripcion_cfg" rows="2"
                                          placeholder="Ej: Realiza tu pago mediante transferencia bancaria a cualquiera de nuestras cuentas"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Instrucciones
                                    <i class="bi bi-question-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Indicaciones adicionales que verá el cliente después de confirmar el pedido"></i>
                                </label>
                                <textarea class="form-control" name="instrucciones" rows="3"
                                          placeholder="Ej: Envía el comprobante de pago a nuestro WhatsApp o correo para confirmar tu pedido"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="config-section">
                    <div class="config-section-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="config-section-icon bg-success-soft">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Cuentas bancarias</h6>
                                <small class="text-muted">Agrega todas las cuentas donde el cliente podrá transferir</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btnAgregarCuenta">
                            <i class="bi bi-plus-lg me-1"></i> Agregar cuenta
                        </button>
                    </div>
                    <div class="config-section-body p-0">

                        {{-- Tabla de cuentas --}}
                        <div class="table-responsive" id="wrapperTablaCuentas" style="display: none;">
                            <table class="table tabla-cuentas mb-0" id="tablaCuentas">
                                <thead>
                                    <tr>
                                        <th style="width: 28%;">
                                            <i class="bi bi-person me-1"></i> Titular
                                        </th>
                                        <th style="width: 22%;">
                                            <i class="bi bi-hash me-1"></i> Número de cuenta
                                        </th>
                                        <th style="width: 20%;">
                                            <i class="bi bi-bank me-1"></i> Banco
                                        </th>
                                        <th style="width: 25%;">
                                            <i class="bi bi-credit-card-2-front me-1"></i> CCI (opcional)
                                        </th>
                                        <th style="width: 60px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="bodyCuentas">
                                    {{-- Filas dinámicas --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- Estado vacío --}}
                        <div id="emptyCuentas" class="cuentas-empty-state">
                            <div class="empty-icon-wrapper">
                                <i class="bi bi-bank2"></i>
                            </div>
                            <div class="empty-title">No hay cuentas registradas</div>
                            <div class="empty-desc">
                                Agrega al menos una cuenta bancaria para que el cliente pueda realizar la transferencia.
                            </div>
                        </div>

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

    <template id="filaCuentaTemplate">
        <tr class="cuenta-row">
            <td>
                <input type="text" 
                       class="form-control form-control-sm" 
                       name="cuentas[__INDEX__][nombre_cuenta]" 
                       placeholder="Ej: Juan Pérez">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="text" 
                           class="form-control" 
                           name="cuentas[__INDEX__][numero_cuenta]" 
                           placeholder="Ej: 1234567890">
                </div>
            </td>
            <td>
                <input type="text" 
                       class="form-control form-control-sm" 
                       name="cuentas[__INDEX__][nombre_banco]" 
                       placeholder="Ej: BCP">
            </td>
            <td>
                <input type="text" 
                       class="form-control form-control-sm" 
                       name="cuentas[__INDEX__][cci]" 
                       placeholder="Ej: 002-193-...">
            </td>
            <td class="text-center">
                <button type="button" 
                        class="btn btn-sm btn-outline-danger btn-eliminar-cuenta" 
                        title="Eliminar cuenta">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>
</template>