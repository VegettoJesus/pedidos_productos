<div class="container-fluid py-3">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Gestión de Pedidos</h5>
            <span class="badge bg-black" id="badgeTotal">0 pedidos</span>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" id="filtroBuscar" class="form-control" placeholder="Buscar código o cliente...">
                </div>
                <div class="col-md-2">
                    <select id="filtroEstado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="pago_en_revision">Pago en revisión</option>
                        <option value="pagado">Pagado</option>
                        <option value="en_preparacion">En preparación</option>
                        <option value="enviado">Enviado</option>
                        <option value="entregado">Entregado</option>
                        <option value="cancelado">Cancelado</option>
                        <option value="reembolsado">Reembolsado</option>
                        <option value="rechazado">Rechazado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" id="fechaDesde" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="date" id="fechaHasta" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" id="btnFiltrar">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="tablaPedidos">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPedidos"></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted" id="paginacionInfo">Mostrando 0 de 0 pedidos</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginacionControles">
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-black">
                <h5 class="modal-title"><i class="bi bi-receipt"></i> Detalle del Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetalleBody"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReembolso" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Reembolso de Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reembolsoPedidoId">

                <div id="reembolsoItemsList"></div>

                <div id="reembolsoEnvioSection" class="reembolso-envio mt-3" style="display:none;">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="reembolsoIncluirEnvio" checked>
                        <label class="form-check-label" for="reembolsoIncluirEnvio">
                            Incluir envío en el reembolso
                        </label>
                    </div>
                    <label class="form-label">Monto del envío</label>
                    <input type="number" step="0.01" id="reembolsoMontoEnvio"
                           class="form-control" value="0.00" min="0">
                </div>

                <div class="mt-3">
                    <label class="form-label">
                        Motivo del reembolso <span class="text-danger">*</span>
                    </label>
                    <textarea id="reembolsoMotivo" class="form-control" rows="3"
                              placeholder="Describe el motivo del reembolso..."></textarea>
                </div>

                <div class="reembolso-total mt-3">
                    <strong>Total a reembolsar:</strong>
                    <span id="reembolsoTotal">S/ 0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarReembolso">
                    <i class="bi bi-cash-coin"></i> Confirmar Reembolso
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Datos del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_pedido_id">
                
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nombres *</label>
                        <input type="text" id="edit_nombres" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Apellidos *</label>
                        <input type="text" id="edit_apellidos" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Celular</label>
                        <input type="text" id="edit_celular" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo Documento</label>
                        <select id="edit_documento_tipo" class="form-select">
                            <option value="">Seleccione</option>
                            <option value="DNI">DNI</option>
                            <option value="RUC">RUC</option>
                            <option value="CE">CE</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número Documento</label>
                        <input type="text" id="edit_documento_numero" class="form-control">
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-md-4">
                        <label class="form-label">Departamento</label>
                        <select id="edit_departamento_id" class="form-select">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Provincia</label>
                        <select id="edit_provincia_id" class="form-select" disabled>
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Distrito</label>
                        <select id="edit_distrito_id" class="form-select" disabled>
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    
                    <div class="col-md-8">
                        <label class="form-label">Dirección exacta</label>
                        <input type="text" id="edit_direccion" class="form-control" placeholder="Calle, número, etc.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Referencia</label>
                        <input type="text" id="edit_referencia" class="form-control" placeholder="Opcional">
                    </div>

                    <div class="col-12" id="aviso_costo_envio" style="display:none;">
                        <div class="alert alert-warning mb-0 py-2" style="font-size: 0.85rem;">
                            <i class="bi bi-exclamation-triangle"></i> 
                            El costo de envío para este distrito es de <strong id="nuevo_costo_envio"></strong>. 
                            El costo anterior era <span id="costo_anterior"></span>. 
                            <br>La diferencia se ajustará automáticamente en el pedido.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-dark" id="btnGuardarCliente">
                    <i class="bi bi-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalZoomEvidencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
            
            <button type="button" class="btn btn-dark rounded-circle position-absolute" 
                    data-bs-dismiss="modal" aria-label="Cerrar"
                    style="top: -15px; right: -15px; z-index: 1055; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-x-lg"></i>
            </button>

            <div class="modal-body p-0 d-flex justify-content-center align-items-center">
                <img id="imgZoomEvidencia" src="" alt="Evidencia Ampliada" 
                     style="max-width: 100%; max-height: 85vh; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); cursor: zoom-in; transition: transform 0.3s ease;"
                     onclick="this.classList.toggle('zoomed')">
            </div>
        </div>
    </div>
</div>