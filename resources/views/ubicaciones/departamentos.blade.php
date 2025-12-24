<div class="container-fluid px-2">
  <div class="card text-center text-white bg-dark">
    <div class="card-header">
        Lista de Departamentos
    </div>
    <div class="card-body">
    <div class="row mb-3">
        <div class="col-lg-2 col-md-6 col-sm-12 d-flex align-items-end mt-2">
            @permiso('ubicaciones/departamentos', 'crear')
                <button id="btnNuevo" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                    <i class="bi bi-plus-circle me-2"></i> Nuevo
                </button>
            @endpermiso
        </div>
      </div>
        <div class="table-responsive mt-4">
        <table id="tablaDepartamentos" class="w-100 table  mt-2">
          <thead>
              <tr>
                  <th></th>
                  <th class="text-center">Id</th>
                  <th class="text-center">Nombres</th>
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
<div class="modal fade" id="modalDepartamento" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="tituloModal">Nuevo Departamento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formDepartamento">
          <input type="hidden" id="idDepartamento" name="id">
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Departamento</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
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