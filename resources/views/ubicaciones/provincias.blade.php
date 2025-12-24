<div class="container-fluid px-2">
  <div class="card text-center text-white bg-dark">
    <div class="card-header">
        Lista de Provincias
    </div>
    <div class="card-body">
    <div class="row mb-3">
        <div class="col-lg-2 col-md-6 col-sm-12 d-flex align-items-end mt-2">
            @permiso('ubicaciones/provincias', 'crear')
                <button id="btnNuevo" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                    <i class="bi bi-plus-circle me-2"></i> Nuevo
                </button>
            @endpermiso
        </div>
      </div>
        <div class="table-responsive mt-4">
        <table id="tablaProvincias" class="w-100 table  mt-2">
          <thead>
              <tr>
                  <th></th>
                  <th class="text-center">Id</th>
                  <th class="text-center">Nombres</th>
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
<!-- Modal Provincia -->
<div class="modal fade" id="modalProvincia" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="tituloModal">Nueva Provincia</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formProvincia">
            <input type="hidden" id="idProvincia" name="id">

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="departamento_id" class="form-label">Departamento</label>
                <select id="departamento_id" name="departamento_id" class="form-select" required>
                    <option value="">Seleccione</option>
                    @foreach($departamentos as $dep)
                        <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="btnGuardar" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>