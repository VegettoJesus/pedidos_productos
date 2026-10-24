<div class="modal fade" id="modalEvidencia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-upload"></i> Subir Evidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="evPedidoId">
                <select id="evTipo" class="form-select mb-2">
                    <option value="empaquetado">Empaquetado</option>
                    <option value="guia_envio">Guía de envío</option>
                    <option value="en_transito">En tránsito</option>
                    <option value="entrega_cliente">Entrega al cliente</option>
                    <option value="dni_receptor">DNI del receptor</option>
                    <option value="firma_receptor">Firma del receptor</option>
                    <option value="incidencia">Incidencia</option>
                    <option value="devolucion">Devolución</option>
                    <option value="otro">Otro</option>
                </select>
                <input type="file" id="evArchivo" class="form-control mb-2">
                <textarea id="evDescripcion" class="form-control mb-2" placeholder="Descripción (opcional)"></textarea>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="evVisible" checked>
                    <label class="form-check-label" for="evVisible">Visible para el cliente</label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-info" onclick="enviarEvidencia()"><i class="bi bi-upload"></i> Subir</button>
            </div>
        </div>
    </div>
</div>