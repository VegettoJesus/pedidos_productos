<div class="container-fluid px-2">
  <div class="card text-center text-white bg-dark">
    <div class="card-header">
        Lista de Usuarios
    </div>
    <div class="card-body">
    <div class="row mb-3">
        <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 text-dark fw-bold">
          <label for="filtro_padre">Estado:</label>
          <select class="form-select form-select-sm" id="filtro_estado" name="filtro_estado">
              <option value="">TODOS</option>
              <option value="1">ACTIVO</option>
              <option value="0">INACTIVO</option>
          </select>
        </div>
        <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 text-dark fw-bold">
          <label for="filtro_permiso">Roles:</label>
          <select class="form-select form-select-sm" id="filtro_roles" name="filtro_roles">
              <option value="">TODOS</option>
              @foreach($roles as $rol)
                  <option value="{{ $rol->id }}">{{ strtoupper($rol->name) }}</option>
              @endforeach
          </select>
        </div>
        <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12 d-flex align-items-end mt-md-2 mt-2">
          <button id="btnBuscar" class="btn btn-success w-100">Buscar</button>
        </div>
        <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12 d-flex align-items-end mt-md-2 mt-2">
            @permiso('AdministracionDelSistema/usuarios', 'crear')
                <button id="btnNuevo" class="btn btn-primary w-100">Nuevo</button>
            @endpermiso
        </div>
      </div>
        <div class="table-responsive mt-4">
        <table id="tablaUsuarios" class="w-100 table table-striped mt-2">
          <thead>
              <tr>
                  <th class="text-center"></th>
                  <th class="text-center">Id</th>
                  <th class="text-center">Nombres</th>
                  <th class="text-center">Apellidos</th>
                  <th class="text-center">Email</th>
                  <th class="text-center">Rol</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Conección</th>
                  <th class="text-center">Fecha Creación</th>
                  <th class="text-center">Fecha Actualización</th>
                  <th class="text-center">Fecha Desactivado</th>
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
<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalNuevoUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <!-- Header -->
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="modalNuevoUsuarioLabel"><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Usuario</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <form id="formNuevoUsuario" enctype="multipart/form-data">
          @csrf
          <input type="hidden" id="id_user" name="id_user" value="0">
          <div class="row g-3">

            <!-- Nombres y Apellidos -->
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
              <label for="nombres" class="form-label fw-bold">Nombres</label>
              <input type="text" class="form-control" id="nombres" name="nombres" placeholder="Ingrese nombres" >
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
              <label for="apellidos" class="form-label fw-bold">Apellidos</label>
              <input type="text" class="form-control" id="apellidos" name="apellidos" placeholder="Ingrese apellidos" >
            </div>

            <!-- Email y Contraseña -->
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
              <label for="email" class="form-label fw-bold">Correo Electrónico</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="usuario@ejemplo.com" >
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12 position-relative">
              <label for="password" class="form-label fw-bold">Contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" placeholder="********" >
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <!-- Rol y Estado -->
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
              <label for="id_rol" class="form-label fw-bold">Rol</label>
              <select class="form-select" id="id_rol" name="id_rol" >
                <option value="">Seleccione rol</option>
                @foreach($roles as $rol)
                  <option value="{{ $rol->id }}">{{ strtoupper($rol->name) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
              <label for="estado" class="form-label fw-bold">Estado</label>
              <select class="form-select" id="estado" name="estado">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>

            <!-- Foto (solo imagen) -->
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
              <label for="imagen" class="form-label fw-bold">Foto de Perfil</label>
              <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
              <small class="text-muted">Solo se permite subir una imagen (jpg, png).</small>
            </div>

            <!-- Tipo Doc y Número -->
            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
              <label for="tipoDoc" class="form-label fw-bold">Tipo Documento</label>
              <select class="form-select" id="tipoDoc" name="tipoDoc" >
                <option value="">Seleccione</option>
                <option value="DNI">DNI</option>
                <option value="CARNET DE EXTRANJERIA">CARNET DE EXTRANJERIA</option>
              </select>
            </div>
            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
              <label for="numeroDoc" class="form-label fw-bold">Número Documento</label>
              <input type="text" class="form-control" id="numeroDoc" name="numeroDoc" placeholder="12345678" >
            </div>

            <!-- Celular y Fecha Nacimiento -->
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <label for="celular" class="form-label fw-bold">Celular</label>
              <input type="text" class="form-control" id="celular" name="celular" placeholder="999999999">
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <label for="fecha_nacimiento" class="form-label fw-bold">Fecha Nacimiento</label>
              <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <label for="nacionalidad" class="form-label fw-bold">Nacionalidad</label>
              <select class="form-select" id="nacionalidad" name="nacionalidad" required>
                  <option value="">Seleccione nacionalidad</option>
                  <option value="Afganistán">Afganistán</option>
                  <option value="Alemania">Alemania</option>
                  <option value="Argentina">Argentina</option>
                  <option value="Australia">Australia</option>
                  <option value="Brasil">Brasil</option>
                  <option value="Canadá">Canadá</option>
                  <option value="Chile">Chile</option>
                  <option value="China">China</option>
                  <option value="Colombia">Colombia</option>
                  <option value="España">España</option>
                  <option value="Estados Unidos">Estados Unidos</option>
                  <option value="Francia">Francia</option>
                  <option value="Italia">Italia</option>
                  <option value="Japón">Japón</option>
                  <option value="México">México</option>
                  <option value="Perú" selected>Perú</option>
                  <option value="Reino Unido">Reino Unido</option>
                  <option value="Rusia">Rusia</option>
                  <option value="Sudáfrica">Sudáfrica</option>
                  <option value="Venezuela">Venezuela</option>
                  <option value="Bolivia">Bolivia</option>
                  <option value="Ecuador">Ecuador</option>
              </select>
          </div>

            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 my-3">
                <h6 class="fw-bold border-bottom pt-2">
                    <i class="bi bi-geo-alt-fill"></i> DOMICILIO ACTUAL
                </h6>
            </div>

            <!-- Ubicación: Departamento / Provincia / Distrito -->
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <label for="departamento_id" class="form-label fw-bold">Departamento</label>
              <select class="form-select" id="departamento_id" name="departamento_id" >
                  <option value="">Seleccione</option>
                  @foreach($departamentos as $dep)
                      <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                  @endforeach
              </select>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <label for="provincia_id" class="form-label fw-bold">Provincia</label>
              <select class="form-select" id="provincia_id" name="provincia_id"  disabled>
                  <option value="">Seleccione</option>
              </select>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <label for="distrito_id" class="form-label fw-bold">Distrito</label>
              <select class="form-select" id="distrito_id" name="distrito_id"  disabled>
                  <option value="">Seleccione</option>
              </select>
            </div>

            <!-- Dirección y Código Postal -->
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 position-relative">
              <label for="direccion" class="form-label fw-bold">Calle</label>
              <div class="input-group">
                <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Av. Ejemplo">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalMapa">
                  <i class="bi bi-geo-alt-fill"></i>
                </button>
              </div>
            </div>
            <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
              <label for="cod_postal" class="form-label fw-bold">Nº Calle</label>
              <input type="text" class="form-control" id="num_calle" name="num_calle" placeholder="Nº Calle">
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <label for="cod_postal" class="form-label fw-bold">Puerta/Piso/Dpto/Otros</label>
              <input type="text" class="form-control" id="dir_otros" name="dir_otros" placeholder="Piso 1, Puerta 3, Dpto 101">
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <label for="cod_postal" class="form-label fw-bold">Código Postal</label>
              <input type="text" class="form-control" id="cod_postal" name="cod_postal" placeholder="1484">
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cancelar</button>
        <button type="submit" form="formNuevoUsuario" class="btn btn-primary"><i class="bi bi-save2-fill"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Mapa -->
<div class="modal fade" id="modalMapa" tabindex="-1" aria-labelledby="modalMapaLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="modalMapaLabel"><i class="bi bi-map-fill"></i> Seleccione Ubicación</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body position-relative">
        <input id="searchMap" class="form-control mb-2" type="text" placeholder="Buscar dirección...">
        <ul id="searchResults" class="list-group position-absolute" style="z-index:1000;width:95%;"></ul>
        <div id="map" style="height:500px;width:100%; margin-top:5px;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="guardarUbicacion">Usar Ubicación</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Roles -->
<div class="modal fade" id="modalRoles" tabindex="-1" aria-labelledby="modalRolesLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="modalRolesLabel">Administrar Roles</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        @permiso('AdministracionDelSistema/usuarios', 'crear')
                <button id="btnNuevoRol" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Crear Rol</button>
        @endpermiso
        <div class="table-responsive">
          <table id="tablaRoles" class="table table-striped table-bordered w-100" style="min-width: 600px;">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detalle Usuario -->
<div class="modal fade" id="modalDetalleUsuario" tabindex="-1" aria-labelledby="modalDetalleUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <!-- Header -->
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="modalDetalleUsuarioLabel"><i class="bi bi-person-lines-fill"></i> Detalle de Usuario</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <div class="row">
          <!-- Foto -->
          <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12 text-center">
            <img id="detalleImagen" src="{{ asset('img/user.png') }}" class="img-fluid rounded-circle shadow" style="max-width:180px;">
            <h6 class="mt-3 fw-bold" id="detalleNombre"></h6>
            <span class="badge bg-primary" id="detalleRol"></span>
          </div>

          <!-- Datos -->
          <div class="col-lg-8 col-md-12 col-sm-12 col-xs-12">
            <ul class="list-group list-group-flush text-start">
              <li class="list-group-item"><i class="bi bi-envelope"></i> <strong>Email:</strong> <span id="detalleEmail"></span></li>
              <li class="list-group-item"><i class="bi bi-card-text"></i> <strong>Documento:</strong> <span id="detalleDocumento"></span></li>
              <li class="list-group-item"><i class="bi bi-geo-alt"></i> <strong>Dirección:</strong> <span id="detalleDireccion"></span></li>
              <li class="list-group-item"><i class="bi bi-telephone"></i> <strong>Celular:</strong> <span id="detalleCelular"></span></li>
              <li class="list-group-item"><i class="bi bi-calendar"></i> <strong>Fecha Nacimiento:</strong> <span id="detalleFechaNac"></span></li>
              <li class="list-group-item"><i class="bi bi-flag"></i> <strong>Nacionalidad:</strong> <span id="detalleNacionalidad"></span></li>
            </ul>
          </div>
        </div>

        <!-- Auditorías -->
        @permiso('AdministracionDelSistema/usuarios', 'configurar')
            <div class="mt-4">
                <h6 class="fw-bold"><i class="bi bi-clock-history"></i> Auditorías</h6>
                <div class="table-responsive">
                    <table class="w-100 table table-sm table-striped mt-2" id="tablaAuditorias">
                        <thead>
                            <tr>
                                <th class="text-center">Acción</th>
                                <th class="text-center">Tabla</th>
                                <th class="text-center">Detalle</th>
                                <th class="text-center">Fecha</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        @endpermiso
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>