<div class="container-fluid px-2">
  <div class="card text-center text-white bg-dark">
    <div class="card-header">
        Lista de Categorías
    </div>
    <div class="card-body">
    <div class="row mb-3">
        <div class="col-lg-2 col-md-6 col-sm-12 d-flex align-items-end mt-2">
            @permiso('Catalogo/categorias', 'crear')
                <button id="btnNuevo" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                    <i class="bi bi-plus-circle me-2"></i> Nuevo
                </button>
            @endpermiso
        </div>

        <div class="col-lg-2 col-md-6 col-sm-12 d-flex align-items-end mt-2">
            @permiso('Catalogo/categorias', 'subcategoria')
                <button id="btnNuevoSub" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                    <i class="bi bi-diagram-2 me-2"></i> Nueva Subcategoría
                </button>
            @endpermiso
        </div>
      </div>
        <div class="table-responsive mt-4">
        <table id="tablaCategorias" class="w-100 table  mt-2">
          <thead>
              <tr>
                  <th></th>
                  <th class="text-center">Id</th>
                  <th class="text-center">Nombres</th>
                  <th class="text-center">Fecha Creación</th>
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