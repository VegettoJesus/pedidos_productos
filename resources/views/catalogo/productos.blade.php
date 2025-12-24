<style>
  /* Estilos visuales llamativos pero sobrios */
  .chip {
    display:inline-flex;align-items:center;gap:.5rem;padding:.35rem .6rem;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;margin:.25rem;
  }
  .chip .remove { cursor:pointer; font-weight:700; color:#ef4444; }
  .img-preview {
    width:120px;height:90px;object-fit:cover;border-radius:.5rem;border:1px solid #e5e7eb;
    position:relative;
  }
  .preview-card { position:relative; display:inline-block; margin:6px; }
  .preview-card .btn-remove {
    position:absolute; top:4px; right:4px; background:rgba(0,0,0,.6); color:white; border:none; width:26px; height:26px; border-radius:50%;
    display:flex;align-items:center;justify-content:center;
  }
  .feature-row { gap:8px; }
  .subcat-list { max-height:160px; overflow:auto; border:1px dashed #e6eef7; padding:8px; border-radius:6px; background:#fff; }
  .note-small { font-size:.85rem; color:#6b7280; }
  .card-section { border-radius:10px; box-shadow: 0 6px 18px rgba(15,23,42,0.06); padding:12px; background:#ffffff; margin-bottom:12px; }
  .nav-pills .nav-link {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 5px;
    font-weight: 500;
    color: #495057;
    background: #f8f9fa;
    border: 1px solid #e3e6ea;
    transition: all 0.2s;
  }

  .nav-pills .nav-link:hover {
    background: #e9ecef;
  }

  .nav-pills .nav-link.active {
    background: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
  }

  /* Inputs y etiquetas más limpios */
  .form-label {
    font-weight: 500;
    font-size: 0.9rem;
  }

  .tab-pane .mb-3, .tab-pane .row {
    margin-bottom: 1rem;
  }

  .input-group input, 
  .form-control,
  .form-select {
    border-radius: 6px;
  }

  /* Mejor diseño de chips de atributos/valores si aparecen luego */
  .chip {
    background-color: #f1f3f5;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 0.85rem;
  }
</style>
<div class="container-fluid px-2">
  <div class="card text-center text-white bg-dark">
    <div class="card-header">Lista de Productos</div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-lg-2 col-md-6 col-sm-12 d-flex align-items-end mt-2">
          @permiso('Catalogo/productos', 'crear')
            <button
              id="btnNuevo"
              class="btn btn-primary w-100 d-flex align-items-center justify-content-center"
              data-bs-toggle="modal"
              data-bs-target="#modalProducto"
            >
              <i class="bi bi-plus-circle me-2"></i> Nuevo
            </button>
          @endpermiso
        </div>
      </div>

      <div class="table-responsive mt-4">
        <table id="tablaProductos" class="w-100 table mt-2">
          <thead>
            <tr>
              <th></th> <!-- Miniatura -->
              <th class="text-center">ID</th>
              <th class="text-center">Nombre</th>
              <th class="text-center">Descripción</th>
              <th class="text-center">Precio</th>
              <th class="text-center">Inventario</th>
              <th class="text-center">Marca</th>
              <th class="text-center">Tipo</th>
              <th class="text-center">Subcategoría</th>
              <th class="text-center">Etiquetas</th>
              <th class="text-center">Estado / Fecha</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div class="card-footer text-muted"></div>
  </div>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form id="formProducto" enctype="multipart/form-data" class="p-0">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i> Nuevo Producto</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="idProducto" name="id">
          <!-- ENCABEZADO DE ESTADO Y VISIBILIDAD -->
          <div class="card-section mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
              
              <h5 class="fw-semibold mb-3 mb-md-0">
                <i class="bi bi-gear-wide-connected me-2"></i> Configuración del producto
              </h5>

              <div class="d-flex flex-column flex-sm-row gap-3">

                <!-- Estado -->
                <div>
                  <label class="form-label fw-semibold mb-1">
                    <i class="bi bi-flag me-1"></i> Estado
                  </label>
                  <select name="estado" class="form-select" required>
                    <option value="borrador" selected>Borrador</option>
                    <option value="publicado">Publicado</option>
                    <option value="oculto">Oculto</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <!-- LEFT COLUMN: basic info + miniatura + features -->
            <div class="col-lg-7">
              <div class="card-section">
                <div class="row g-2">
                  <div class="col-md-8">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Nombre del producto">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Marca</label>
                    <input type="text" name="marca" class="form-control" placeholder="Marca">
                  </div>

                  <div class="col-12 mt-2">
                    <label class="form-label fw-semibold">Descripción corta</label>
                    <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción breve"></textarea>
                  </div>
                </div>
              </div>

              <!-- Imagenes multi-upload -->
              <div class="card-section">
                <div class="row align-items-center">
                  <div class="col-md-8">
                    <label class="form-label fw-semibold">Imágenes del producto (máx 6)</label>
                    <input id="imagenesInput" type="file" name="imagenes[]" class="form-control" accept="image/*" multiple>
                    <div class="note-small mt-1">La primera imagen que quede será tomada como principal (miniatura aparte). Formatos: jpg, png, webp. Máx 4MB por archivo.</div>
                  </div>
                  <div class="col-md-4 text-end">
                    <label class="form-label fw-semibold">Miniatura</label>
                    <div class="d-flex justify-content-end">
                      <div>
                        <input id="miniaturaInput" type="file" name="imagen_miniatura" accept="image/*" class="form-control mb-2">
                        <div id="miniPreview" style="width:110px;height:80px;overflow:hidden;border-radius:8px;border:1px solid #e5e7eb">
                          <img src="" alt="" id="miniImg" style="width:100%;height:100%;object-fit:cover;display:none;">
                          <div id="miniPlaceholder" class="d-flex align-items-center justify-content-center text-muted" style="height:80px;">No seleccionada</div>
                        </div>
                        <button type="button" id="removeMiniBtn" class="btn btn-sm btn-outline-danger mt-2 d-none">Quitar miniatura</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div id="previewContainer" class="mt-3"></div>
              </div>

            </div>

            <!-- RIGHT COLUMN: categorias, etiquetas, dimensiones y meta -->
            <div class="col-lg-5">
              <div class="card-section">
                <label class="form-label fw-semibold">Categoría</label>
                <select id="categoriaSelect" class="form-select mb-2">
                  <option value="">-- Seleccione categoría --</option>
                </select>

                <label class="form-label fw-semibold mt-2">Subcategorías</label>
                <div id="subcategoriaList" class="subcat-list mb-2">
                  <!-- Radios generados por JS -->
                  <div class="text-center text-muted">Selecciona una categoría primero</div>
                </div>
              </div>

              <div class="card-section">
                <label class="form-label fw-semibold">Etiquetas</label>
                <div class="d-flex gap-2 mb-2">
                  <input id="tagInput" type="text" class="form-control" placeholder="Escribe una etiqueta">
                  <button type="button" id="btnAddTag" class="btn btn-primary">Añadir</button>
                </div>

                <div class="note-small mb-2">Selecciona de la lista o crea una etiqueta nueva. Las seleccionadas aparecerán como chips.</div>

                <div id="availableTags" class="mb-2" style="max-height:120px; overflow:auto;">
                  <!-- Lista de etiquetas disponibles (checkboxes) -->
                </div>

                <div class="mb-2">
                  <label class="form-label mb-1">Etiquetas seleccionadas</label>
                  <div id="selectedTags"></div>
                </div>
              </div>
            </div>
          </div>
          <hr class="my-4">

          <div class="mb-4 p-3 border rounded bg-light">
            <label class="form-label fw-semibold mb-2">
              <i class="bi bi-ui-checks-grid me-1"></i> Tipo de producto
            </label>
            <select name="tipo_producto" id="tipoProductoSelect" class="form-select" required>
              <option value="simple">Simple</option>
              <option value="variable">Variable</option>
              <option value="agrupado">Agrupado</option>
            </select>
          </div>

          <div class="row">
            <div class="col-md-3">
              <div class="nav flex-column nav-pills" id="v-tabs" role="tablist" aria-orientation="vertical">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-general" type="button">General</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-inventario" type="button">Inventario</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-envio" type="button">Envío</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-relacionados" type="button">Productos relacionados</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-atributos" type="button">Atributos</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-avanzado" type="button">Avanzado</button>
              </div>
            </div>

            <div class="col-md-9">
              <div class="tab-content">
                <div class="tab-pane fade show active p-3" id="tab-general">
                  <div class="mb-3">
                    <label class="form-label">Precio normal</label>
                    <input type="number" step="0.01" name="precio_regular" class="form-control">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">
                      Precio rebajado
                      <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" title="Se mostrará si tiene fecha activa"></i>
                    </label>
                    <input type="number" step="0.01" name="precio_rebajado" class="form-control">
                  </div>
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="checkRebaja">
                    <label class="form-check-label" for="checkRebaja" data-bs-toggle="tooltip" title="Habilita fechas para activar rebaja automáticamente">
                      Programar rebaja
                    </label>
                  </div>
                  <div id="rebajaFechas" class="row g-2 d-none">
                    <div class="col-md-6">
                      <label class="form-label">Inicio</label>
                      <input type="date" name="fecha_inicio_rebaja" class="form-control">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Fin</label>
                      <input type="date" name="fecha_fin_rebaja" class="form-control">
                    </div>
                  </div>
                </div>

                <div class="tab-pane fade p-3" id="tab-inventario">
                  <div class="mb-3">
                    <label class="form-label">
                      SKU
                      <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" title="Código único del producto"></i>
                    </label>
                    <input type="text" name="sku" class="form-control">
                  </div>

                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="checkGestion" name="gestion_inventario">
                    <label class="form-check-label" for="checkGestion" data-bs-toggle="tooltip" title="Activa control de stock por unidad">
                      Gestionar inventario
                    </label>
                  </div>

                  <div id="invExtra" class="d-none">
                    <div class="mb-3">
                      <label class="form-label">Cantidad en inventario</label>
                      <input type="number" name="stock" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label" data-bs-toggle="tooltip" title="Permite realizar compras sin stock disponible">
                        ¿Permitir reservas?
                      </label>
                      <div class="form-check">
                        <input type="radio" class="form-check-input" name="backorders" value="no" checked>
                        <label class="form-check-label">No permitir</label>
                      </div>
                      <div class="form-check">
                        <input type="radio" class="form-check-input" name="backorders" value="sí">
                        <label class="form-check-label">Permitir</label>
                      </div>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">
                      Estado del inventario
                      <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" title="Define visibilidad del producto según disponibilidad"></i>
                    </label>
                    <div class="form-check">
                      <input type="radio" class="form-check-input" name="estado_inv" value="existe" checked>
                      <label class="form-check-label">Hay existencias</label>
                    </div>
                    <div class="form-check">
                      <input type="radio" class="form-check-input" name="estado_inv" value="agotado">
                      <label class="form-check-label">Agotado</label>
                    </div>
                    <div class="form-check">
                      <input type="radio" class="form-check-input" name="estado_inv" value="reservar">
                      <label class="form-check-label">Se puede reservar</label>
                    </div>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="vendido_individualmente">
                    <label class="form-check-label" data-bs-toggle="tooltip" title="Solo permite 1 unidad por carrito">
                      Limitar compras a 1 artículo por pedido
                    </label>
                  </div>
                </div>

                <div class="tab-pane fade p-3" id="tab-envio">
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label">
                        Peso
                        <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" title="Peso total del producto"></i>
                      </label>
                      <div class="input-group">
                        <input type="number" step="0.01" name="peso" class="form-control">
                        <select name="peso_unidad" class="form-select" style="max-width:90px;">
                          <option value="kg">kg</option>
                          <option value="g">g</option>
                          <option value="mg">mg</option>
                          <option value="lb">lb</option>
                          <option value="oz">Oz</option>
                          <option value="ton">Ton</option>
                        </select>
                      </div>
                    </div>

                    <div class="col-12">
                      <label class="form-label" data-bs-toggle="tooltip" title="Largo × Ancho × Alto">
                        Dimensiones (cm)
                      </label>
                      <div class="input-group">
                        <input name="longitud" class="form-control" placeholder="Largo">
                        <input name="anchura" class="form-control" placeholder="Ancho">
                        <input name="altura" class="form-control" placeholder="Alto">
                      </div>
                    </div>
                  </div>
                </div>

                <div class="tab-pane fade p-3" id="tab-relacionados">
                  <div class="mb-3">
                    <label class="form-label">Ventas dirigidas (Upsells)</label>
                    <input type="text" id="inputUpsells" class="form-control" placeholder="Buscar producto...">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Ventas cruzadas (Cross-sells)</label>
                    <input type="text" id="inputCrosssells" class="form-control" placeholder="Buscar producto...">
                  </div>
                </div>

                <div class="tab-pane fade p-3" id="tab-atributos"></div>

                <div class="tab-pane fade p-3" id="tab-avanzado">
                  <div class="mb-3">
                    <label class="form-label" data-bs-toggle="tooltip" title="Visible en la página del pedido para el cliente">
                      Nota de compra
                    </label>
                    <textarea name="nota_interna" class="form-control" rows="3"></textarea>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" name="permite_valoraciones" id="checkValoraciones" class="form-check-input" checked>
                    <label class="form-check-label" for="checkValoraciones" data-bs-toggle="tooltip" title="Permite reseñas en el producto">
                      Activar valoraciones
                    </label>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar producto</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal: Crear Atributo -->
<div class="modal fade" id="modalCrearAtributo" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog">
    <form id="formCrearAtributo" class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo atributo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" required>
        <label class="form-label mt-2">Slug (opcional)</label>
        <input name="slug" class="form-control" placeholder="si no pones se autogenera">
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Crear Valor (Term) -->
<div class="modal fade" id="modalCrearValor" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog">
    <form id="formCrearValor" class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo valor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="atributo_id" id="valor_atributo_id">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" required>
        <label class="form-label mt-2">Slug (opcional)</label>
        <input name="slug" class="form-control" placeholder="si no pones se autogenera">
        <label class="form-label mt-2">Descripción</label>
        <textarea name="descripcion" class="form-control" rows="2"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Guardar valor</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="modalProductoSimple" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form id="formProductoSimple" enctype="multipart/form-data" class="p-0" onsubmit="return handleGuardarProductoSimple(event)">
        
        <!-- HEADER -->
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">
            <i class="bi bi-pencil-square me-2"></i> Editar Producto Simple
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body">
          <input type="hidden" name="id" id="simple_id">

          <!-- CONFIGURACIÓN GENERAL -->
          <div class="card-section mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
              <h5 class="fw-semibold mb-3 mb-md-0">
                <i class="bi bi-gear me-2"></i> Configuración del producto
              </h5>

              <div class="d-flex flex-column flex-sm-row gap-3">
                <div>
                  <label class="form-label fw-semibold mb-1">
                    <i class="bi bi-flag me-1"></i> Estado
                  </label>
                  <select name="estado" id="simple_estado" class="form-select" required>
                    <option value="borrador">Borrador</option>
                    <option value="publicado">Publicado</option>
                    <option value="oculto">Oculto</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- DATOS PRINCIPALES -->
          <div class="row">
            <div class="col-lg-7">
              <div class="card-section">
                <div class="row g-2">
                  <div class="col-md-8">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="nombre" id="simple_nombre" class="form-control" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Marca</label>
                    <input type="text" name="marca" id="simple_marca" class="form-control">
                  </div>
                  <div class="col-12 mt-2">
                    <label class="form-label fw-semibold">Descripción corta</label>
                    <textarea name="descripcion" id="simple_descripcion" class="form-control" rows="3"></textarea>
                  </div>
                </div>
              </div>

              <!-- Imágenes -->
              <div class="card-section mt-3">
                <div class="row align-items-center">
                  <div class="col-md-8">
                    <label class="form-label fw-semibold">Imágenes (máx 6)</label>
                    <input id="simple_imagenes" type="file" name="imagenes[]" class="form-control" accept="image/*" multiple>
                    <div class="note-small mt-1">La primera imagen será principal.</div>
                    <div id="simple_previewContainer" class="mt-3"></div>
                  </div>
                  <!-- En la sección de imágenes, actualiza la parte de miniatura: -->
                  <div class="col-md-4 text-end">
                    <label class="form-label fw-semibold">Miniatura</label>
                    <div class="position-relative d-inline-block">
                      <input id="simple_miniatura" type="file" name="imagen_miniatura" 
                            class="form-control mb-2 visually-hidden" accept="image/*">
                      
                      <div id="simple_miniPreview" 
                          class="border rounded cursor-pointer" 
                          style="width:120px;height:100px;overflow:hidden;">
                        <img src="" id="simple_miniImg" 
                            style="width:100%;height:100%;object-fit:cover;display:none;">
                        <div id="simple_miniPlaceholder" 
                            class="d-flex flex-column align-items-center justify-content-center text-muted small" 
                            style="height:100%;">
                          <i class="bi bi-image fs-4 mb-1"></i>
                          <span>Click para subir</span>
                        </div>
                      </div>
                      
                      <button type="button" id="simple_removeMini" 
                              class="btn btn-sm btn-outline-danger mt-2 d-none">
                        <i class="bi bi-trash"></i> Quitar
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- DERECHA: Categoría + etiquetas -->
            <div class="col-lg-5">
              <div class="card-section">
                <label class="form-label fw-semibold">Categoría</label>
                <select id="simple_categoria" class="form-select mb-2"></select>

                <label class="form-label fw-semibold mt-2">Subcategorías</label>
                <div id="simple_subcategorias" class="subcat-list mb-2 text-muted">
                  Selecciona una categoría primero
                </div>
              </div>

              <div class="card-section">
                <label class="form-label fw-semibold">Etiquetas</label>
                <div class="d-flex gap-2 mb-2">
                  <input id="simple_tagInput" type="text" class="form-control" placeholder="Escribe una etiqueta">
                  <button type="button" id="simple_btnAddTag" class="btn btn-primary">Añadir</button>
                </div>

                <div class="note-small mb-2">
                  Selecciona de la lista o crea una etiqueta nueva. Las seleccionadas aparecerán como chips.
                </div>

                <div id="simple_availableTags" class="mb-2" style="max-height:120px; overflow:auto;">
                </div>

                <div class="mb-2">
                  <label class="form-label mb-1">Etiquetas seleccionadas</label>
                  <div id="simple_selectedTags" class="d-flex flex-wrap gap-2"></div>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- TABS -->
          <div class="row">
            <div class="col-md-3">
              <div class="nav flex-column nav-pills" role="tablist">
                <button type="button" class="nav-link active" data-bs-toggle="pill" data-bs-target="#simple-general">General</button>
                <button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#simple-inventario">Inventario</button>
                <button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#simple-envio">Envío</button>
                <button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#simple-relacionados">Relacionados</button>
                <button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#simple-atributos">Atributos</button>
                <button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#simple-avanzado">Avanzado</button>
              </div>
            </div>

            <div class="col-md-9">
              <div class="tab-content">
                
                <!-- GENERAL -->
                <div class="tab-pane fade show active p-3" id="simple-general">
                  <div class="mb-3">
                    <label class="form-label">Precio normal</label>
                    <input type="number" step="0.01" name="precio_regular" id="simple_precio_regular" class="form-control">
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Precio rebajado</label>
                    <input type="number" step="0.01" name="precio_rebajado" id="simple_precio_rebajado" class="form-control">
                  </div>
                  
                  <!-- Programar rebaja -->
                  <div class="mb-3">
                    <div class="form-check">
                      <input type="checkbox" id="simple_programar_rebaja" class="form-check-input">
                      <label class="form-check-label fw-semibold">Programar rebaja</label>
                    </div>
                  </div>
                  
                  <div id="simple_fechas_rebaja" class="row g-2 mt-2" style="display: none;">
                    <div class="col-md-6">
                      <label class="form-label small">Fecha inicio rebaja</label>
                      <input type="date" id="simple_fecha_inicio_rebaja" name="fecha_inicio_rebaja" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small">Fecha fin rebaja</label>
                      <input type="date" id="simple_fecha_fin_rebaja" name="fecha_fin_rebaja" class="form-control form-control-sm">
                    </div>
                  </div>
                </div>

                <!-- INVENTARIO -->
                <div class="tab-pane fade p-3" id="simple-inventario">
                  <!-- SKU -->
                  <div class="mb-4">
                    <label class="form-label fw-semibold">SKU</label>
                    <input type="text" name="sku" id="simple_sku" class="form-control" placeholder="Ej: PROD-001">
                    <div class="form-text small">Identificador único del producto</div>
                  </div>

                  <!-- Gestión de inventario -->
                  <div class="mb-4">
                    <div class="form-check mb-3">
                      <input type="checkbox" id="simple_gestion_inventario" name="gestion_inventario" class="form-check-input">
                      <label class="form-check-label fw-semibold" for="simple_gestion_inventario">
                        Gestionar inventario
                      </label>
                      <div class="form-text small">Activar para controlar el stock del producto</div>
                    </div>

                    <!-- Contenedor que se mostrará solo si está activado el check -->
                    <div id="simple_inventario_detalles" style="display: none;">
                      <!-- Cantidad en inventario -->
                      <div class="mb-3">
                        <label class="form-label">Cantidad en inventario</label>
                        <input type="number" name="stock" id="simple_stock" class="form-control" min="0" value="0">
                        <div class="form-text small">Cantidad disponible actualmente</div>
                      </div>

                      <!-- Permitir reservas -->
                      <div class="mb-4">
                        <label class="form-label d-block mb-2">¿Permitir reservas?</label>
                        <div class="d-flex gap-4">
                          <div class="form-check">
                            <input type="radio" id="simple_backorders_no" name="backorders" value="0" class="form-check-input">
                            <label class="form-check-label" for="simple_backorders_no">No permitir</label>
                          </div>
                          <div class="form-check">
                            <input type="radio" id="simple_backorders_si" name="backorders" value="1" class="form-check-input">
                            <label class="form-check-label" for="simple_backorders_si">Permitir</label>
                          </div>
                        </div>
                        <div class="form-text small">Permitir que los clientes realicen pedidos aunque no haya stock</div>
                      </div>
                    </div>
                  </div>
                  <div class="mb-4">
                    <label class="form-label d-block mb-2 fw-semibold">Estado de inventario</label>
                    <div class="d-flex flex-column gap-2">
                      <div class="form-check">
                        <input type="radio" id="simple_estado_existe" name="estado_inventario" value="existe" class="form-check-input">
                        <label class="form-check-label" for="simple_estado_existe">Hay existencias</label>
                      </div>
                      <div class="form-check">
                        <input type="radio" id="simple_estado_agotado" name="estado_inventario" value="agotado" class="form-check-input">
                        <label class="form-check-label" for="simple_estado_agotado">Agotado</label>
                      </div>
                      <div class="form-check">
                        <input type="radio" id="simple_estado_reservar" name="estado_inventario" value="reservar" class="form-check-input">
                        <label class="form-check-label" for="simple_estado_reservar">Se puede reservar</label>
                      </div>
                    </div>
                  </div>
                  <!-- Limitar compras -->
                  <div class="mb-3">
                    <div class="form-check">
                      <input type="checkbox" id="simple_vendido_individualmente" name="vendido_individualmente" class="form-check-input">
                      <label class="form-check-label fw-semibold" for="simple_vendido_individualmente">
                        Limitar compras a 1 artículo por pedido
                      </label>
                      <div class="form-text small">El cliente solo podrá agregar una unidad de este producto por pedido</div>
                    </div>
                  </div>
                </div>
                <!-- ENVÍO -->
                <div class="tab-pane fade p-3" id="simple-envio">
                  <div class="mb-3">
                    <label class="form-label">Peso</label>
                    <input type="number" step="0.01" name="peso" id="simple_peso" class="form-control">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Dimensiones (cm)</label>
                    <div class="input-group">
                      <input type="number" step="0.01" name="longitud" id="simple_longitud" class="form-control" placeholder="Largo">
                      <input type="number" step="0.01" name="anchura" id="simple_anchura" class="form-control" placeholder="Ancho">
                      <input type="number" step="0.01" name="altura" id="simple_altura" class="form-control" placeholder="Alto">
                    </div>
                  </div>
                </div>

                <!-- RELACIONADOS -->
                <div class="tab-pane fade p-3" id="simple-relacionados">
                  <div class="mb-4">
                    <label class="form-label fw-semibold">
                      <i class="bi bi-arrow-up-circle me-1"></i> Upsells
                    </label>
                    <div class="note-small mb-2">
                      Productos que podrían gustar al cliente en lugar del producto actual.
                    </div>
                    <input type="text" 
                          id="simple_upsells" 
                          class="form-control" 
                          placeholder="Buscar producto por nombre o SKU...">
                    <div class="mt-2">
                      <small class="text-muted">Escribe al menos 2 caracteres para buscar</small>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label class="form-label fw-semibold">
                      <i class="bi bi-arrow-left-right me-1"></i> Cross-sells
                    </label>
                    <div class="note-small mb-2">
                      Productos complementarios que se pueden comprar junto con el producto actual.
                    </div>
                    <input type="text" 
                          id="simple_crosssells" 
                          class="form-control" 
                          placeholder="Buscar producto por nombre o SKU...">
                    <div class="mt-2">
                      <small class="text-muted">Escribe al menos 2 caracteres para buscar</small>
                    </div>
                  </div>
                </div>

                <!-- ATRIBUTOS -->
                <div class="tab-pane fade p-3" id="simple-atributos">
                  <div id="simple_atributosContainer" class="mb-3"></div>
                </div>

                <!-- AVANZADO -->
                <div class="tab-pane fade p-3" id="simple-avanzado">
                  <div class="mb-3">
                    <label class="form-label">Nota interna</label>
                    <textarea name="nota_interna" id="simple_nota_interna" class="form-control" rows="3"></textarea>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" name="permite_valoraciones" id="simple_valoraciones" class="form-check-input">
                    <label class="form-check-label">Permitir valoraciones</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button type="submit" id="btnGuardarSimple" class="btn btn-success">
            <i class="bi bi-save me-1"></i> Guardar cambios
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>

      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalProductoVariable" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <form id="formProductoVariable" onsubmit="return handleGuardarProductoVariable(event)">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar Producto Variable</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="variable_variaciones_container">
          <!-- Aquí se renderizan dinámicamente las variaciones -->
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="modalProductoAgrupado" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="formProductoAgrupado" onsubmit="return handleGuardarProductoAgrupado(event)">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar Producto Agrupado</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <h6>Productos Hijos</h6>
          <ul id="agrupado_hijos"></ul>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
  window._CATEGORIAS = @json($categorias);
  window._ETIQUETAS = @json($etiquetas);
  window._ATRIBUTOS = @json($atributos);
</script>