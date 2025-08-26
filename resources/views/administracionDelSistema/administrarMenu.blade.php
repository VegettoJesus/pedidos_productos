<div class="container-fluid px-2" style="display: inline-grid">
  <div class="card text-center text-white bg-dark">
    <div class="card-header">
        Lista de Menu
    </div>
    <div class="card-body">
    <div class="row mb-3">
        <div class="col-md-4 text-dark fw-bold">
          <label for="filtro_padre">Padre:</label>
          <select class="form-select form-select-sm" id="filtro_padre" name="filtro_padre">
              <option value="">TODOS</option>
              @foreach($padres as $padre)
                  <option value="{{ $padre->id }}">{{ strtoupper($padre->nombre) }}</option>
              @endforeach
          </select>
      </div>
        <div class="col-md-4 text-dark fw-bold">
          <label for="filtro_permiso">Permiso (Rol):</label>
          <select class="form-select form-select-sm" id="filtro_permiso" name="filtro_permiso">
              <option value="">TODOS</option>
              @foreach($roles as $rol)
                  <option value="{{ $rol->id }}">{{ strtoupper($rol->name) }}</option>
              @endforeach
          </select>
      </div>

        <div class="col-md-2 d-flex align-items-end">
          <button id="btnBuscar" class="btn btn-success w-100">Buscar</button>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button id="btnNuevo" class="btn btn-primary w-100">Nuevo</button>
        </div>
      </div>
        <div class="table-responsive mt-4">
        <table id="tablaMenus" class="table table-striped">
          <thead>
              <tr>
                  <th class="text-center"></th>
                  <th class="text-center">Id</th>
                  <th class="text-center">Nombre</th>
                  <th class="text-center">URL</th>
                  <th class="text-center">Orden</th>
                  <th class="text-center">Fecha Reg.</th>
                  <th class="text-center">Fecha Actu.</th>
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

<div class="modal fade" id="modalPermisos" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Gestionar Permisos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered tablepersonality" id="tablaPermisos">
          <thead>
            <tr>
              <th>Rol</th>
              <th class="text-center">Ver</th>
              <th class="text-center">Crear</th>
              <th class="text-center">Editar</th>
              <th class="text-center">Eliminar</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
    window.padres = @json($padres);
</script>