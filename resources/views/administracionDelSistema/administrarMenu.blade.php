<div class="container-fluid px-2" style="display: inline-grid">
  <div class="card text-center text-white bg-dark">
    <div class="card-header">
        Lista de Menu
    </div>
    <div class="card-body">
    <div class="row mb-3">
        <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 text-dark fw-bold">
          <label for="filtro_padre">Padre:</label>
          <select class="form-select form-select-sm" id="filtro_padre" name="filtro_padre">
              <option value="">TODOS</option>
              @foreach($padres as $padre)
                  <option value="{{ $padre->id }}">{{ strtoupper($padre->nombre) }}</option>
              @endforeach
          </select>
      </div>
        <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 text-dark fw-bold">
          <label for="filtro_permiso">Permiso (Rol):</label>
          <select class="form-select form-select-sm" id="filtro_permiso" name="filtro_permiso">
              <option value="">TODOS</option>
              @foreach($roles as $rol)
                  <option value="{{ $rol->id }}">{{ strtoupper($rol->name) }}</option>
              @endforeach
          </select>
      </div>

        <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12 d-flex align-items-end mt-2">
          <button id="btnBuscar" class="btn btn-success w-100">Buscar</button>
        </div>
        <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12 d-flex align-items-end mt-2">
            @permiso('AdministracionDelSistema/administrarMenu', 'crear')
                <button id="btnNuevo" class="btn btn-primary w-100">Nuevo</button>
            @endpermiso
        </div>
      </div>
        <div class="table-responsive mt-4">
        <table id="tablaMenus" class="w-100 table mt-2">
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

<div class="modal fade" id="modalPermisos" tabindex="-1" data-bs-backdrop="static"  data-bs-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Gestionar Permisos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table align-middle" id="tablaPermisos">
            <thead class="table-dark">
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
</div>
<script>
    window.padres = @json($padres);
</script>