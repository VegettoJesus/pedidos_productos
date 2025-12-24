<div class="container-fluid px-2">
  <div class="card text-center text-white bg-dark">
    <div class="card-header">
        Lista de Distritos
    </div>
    <div class="card-body">
    <div class="row mb-3">
        <div class="col-lg-2 col-md-6 col-sm-12 d-flex align-items-end mt-2">
            @permiso('ubicaciones/distritos', 'crear')
                <button id="btnNuevo" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                    <i class="bi bi-plus-circle me-2"></i> Nuevo
                </button>
            @endpermiso
        </div>
      </div>
        <div class="table-responsive mt-4">
        <table id="tablaDistritos" class="w-100 table  mt-2">
          <thead>
              <tr>
                  <th></th>
                  <th class="text-center">Id</th>
                  <th class="text-center">Nombres</th>
                  <th class="text-center">Costo de envio</th>
                  <th class="text-center">Provincia</th>
                  <th class="text-center">Departamento</th>
                  <th class="text-center">Fecha Creación</th>
                  <th class="text-center">Fecha Actualización</th>
                  <th class="text-center">Acciones</th>
              </tr>
          </thead>
          <tbody></tbody>
        </table>
        </div>
    </div>
    <div class="card-footer text-muted">
    </div>
 </div>
</div>
<!-- Modal Distrito -->
<div class="modal fade" id="modalDistrito" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="tituloModal">Nuevo Distrito</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formDistrito">
          <input type="hidden" id="idDistrito" name="id">

          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
          </div>

          <div class="mb-3">
            <label for="costo_envio" class="form-label">Costo de Envío</label>
            <input type="number" step="0.01" class="form-control" id="costo_envio" name="costo_envio" required>
          </div>

          <div class="mb-3">
            <label for="provincia_id" class="form-label">Provincia</label>
            <select class="form-select" id="provincia_id" name="provincia_id" required>
              <option value="">Seleccione...</option>
              @foreach($departamentos as $dep)
                <optgroup label="Departamento: {{ $dep->nombre }}">
                  @foreach($dep->provincias as $prov)
                    <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                  @endforeach
                </optgroup>
              @endforeach
            </select>
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
