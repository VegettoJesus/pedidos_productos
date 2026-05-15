function renderEtiquetasSimpleUI(producto) {
  const availableDiv = $('#simple_availableTags');
  const selectedDiv  = $('#simple_selectedTags');

  availableDiv.html('');
  selectedDiv.html('');

  const seleccionadas = (producto.etiquetas || []).map(e => e.id);

  // Render disponibles
  _ETIQUETAS.forEach(tag => {
    const checked = seleccionadas.includes(tag.id) ? 'checked' : '';
    availableDiv.append(`
      <div class="form-check">
        <input class="form-check-input tag-available simple_tagCheck" type="checkbox" value="${tag.id}" id="tag_${tag.id}" ${checked}>
        <label class="form-check-label" for="tag_${tag.id}">
          <span class="text-black">${tag.nombre}</span>
        </label>
      </div>
    `);
  });

  // Render chips seleccionados
  producto.etiquetas?.forEach(t => {
    selectedDiv.append(`
      <span class="badge d-inline-flex align-items-center text-black chip">
        ${t.slug}
        <button type="button" class="btn-close btn-close-white btn-sm ms-2 remove-tag" data-id="${t.id}"></button>
      </span>
    `);
  });

  // Evento check
  $('.simple_tagCheck').off('change').on('change', function () {
    const tagId = parseInt(this.value);
    const tag = _ETIQUETAS.find(e => e.id === tagId);
    if (this.checked) {
      if (!producto.etiquetas.some(e => e.id === tagId)) {
        producto.etiquetas.push(tag);
      }
    } else {
      producto.etiquetas = producto.etiquetas.filter(e => e.id !== tagId);
    }
    renderEtiquetasSimpleUI(producto);
  });

  // Evento eliminar desde chip
  selectedDiv.find('.remove-tag').off('click').on('click', function () {
    const tagId = parseInt($(this).data('id'));
    producto.etiquetas = producto.etiquetas.filter(e => e.id !== tagId);
    renderEtiquetasSimpleUI(producto);
  });
}

// Crear nueva etiqueta en frontend
$('#simple_btnAddTag').off('click').on('click', async function () {
  const val = $('#simple_tagInput').val().trim();
  if (!val) return;

  try {
    const { data } = await axios.post('productos', {
      opcion: 'CrearEtiqueta',
      nombre: val
    });

    if (data.respuesta === 'ok') {
      const nueva = data.etiqueta;

      _ETIQUETAS.push(nueva);

      if (!editState.productoActual.etiquetas) {
        editState.productoActual.etiquetas = [];
      }
      editState.productoActual.etiquetas.push(nueva);

      $('#simple_tagInput').val('');

      renderEtiquetasSimpleUI(editState.productoActual);

      Swal.fire('Etiqueta creada', `"${nueva.nombre}" fue agregada correctamente`, 'success');
    } else {
      Swal.fire('Error', data.mensaje || 'No se pudo crear la etiqueta', 'error');
    }
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'Error de servidor al crear la etiqueta', 'error');
  }
});

function renderAtributosSimpleUI() {
  const container = document.getElementById('simple_atributosContainer');
  if (!container) return;

  container.innerHTML = '';

  // --- Selector de atributos existentes ---
  let selectWrap = document.createElement('div');
  selectWrap.className = 'atributos-select-wrap mb-3';
  selectWrap.innerHTML = `
    <label class="form-label">Seleccionar atributo existente</label>
    <select class="form-select mb-2" id="simple_select_atributo">
      <option value="">-- Seleccione --</option>
      ${_ATRIBUTOS.map(a => `<option value="${a.id}">${escapeHtml(a.nombre)}</option>`).join('')}
    </select>
    <div>
      <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="abrirModalCrearAtributo()">Crear nuevo atributo</button>
    </div>
    <div id="simple_atributosBlocks" class="mt-3"></div>
  `;
  container.appendChild(selectWrap);

  // --- Listener para añadir atributos existentes ---
  selectWrap.querySelector('#simple_select_atributo').addEventListener('change', function() {
    const id = this.value;
    if (!id) return;
    const atributo = _ATRIBUTOS.find(a => String(a.id) === String(id));
    if (atributo) addAtributoBlockSimple(atributo);
    this.value = '';
  });

  renderAtributoBlocksSimple();
}
async function abrirModalCrearAtributo() {
  // Ocultar temporalmente el modal de Bootstrap
  const modalSimple = bootstrap.Modal.getInstance(document.getElementById('modalProductoSimple'));
  if (modalSimple) {
    modalSimple.hide();
  }

  const { value: formValues } = await Swal.fire({
    title: 'Nuevo atributo',
    html: `
      <div id="swalAtributoContainer" style="max-width: 500px; margin: 0 auto;">
        <label class="form-label mb-2">Nombre del atributo</label>
        <input id="swal_attr_nombre" class="form-control mb-3" placeholder="Ej: Color, Tamaño, Material" autofocus>
        
        <label class="form-label mb-2">Slug (opcional)</label>
        <input id="swal_attr_slug" class="form-control" placeholder="Ej: color, tamano, material">
        
        <div class="mt-3 small text-muted">
          <i class="bi bi-info-circle"></i> El slug se generará automáticamente si lo dejas vacío
        </div>
      </div>
    `,
    width: 600,
    showCancelButton: true,
    confirmButtonText: 'Guardar atributo',
    cancelButtonText: 'Cancelar',
    showLoaderOnConfirm: true,
    allowOutsideClick: () => !Swal.isLoading(),
    preConfirm: async () => {
      const nombre = document.getElementById('swal_attr_nombre').value.trim();
      const slug = document.getElementById('swal_attr_slug').value.trim();
      
      if (!nombre) {
        Swal.showValidationMessage('El nombre del atributo es requerido');
        return false;
      }
      
      return { nombre, slug };
    }
  });

  // Mostrar nuevamente el modal de Bootstrap
  if (modalSimple) {
    setTimeout(() => {
      modalSimple.show();
    }, 100);
  }

  if (!formValues) return;

  try {
    const { data } = await axios.post('productos', {
      opcion: 'CrearAtributo',
      nombre: formValues.nombre,
      slug: formValues.slug
    });

    if (data.respuesta === 'ok') {
      const nuevoAtributo = data.atributo;
      
      // 1. Actualizar la lista global de atributos
      _ATRIBUTOS.push(nuevoAtributo);
      
      // 2. AGREGAR AUTOMÁTICAMENTE EL ATRIBUTO AL PRODUCTO ACTUAL
      if (!editState.productoActual.atributos) {
        editState.productoActual.atributos = [];
      }
      
      // Verificar si ya existe
      const yaExiste = editState.productoActual.atributos.some(a => a.id === nuevoAtributo.id);
      
      if (!yaExiste) {
        // Agregar el nuevo atributo al producto con array de términos vacío
        editState.productoActual.atributos.push({
          id: nuevoAtributo.id,
          nombre: nuevoAtributo.nombre,
          slug: nuevoAtributo.slug,
          terminos: [] // Inicialmente vacío
        });
      }
      
      // 3. Re-renderizar la UI de atributos
      renderAtributosSimpleUI();
      
      // 4. Seleccionar automáticamente el atributo recién creado en el dropdown
      setTimeout(() => {
        $('#simple_select_atributo').val(nuevoAtributo.id);
      }, 100);
      
      // 5. Mostrar mensaje de éxito
      Swal.fire({
        title: '¡Atributo creado y agregado!',
        text: `"${nuevoAtributo.nombre}" ha sido agregado al producto`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      Swal.fire('Error', data.mensaje, 'error');
    }
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'No se pudo crear el atributo', 'error');
  }
}

async function abrirModalCrearValor(atributoId) {
  // Obtener información del atributo para mostrar en el título
  const atributo = _ATRIBUTOS.find(a => a.id == atributoId);
  if (!atributo) {
    Swal.fire('Error', 'Atributo no encontrado', 'error');
    return;
  }

  // Verificar si el atributo está agregado al producto
  let atributoEnProducto = editState.productoActual.atributos?.find(a => a.id == atributoId);
  
  // Si el atributo no está en el producto, agregarlo primero
  if (!atributoEnProducto) {
    if (!editState.productoActual.atributos) {
      editState.productoActual.atributos = [];
    }
    
    editState.productoActual.atributos.push({
      id: atributo.id,
      nombre: atributo.nombre,
      slug: atributo.slug,
      terminos: []
    });
    
    atributoEnProducto = editState.productoActual.atributos.find(a => a.id == atributoId);
  }

  // Ocultar temporalmente el modal de Bootstrap
  const modalSimple = bootstrap.Modal.getInstance(document.getElementById('modalProductoSimple'));
  if (modalSimple) {
    modalSimple.hide();
  }

  const { value: formValues } = await Swal.fire({
    title: `Nuevo valor para "${atributo.nombre}"`,
    html: `
      <div id="swalValorContainer" style="max-width: 500px; margin: 0 auto;">
        <input type="hidden" id="swal_val_attrId" value="${atributoId}">
        
        <label class="form-label mb-2">Nombre del valor</label>
        <input id="swal_val_nombre" class="form-control mb-3" placeholder="Ej: Rojo, Grande, Algodón" autofocus>
        
        <label class="form-label mb-2">Slug (opcional)</label>
        <input id="swal_val_slug" class="form-control mb-3" placeholder="Ej: rojo, grande, algodon">
        
        <label class="form-label mb-2">Descripción (opcional)</label>
        <textarea id="swal_val_desc" class="form-control" rows="2" placeholder="Descripción adicional..."></textarea>
        
        <div class="mt-3 small text-muted">
          <i class="bi bi-info-circle"></i> El slug se generará automáticamente si lo dejas vacío
        </div>
      </div>
    `,
    width: 600,
    showCancelButton: true,
    confirmButtonText: 'Guardar valor',
    cancelButtonText: 'Cancelar',
    showLoaderOnConfirm: true,
    allowOutsideClick: () => !Swal.isLoading(),
    preConfirm: async () => {
      const nombre = document.getElementById('swal_val_nombre').value.trim();
      
      if (!nombre) {
        Swal.showValidationMessage('El nombre del valor es requerido');
        return false;
      }
      
      const slug = document.getElementById('swal_val_slug').value.trim();
      const descripcion = document.getElementById('swal_val_desc').value.trim();
      
      return {
        atributo_id: document.getElementById('swal_val_attrId').value,
        nombre,
        slug,
        descripcion
      };
    }
  });

  // Mostrar nuevamente el modal de Bootstrap
  if (modalSimple) {
    setTimeout(() => {
      modalSimple.show();
    }, 100);
  }

  if (!formValues) return;

  try {
    const { data } = await axios.post('productos', {
      opcion: 'CrearValorAtributo',
      ...formValues
    });

    if (data.respuesta === 'ok') {
      const nuevoValor = data.termino;
      
      // 1. Buscar atributo en la lista global y añadir valor nuevo
      const attrGlobal = _ATRIBUTOS.find(a => a.id == formValues.atributo_id);
      if (attrGlobal) {
        if (!attrGlobal.terminos) attrGlobal.terminos = [];
        attrGlobal.terminos.push(nuevoValor);
      }
      
      // 2. SELECCIONAR AUTOMÁTICAMENTE EL NUEVO VALOR EN EL ATRIBUTO DEL PRODUCTO
      if (atributoEnProducto) {
        // Verificar si el valor ya está seleccionado
        const yaSeleccionado = atributoEnProducto.terminos?.some(t => t.id === nuevoValor.id);
        
        if (!yaSeleccionado) {
          // Agregar el nuevo valor a los términos seleccionados
          if (!atributoEnProducto.terminos) {
            atributoEnProducto.terminos = [];
          }
          
          atributoEnProducto.terminos.push({
            id: nuevoValor.id,
            nombre: nuevoValor.nombre,
            slug: nuevoValor.slug
          });
        }
      }
      
      // 3. Re-renderizar los bloques de atributos
      renderAtributoBlocksSimple();
      
      // 4. Mostrar mensaje de éxito
      Swal.fire({
        title: '¡Valor creado y seleccionado!',
        text: `"${nuevoValor.nombre}" ha sido agregado y seleccionado en "${atributo.nombre}"`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      Swal.fire('Error', data.mensaje, 'error');
    }
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'No se pudo crear el valor', 'error');
  }
}

function addAtributoBlockSimple(atributoObj) {
  if (!editState.productoActual.atributos) {
    editState.productoActual.atributos = [];
  }

  if (editState.productoActual.atributos.some(x => x.id === atributoObj.id)) {
    showAlert('info', 'Ya agregado', 'Ese atributo ya fue agregado al producto.');
    return;
  }

  editState.productoActual.atributos.push({
    id: atributoObj.id,
    nombre: atributoObj.nombre,
    slug: atributoObj.slug,
    terminos: []
  });

  renderAtributoBlocksSimple();
}

function renderAtributoBlocksSimple() {
  const blocks = document.getElementById('simple_atributosBlocks');
  if (!blocks) return;

  blocks.innerHTML = '';

  (editState.productoActual.atributos || []).forEach(attr => {
    const block = createAtributoBlockSimple(attr);
    blocks.appendChild(block);
  });
}

function createAtributoBlockSimple(attr) {
  const attrFull = _ATRIBUTOS.find(x => String(x.id) === String(attr.id));

  const wrapper = document.createElement('div');
  wrapper.className = 'atributo-block border rounded p-3 mt-3 mb-3 shadow-sm bg-white';
  wrapper.dataset.atributoId = attr.id;
  const isVisible = attr.pivot?.visible === 1 || attr.pivot?.visible === true;
  wrapper.innerHTML = `
    <div class="d-flex justify-content-between align-items-start mb-2">
      <div>
        <h6 class="mb-1 fw-bold">${escapeHtml(attr.nombre)}</h6>
        <span class="badge bg-light text-secondary border small">${escapeHtml(attr.slug)}</span>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input chk-visible" type="checkbox" ${isVisible ? 'checked' : ''}>
        <label class="form-check-label small">Visible</label>
      </div>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-sm btn-outline-primary btnCrearValor2 me-2">
        <i class="bi bi-plus-circle"></i> Crear valor
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary btnSelectAll">
        <i class="bi bi-check2-square"></i> Seleccionar todos
      </button>
      <button type="button" class="btn btn-sm btn-outline-danger ms-2 btnEliminarAtributo">
        <i class="bi bi-trash"></i>
      </button>
    </div>

    <div class="valores-area">
      <div class="small fw-semibold mb-2">Valores seleccionados:</div>
      <div class="valores-chips d-flex flex-wrap gap-2 mb-3"></div>

      <div class="small text-muted">Valores disponibles:</div>
      <div class="valores-available d-flex flex-wrap gap-2 mt-2"></div>
    </div>
  `;

  // --- Render valores disponibles ---
  const availDiv = wrapper.querySelector('.valores-available');
  if (availDiv && attrFull?.terminos) {
    attrFull.terminos.forEach(term => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-sm btn-outline-info btn-termino';
      btn.dataset.termId = term.id;
      btn.textContent = term.nombre;
      btn.addEventListener('click', () => {
        if (!attr.terminos.some(v => v.id === term.id)) {
          attr.terminos.push({
            id: term.id,
            nombre: term.nombre,
            slug: term.slug
          });
          renderAtributoBlocksSimple();
        }
      });
      availDiv.appendChild(btn);
    });
  }

  // --- Render chips (valores seleccionados) ---
  const chipsDiv = wrapper.querySelector('.valores-chips');
  (attr.terminos || []).forEach(v => {
    const chip = document.createElement('span');
    chip.className = 'chip d-inline-flex align-items-center px-2 py-1 rounded bg-light border';
    chip.innerHTML = `
      ${escapeHtml(v.nombre)}
      <button type="button" class="btn-close btn-sm ms-2 remove-valor"></button>
    `;
    chip.querySelector('.remove-valor').addEventListener('click', () => {
      attr.terminos = attr.terminos.filter(x => x.id !== v.id);
      renderAtributoBlocksSimple();
    });
    chipsDiv.appendChild(chip);
  });

  // --- Botones ---
  wrapper.querySelector('.btnEliminarAtributo').addEventListener('click', () => {
    editState.productoActual.atributos = editState.productoActual.atributos.filter(a => a.id !== attr.id);
    renderAtributoBlocksSimple();
  });

  wrapper.querySelector('.btnSelectAll').addEventListener('click', () => {
    attr.terminos = (attrFull?.terminos || []).map(t => ({
      id: t.id, nombre: t.nombre, slug: t.slug
    }));
    renderAtributoBlocksSimple();
  });

  wrapper.querySelector('.btnCrearValor2').addEventListener('click', () => {
    abrirModalCrearValor(attr.id);
  });

  return wrapper;
}

$('#modalProductoSimple').on('hidden.bs.modal', function () {
    if (typeof tinymce !== 'undefined' && tinymce.get('simple_descripcion_larga')) {
        tinymce.get('simple_descripcion_larga').remove();
    }
});

function abrirModalSimple(producto) {
  editState.productoActual = producto;
  editState.imagenes = producto.imagenes || [];
  editState.imagenesNuevas = [];  
  editState.imagenesAEliminar = [];
  editState.upsells = [];
  editState.crosssells = [];

  // Datos básicos
  $('#simple_id').val(producto.id);
  $('#simple_nombre').val(producto.nombre);
  $('#simple_marca').val(producto.marca || '');
  $('#simple_descripcion').val(producto.descripcion || '');
  $('#simple_estado').val(producto.estado || 'borrador');
  $('#simple_precio_regular').val(producto.precio_regular || '');
  $('#simple_precio_rebajado').val(producto.precio_rebajado || '');
  setupInventarioFields(producto);
  $('#simple_sku').val(producto.sku || '');
  $('#simple_stock').val(producto.stock || '');
  $('#simple_estado_inventario').val(producto.estado_inventario || 'existe');
  $('#simple_peso').val(producto.peso || '');
  $('#simple_longitud').val(producto.longitud || '');
  $('#simple_anchura').val(producto.anchura || '');
  $('#simple_altura').val(producto.altura || '');
  $('#simple_nota_interna').val(producto.nota_interna || '');
  $('#simple_valoraciones').prop('checked', !!producto.permite_valoraciones);

  if (typeof tinymce !== 'undefined') {
        if (tinymce.get('simple_descripcion_larga')) {
            tinymce.get('simple_descripcion_larga').remove();
        }
        
        tinymce.init({
            selector: '#simple_descripcion_larga',
            language: 'es_MX',
            height: 400,
            menubar: false,
            license_key: 'gpl',
            base_url: '/assets/tinymce',  
            suffix: '.min',
            plugins: [
                'lists', 'link', 'autolink', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code',
                'fullscreen', 'insertdatetime', 'media', 'table',
                'wordcount'
            ],
            toolbar: 'undo redo | styles forecolor | bold italic | alignleft aligncenter alignright alignjustify',
            content_style: `
              @font-face {
                  font-family: 'default';
                  src: url('/fonts/Poppins-Light.ttf');
              }
              body {
                  font-family: 'default', Poppins, sans-serif;
                  font-size: 14px;
              }
            `,
            statusbar: false,
            forced_root_block: 'p',
            convert_urls: false,
            remove_script_host: false,
            paste_data_images: false,
            setup: function(editor) {
                editor.on('init', function() {
                    editor.setContent(producto.descripcion_completa || '');
                });
            }
        });
    } else {
        $('#simple_descripcion_larga').val(producto.descripcion_completa || '');
    }
  // Miniatura
  if (producto.imagen_miniatura) {
    $('#simple_miniImg').attr('src', "/" + producto.imagen_miniatura).show();
    $('#simple_miniPlaceholder').hide();
    $('#simple_removeMini').removeClass('d-none');
  }

  if (producto.productos_relacionados && producto.productos_relacionados.length > 0) {
    console.log('Productos relacionados cargados:', producto.productos_relacionados);
    
    producto.productos_relacionados.forEach(rel => {
      console.log('Procesando relación:', rel, 'pivot:', rel.pivot);
      
      // Verificar que la relación tenga datos válidos
      if (!rel || !rel.id) {
        console.warn('Relación inválida encontrada:', rel);
        return;
      }
      
      // Usar rel.pivot.tipo que es donde Laravel guarda el campo extra
      if (rel.pivot && rel.pivot.tipo === 'upsell') {
        editState.upsells.push({
          id: rel.id,
          nombre: rel.nombre || 'Producto no encontrado',
          sku: rel.sku || 'N/A'
        });
      } else if (rel.pivot && rel.pivot.tipo === 'crosssell') {
        editState.crosssells.push({
          id: rel.id,
          nombre: rel.nombre || 'Producto no encontrado',
          sku: rel.sku || 'N/A'
        });
      }
    });
  }

  console.log('Upsells cargados:', editState.upsells);
  console.log('Crosssells cargados:', editState.crosssells);

  renderImagenesExistentes();

  if (!document.getElementById('simple_removeAllImages')) {
    $('#simple_imagenes').after(`
      <button type="button" id="simple_removeAllImages" class="btn btn-sm btn-outline-danger mt-2 d-none">
        <i class="bi bi-trash"></i> Eliminar todas las imágenes
      </button>
    `);
  }

  setupEventosImagenes();
  setupMiniaturaEvents();
  setupProgramarRebaja(producto);
  setupProductoRelacionadoSearch('#simple_upsells', 'upsells');
  setupProductoRelacionadoSearch('#simple_crosssells', 'crosssells');

  // Etiquetas
  const tags = $('#simple_tags');
  tags.html('');
  if (producto.etiquetas && producto.etiquetas.length > 0) {
    producto.etiquetas.forEach(t => {
      tags.append(`<span class="badge me-1" style="background:${t.color}">${t.nombre}</span>`);
    });
  } else {
    tags.html('<span class="text-muted">Sin etiquetas</span>');
  }

  // 🔹 CATEGORÍAS + SUBCATEGORÍAS
  const categoriaId = producto.subcategoria ? producto.subcategoria.id_categoria : null;
  const subcategoriaId = producto.subcategoria ? producto.subcategoria.id : null;
  renderCategoriasSimple(categoriaId, subcategoriaId);

  // 🔹 ETIQUETAS
  renderEtiquetasSimpleUI(producto);

  // 🔹 ATRIBUTOS
  renderAtributosSimpleUI();

  // Mostrar modal
  const modal = new bootstrap.Modal(document.getElementById('modalProductoSimple'));
  modal.show();
}

function setupInventarioFields(producto) {
  // SKU
  $('#simple_sku').val(producto.sku || '');
  
  // Gestión de inventario (checkbox)
  const gestionInventario = producto.gestion_inventario == 1 || producto.gestion_inventario === true;
  $('#simple_gestion_inventario').prop('checked', gestionInventario);
  
  // Stock
  $('#simple_stock').val(producto.stock || 0);
  
  // Backorders (radio buttons)
  const backorders = producto.backorders == 1 || producto.backorders === true;
  if (backorders) {
    $('#simple_backorders_si').prop('checked', true);
  } else {
    $('#simple_backorders_no').prop('checked', true);
  }
  
  // Estado de inventario (radio buttons)
  const estadoInventario = producto.estado_inventario || 'existe';
  $(`#simple_estado_${estadoInventario}`).prop('checked', true);
  
  // Vendido individualmente (checkbox)
  const vendidoIndividualmente = producto.vendido_individualmente == 1 || producto.vendido_individualmente === true;
  $('#simple_vendido_individualmente').prop('checked', vendidoIndividualmente);
  
  // Mostrar/ocultar detalles de inventario según el checkbox
  toggleInventarioDetalles(gestionInventario);
  
  // Evento para el checkbox de gestión de inventario
  $('#simple_gestion_inventario').off('change').on('change', function() {
    toggleInventarioDetalles(this.checked);
  });
}

// Función para mostrar/ocultar los detalles de inventario
function toggleInventarioDetalles(mostrar) {
  const $detalles = $('#simple_inventario_detalles');
  const $stockInput = $('#simple_stock');
  
  if (mostrar) {
    $detalles.slideDown();
    $stockInput.prop('disabled', false);
  } else {
    $detalles.slideUp();
    $stockInput.prop('disabled', true).val(0);
    
    // Resetear radios cuando se oculta
    $('#simple_backorders_no').prop('checked', true);
    $('#simple_estado_existe').prop('checked', true);
  }
}
function setupProductoRelacionadoSearch(selector, type) {
  const $input = $(selector);
  if (!$input.length) {
    console.error(`Input ${selector} no encontrado`);
    return;
  }

  console.log(`Configurando búsqueda para: ${type}, selector: ${selector}`);

  // VERIFICAR SI YA EXISTE EL CONTENEDOR - EVITAR DUPLICADOS
  const containerId = `${type}-tags-container`;
  let $container = $(`#${containerId}`);
  
  if ($container.length === 0) {
    // Crear contenedor para tags solo si no existe
    $container = $(`<div id="${containerId}" class="tag-container d-flex flex-wrap gap-2 mt-2 mb-3"></div>`);
    $input.after($container);
    console.log(`Contenedor ${containerId} creado`);
  } else {
    console.log(`Contenedor ${containerId} ya existe, limpiando contenido...`);
    $container.empty();
  }

  // VERIFICAR SI YA EXISTE EL DROPDOWN - EVITAR DUPLICADOS
  const dropdownClass = `dropdown-${type}`;
  let $dropdown = $(`.${dropdownClass}`);
  
  if ($dropdown.length === 0) {
    // Crear dropdown solo si no existe
    $dropdown = $(`
      <div class="dropdown-menu p-0 shadow ${dropdownClass}" 
           style="display: none; max-height: 250px; overflow-y: auto; width: 100%;">
      </div>
    `);
    console.log(`Dropdown ${dropdownClass} creado`);
  } else {
    console.log(`Dropdown ${dropdownClass} ya existe, limpiando contenido...`);
    $dropdown.empty();
  }

  // VERIFICAR SI YA EXISTE EL WRAPPER - EVITAR DUPLICADOS
  if (!$input.parent().hasClass('position-relative')) {
    // Crear un wrapper para el input y dropdown solo si no existe
    const $wrapper = $('<div class="position-relative"></div>');
    $input.wrap($wrapper);
    console.log('Wrapper creado');
  } else {
    console.log('Wrapper ya existe');
  }

  // Asegurarse de que el dropdown esté en el DOM correctamente
  if ($dropdown.parent().length === 0) {
    $input.after($dropdown);
    console.log('Dropdown agregado al DOM');
  }

  // Limpiar eventos previos para evitar duplicación
  $input.off('input.productoSearch');
  $input.off('keydown.productoSearch');
  $(window).off(`resize.${type}`);

  let timeout = null;

  // Evento de búsqueda
  $input.on('input.productoSearch', function(e) {
    console.log(`Buscando para ${type}:`, $(this).val());
    
    clearTimeout(timeout);
    const term = $(this).val().trim();
    
    if (!term || term.length < 2) {
      $dropdown.hide();
      return;
    }

    // Mostrar indicador de búsqueda
    $dropdown.html(`
      <div class="dropdown-item text-muted py-2">
        <div class="d-flex align-items-center">
          <div class="spinner-border spinner-border-sm text-primary bg-transparent me-2"></div>
          <span>Buscando...</span>
        </div>
      </div>
    `);
    positionDropdown();
    $dropdown.show();

    timeout = setTimeout(async () => {
      try {
        // 🔥 OBTENER EL ID DEL PRODUCTO ACTUAL
        const productoId = $('#simple_id').val();
        
        const { data } = await axios.post('productos', {
          opcion: 'Buscar',
          query: term,
          producto_id: productoId // Enviar el ID al backend
        });

        console.log(`Resultados para ${type}:`, data);

        if (data.respuesta === 'ok' && data.productos && data.productos.length > 0) {
          renderDropdown(data.productos);
          positionDropdown();
          $dropdown.show();
        } else {
          // MOSTRAR MENSAJE CUANDO NO HAY RESULTADOS
          $dropdown.html(`
            <div class="dropdown-item text-muted py-2">
              <i class="bi bi-search me-2"></i>
              No se encontraron productos
            </div>
          `);
          positionDropdown();
        }
      } catch (err) {
        console.error('Error buscando productos:', err);
        $dropdown.html(`
          <div class="dropdown-item text-danger py-2">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Error en la búsqueda
          </div>
        `);
        positionDropdown();
      }
    }, 400);
  });

  // Posicionar dropdown correctamente
  function positionDropdown() {
    const inputHeight = $input.outerHeight();
    
    $dropdown.css({
      position: 'absolute',
      top: inputHeight + 5,
      left: 0,
      width: $input.outerWidth(),
      display: 'block',
      zIndex: 1060 // Bootstrap modal tiene z-index 1055
    });
  }

  // Renderizar dropdown
  function renderDropdown(productos) {
    $dropdown.empty();
    
    // Filtrar productos que ya están seleccionados
    const selectedIds = editState[type] ? editState[type].map(p => p.id) : [];
    console.log(`IDs ya seleccionados para ${type}:`, selectedIds);
    
    const productosFiltrados = productos.filter(p => !selectedIds.includes(p.id));
    
    if (productosFiltrados.length === 0) {
      $dropdown.html(`
        <div class="dropdown-item text-muted py-2">
          <i class="bi bi-info-circle me-2"></i>
          No hay productos disponibles
        </div>
      `);
      return;
    }

    productosFiltrados.forEach(prod => {
      // Función para truncar texto largo
      function truncateText(text, maxLength = 50) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
      }

      const nombreTruncado = truncateText(prod.nombre, 45);
      const skuTexto = prod.sku ? `SKU: ${prod.sku}` : 'Sin SKU';
      
      const $item = $(`
        <button type="button" class="dropdown-item text-start py-2">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="fw-medium" title="${escapeHtml(prod.nombre)}">
                ${escapeHtml(nombreTruncado)}
              </div>
              <div class="small text-muted">
                ${skuTexto} • ID: ${prod.id}
              </div>
            </div>
            <i class="bi bi-plus-circle ms-2 text-primary bg-transparent"></i>
          </div>
        </button>
      `);
      
      $item.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log(`Agregando producto ${prod.id} a ${type}`);
        addProductTag(prod, type);
        $dropdown.hide();
      });
      
      $dropdown.append($item);
    });
  }

  // Evento para cerrar dropdown al hacer clic fuera
  $(document).off(`click.${type}`).on(`click.${type}`, function(e) {
    if (!$(e.target).closest($input).length && !$(e.target).closest($dropdown).length) {
      $dropdown.hide();
    }
  });

  // Tecla ESC para cerrar
  $input.on('keydown.productoSearch', function(e) {
    if (e.key === 'Escape') {
      $dropdown.hide();
    }
  });

  // Redimensionar dropdown cuando cambie el tamaño de la ventana
  $(window).on(`resize.${type}`, positionDropdown);

  // Cargar tags existentes si los hay
  loadExistingTags(type);
}

function addProductTag(producto, type) {
  const existingIndex = editState[type].findIndex(p => p.id === producto.id);
  if (existingIndex !== -1) {
    Swal.fire({
      title: 'Ya agregado',
      text: 'Este producto ya está en la lista',
      icon: 'info',
      timer: 1500,
      showConfirmButton: false
    });
    return;
  }

  // Añadir al estado
  editState[type].push({
    id: producto.id,
    nombre: producto.nombre,
    sku: producto.sku || 'N/A'
  });

  console.log(`Estado actualizado para ${type}:`, editState[type]);

  // Actualizar tags visuales
  updateTags(type);
  
  // Limpiar input
  $(`#simple_${type === 'upsells' ? 'upsells' : 'crosssells'}`).val('');
}

// Función para truncar texto en tags
function truncateTagText(text, maxLength = 25) {
  if (!text || text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
}

// Actualizar tags visuales - MODIFICADO para prevenir duplicados Y truncar texto largo
function updateTags(type) {
  const containerId = `${type}-tags-container`;
  const $container = $(`#${containerId}`);
  
  if (!$container.length) {
    console.error(`Contenedor ${containerId} no encontrado`);
    return;
  }

  // Limpiar completamente
  $container.empty();
  
  // Usar un Set para prevenir duplicados por si acaso
  const uniqueProducts = [];
  const seenIds = new Set();
  
  editState[type].forEach(producto => {
    if (!seenIds.has(producto.id)) {
      seenIds.add(producto.id);
      uniqueProducts.push(producto);
    }
  });

  // Actualizar estado con productos únicos
  editState[type] = uniqueProducts;
  
  // Renderizar tags únicos con texto truncado
  uniqueProducts.forEach(producto => {
    // Truncar nombre si es muy largo
    const nombreTruncado = truncateTagText(producto.nombre, 30);
    const skuTexto = producto.sku || 'N/A';
    
    const $tag = $(`
      <span class="badge bg-primary text-white border-0 px-3 py-2 d-inline-flex align-items-center mb-2 me-2 tag-producto" 
            data-product-id="${producto.id}"
            title="${escapeHtml(producto.nombre)} (${skuTexto})">
        <span class="me-1 text-truncate" style="max-width: 150px;">
          ${escapeHtml(nombreTruncado)}
        </span>
        <small class="me-1 opacity-75">
          (${truncateTagText(skuTexto, 10)})
        </small>
        <button type="button" class="btn-close btn-close-white btn-sm ms-1 remove-tag-btn" 
                data-id="${producto.id}" data-type="${type}"
                title="Eliminar"></button>
      </span>
    `);
    
    $container.append($tag);
  });

  // Si no hay tags, mostrar mensaje UNA sola vez
  if (uniqueProducts.length === 0) {
    $container.html('<span class="text-muted small">No hay productos seleccionados</span>');
  }
}

// Cargar tags existentes
function loadExistingTags(type) {
  console.log(`Cargando tags existentes para ${type}:`, editState[type]);
  
  // Limpiar posibles duplicados en el estado antes de cargar
  const uniqueProducts = [];
  const seenIds = new Set();
  
  editState[type].forEach(producto => {
    if (!seenIds.has(producto.id)) {
      seenIds.add(producto.id);
      uniqueProducts.push(producto);
    }
  });
  
  editState[type] = uniqueProducts;
  
  updateTags(type);
}

// Evento delegado para eliminar tags - MODIFICADO para mayor robustez
$(document).off('click.remove-tag').on('click.remove-tag', '.remove-tag-btn', function() {
  const productId = parseInt($(this).data('id'));
  const type = $(this).data('type');
  
  console.log(`Eliminando producto ${productId} de ${type}`);
  
  if (!productId || !type) {
    console.error('Datos inválidos para eliminar tag');
    return;
  }
  
  // Eliminar del estado
  const originalLength = editState[type].length;
  editState[type] = editState[type].filter(p => p.id !== productId);
  
  if (editState[type].length === originalLength) {
    console.warn(`Producto ${productId} no encontrado en ${type}`);
  }
  
  // Actualizar vista
  updateTags(type);
});

function limpiarProductoRelacionadoSearch() {
  console.log('Limpiando búsquedas de productos relacionados...');
  
  $(document).off('click.upsells');
  $(document).off('click.crosssells');
  $(window).off('resize.upsells');
  $(window).off('resize.crosssells');
}

function renderImagenesExistentes() {
  const prev = $('#simple_previewContainer');
  prev.html('');
  
  // Mostrar imágenes existentes
  editState.imagenes.forEach((img, index) => {
    const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative existing-image');
    
    preview.html(`
      <img src="/${img.imagen_path}" 
           class="rounded border" 
           style="width:80px;height:80px;object-fit:cover;cursor:pointer;"
           data-index="${index}">
      <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
              style="transform: translate(30%, -30%); padding: 0.2rem;"
              data-index="${index}" data-type="existente"></button>
    `);
    
    prev.append(preview);
  });
  
  // Mostrar imágenes nuevas (si las hay)
  editState.imagenesNuevas.forEach((imgData, index) => {
    const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative new-image');
    
    preview.html(`
      <img src="${imgData.preview}" 
           class="rounded border" 
           style="width:80px;height:80px;object-fit:cover;"
           data-index="${index}">
      <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
              style="transform: translate(30%, -30%); padding: 0.2rem;"
              data-index="${index}" data-type="nueva"></button>
    `);
    
    prev.append(preview);
  });

  // Mostrar mensaje si no hay imágenes
  if (editState.imagenes.length === 0 && editState.imagenesNuevas.length === 0) {
    prev.html('<span class="text-muted">No hay imágenes</span>');
  }

  // Actualizar contador
  actualizarContadorImagenes();
}

function renderImagenesExistentes() {
  const prev = $('#simple_previewContainer');
  prev.html('');
  
  // Mostrar imágenes existentes
  editState.imagenes.forEach((img, index) => {
    const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative existing-image');
    
    preview.html(`
      <img src="/${img.imagen_path}" 
           class="rounded border" 
           style="width:80px;height:80px;object-fit:cover;cursor:pointer;"
           data-index="${index}">
      <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
              style="transform: translate(30%, -30%); padding: 0.2rem;"
              data-index="${index}" data-type="existente"></button>
    `);
    
    prev.append(preview);
  });
  
  // Mostrar imágenes nuevas (si las hay)
  editState.imagenesNuevas.forEach((imgData, index) => {
    const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative new-image');
    
    preview.html(`
      <img src="${imgData.preview}" 
           class="rounded border" 
           style="width:80px;height:80px;object-fit:cover;"
           data-index="${index}">
      <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
              style="transform: translate(30%, -30%); padding: 0.2rem;"
              data-index="${index}" data-type="nueva"></button>
    `);
    
    prev.append(preview);
  });

  // Mostrar mensaje si no hay imágenes
  if (editState.imagenes.length === 0 && editState.imagenesNuevas.length === 0) {
    prev.html('<span class="text-muted">No hay imágenes</span>');
  }

  // Actualizar contador
  actualizarContadorImagenes();
}

function setupEventosImagenes() {
  // Previsualización al seleccionar imágenes nuevas
  $('#simple_imagenes').off('change').on('change', function(e) {
    const files = e.target.files;
    if (!files || files.length === 0) return;
    
    // Verificar límite (máximo 6)
    const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length + files.length;
    
    if (totalImagenes > 6) {
      Swal.fire({
        title: 'Límite excedido',
        text: `Ya tienes ${editState.imagenes.length + editState.imagenesNuevas.length} imágenes. Solo puedes agregar ${6 - (editState.imagenes.length + editState.imagenesNuevas.length)} más.`,
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      $(this).val('');
      return;
    }

    // Procesar cada archivo
    Array.from(files).forEach(file => {
      if (!file.type.startsWith('image/')) {
        Swal.fire('Error', `El archivo ${file.name} no es una imagen válida`, 'error');
        return;
      }
      
      const reader = new FileReader();
      reader.onload = function(event) {
        // Agregar a imagenesNuevas
        editState.imagenesNuevas.push({
          file: file,
          preview: event.target.result,
          name: file.name
        });
        
        // Re-renderizar todas las imágenes
        renderImagenesExistentes();
      };
      reader.readAsDataURL(file);
    });

    // Limpiar el input para permitir seleccionar las mismas imágenes nuevamente si es necesario
    $(this).val('');
  });

  // Eliminar imagen (evento delegado)
  $(document).off('click', '#simple_previewContainer .remove-image').on('click', '#simple_previewContainer .remove-image', function() {
    const index = parseInt($(this).data('index'));
    const type = $(this).data('type');
    
    Swal.fire({
      title: '¿Eliminar imagen?',
      text: type === 'existente' 
        ? 'Esta imagen se eliminará al guardar el producto' 
        : 'Esta imagen será removida de la selección',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        if (type === 'existente') {
          // Es una imagen existente - marcar para eliminar
          if (!editState.imagenesAEliminar) {
            editState.imagenesAEliminar = [];
          }
          editState.imagenesAEliminar.push(editState.imagenes[index].id);
          editState.imagenes.splice(index, 1);
        } else if (type === 'nueva') {
          // Es una imagen nueva - simplemente remover
          editState.imagenesNuevas.splice(index, 1);
        }
        
        renderImagenesExistentes();
      }
    });
  });

  // Eliminar todas las imágenes
  $('#simple_removeAllImages').off('click').on('click', function() {
    const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length;
    
    if (totalImagenes === 0) return;
    
    Swal.fire({
      title: '¿Eliminar todas las imágenes?',
      text: 'Se eliminarán todas las imágenes (tanto existentes como nuevas)',
      html: `
        <div class="text-start">
          <p>Se eliminarán:</p>
          <ul>
            ${editState.imagenes.length > 0 ? `<li>${editState.imagenes.length} imágenes existentes</li>` : ''}
            ${editState.imagenesNuevas.length > 0 ? `<li>${editState.imagenesNuevas.length} imágenes nuevas</li>` : ''}
          </ul>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar todas',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545'
    }).then((result) => {
      if (result.isConfirmed) {
        // Marcar todas las imágenes existentes para eliminar
        if (editState.imagenes.length > 0) {
          if (!editState.imagenesAEliminar) {
            editState.imagenesAEliminar = [];
          }
          editState.imagenes.forEach(img => {
            if (img.id) {
              editState.imagenesAEliminar.push(img.id);
            }
          });
        }
        
        // Limpiar todo
        editState.imagenes = [];
        editState.imagenesNuevas = [];
        $('#simple_imagenes').val('');
        
        renderImagenesExistentes();
        
        Swal.fire('Eliminadas', 'Todas las imágenes han sido eliminadas', 'success');
      }
    });
  });
}
function setupEventosImagenes() {
  // Previsualización al seleccionar imágenes nuevas
  $('#simple_imagenes').off('change').on('change', function(e) {
    const files = e.target.files;
    if (!files || files.length === 0) return;
    
    // Verificar límite (máximo 6)
    const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length + files.length;
    
    if (totalImagenes > 6) {
      Swal.fire({
        title: 'Límite excedido',
        text: `Ya tienes ${editState.imagenes.length + editState.imagenesNuevas.length} imágenes. Solo puedes agregar ${6 - (editState.imagenes.length + editState.imagenesNuevas.length)} más.`,
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      $(this).val('');
      return;
    }

    // Procesar cada archivo
    Array.from(files).forEach(file => {
      if (!file.type.startsWith('image/')) {
        Swal.fire('Error', `El archivo ${file.name} no es una imagen válida`, 'error');
        return;
      }
      
      const reader = new FileReader();
      reader.onload = function(event) {
        // Agregar a imagenesNuevas
        editState.imagenesNuevas.push({
          file: file,
          preview: event.target.result,
          name: file.name
        });
        
        // Re-renderizar todas las imágenes
        renderImagenesExistentes();
      };
      reader.readAsDataURL(file);
    });

    // Limpiar el input para permitir seleccionar las mismas imágenes nuevamente si es necesario
    $(this).val('');
  });

  // Eliminar imagen (evento delegado)
  $(document).off('click', '#simple_previewContainer .remove-image').on('click', '#simple_previewContainer .remove-image', function() {
    const index = parseInt($(this).data('index'));
    const type = $(this).data('type');
    
    Swal.fire({
      title: '¿Eliminar imagen?',
      text: type === 'existente' 
        ? 'Esta imagen se eliminará al guardar el producto' 
        : 'Esta imagen será removida de la selección',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        if (type === 'existente') {
          // Es una imagen existente - marcar para eliminar
          if (!editState.imagenesAEliminar) {
            editState.imagenesAEliminar = [];
          }
          editState.imagenesAEliminar.push(editState.imagenes[index].id);
          editState.imagenes.splice(index, 1);
        } else if (type === 'nueva') {
          // Es una imagen nueva - simplemente remover
          editState.imagenesNuevas.splice(index, 1);
        }
        
        renderImagenesExistentes();
      }
    });
  });

  // Eliminar todas las imágenes
  $('#simple_removeAllImages').off('click').on('click', function() {
    const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length;
    
    if (totalImagenes === 0) return;
    
    Swal.fire({
      title: '¿Eliminar todas las imágenes?',
      text: 'Se eliminarán todas las imágenes (tanto existentes como nuevas)',
      html: `
        <div class="text-start">
          <p>Se eliminarán:</p>
          <ul>
            ${editState.imagenes.length > 0 ? `<li>${editState.imagenes.length} imágenes existentes</li>` : ''}
            ${editState.imagenesNuevas.length > 0 ? `<li>${editState.imagenesNuevas.length} imágenes nuevas</li>` : ''}
          </ul>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar todas',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545'
    }).then((result) => {
      if (result.isConfirmed) {
        // Marcar todas las imágenes existentes para eliminar
        if (editState.imagenes.length > 0) {
          if (!editState.imagenesAEliminar) {
            editState.imagenesAEliminar = [];
          }
          editState.imagenes.forEach(img => {
            if (img.id) {
              editState.imagenesAEliminar.push(img.id);
            }
          });
        }
        
        // Limpiar todo
        editState.imagenes = [];
        editState.imagenesNuevas = [];
        $('#simple_imagenes').val('');
        
        renderImagenesExistentes();
        
        Swal.fire('Eliminadas', 'Todas las imágenes han sido eliminadas', 'success');
      }
    });
  });
}

function actualizarContadorImagenes(debug = false) {
  // Calcular totales
  const totalExistentes = editState.imagenes ? editState.imagenes.length : 0;
  const totalNuevas = editState.imagenesNuevas ? editState.imagenesNuevas.length : 0;
  const total = totalExistentes + totalNuevas;
  
  if (debug) {
    console.log('=== ACTUALIZAR CONTADOR ===');
    console.log('Imágenes existentes:', editState.imagenes);
    console.log('Imágenes nuevas:', editState.imagenesNuevas);
    console.log(`Total: ${totalExistentes} + ${totalNuevas} = ${total}`);
  }
  
  // Asegurarse de que el elemento de nota exista
  let $noteSmall = $('#simple_imagenes').closest('.card-section').find('.note-small');
  if ($noteSmall.length === 0) {
    // Si no existe en el contenedor, crearlo después del input
    $noteSmall = $('<div class="note-small mt-1"></div>');
    $('#simple_imagenes').after($noteSmall);
  }
  
  // Asegurarse de que el botón eliminar todas exista
  let $removeAllBtn = $('#simple_removeAllImages');
  if ($removeAllBtn.length === 0) {
    $removeAllBtn = $(`
      <button type="button" id="simple_removeAllImages" class="btn btn-sm btn-outline-danger mt-2 d-none">
        <i class="bi bi-trash"></i> Eliminar todas las imágenes
      </button>
    `);
    $noteSmall.after($removeAllBtn);
  }
  
  // Actualizar botón eliminar todas
  if (total > 0) {
    $removeAllBtn.removeClass('d-none');
  } else {
    $removeAllBtn.addClass('d-none');
  }
  
  // Actualizar input y mensaje
  const $input = $('#simple_imagenes');
  const maxImagenes = 6;
  
  if (total >= maxImagenes) {
    // Límite alcanzado
    $input.prop('disabled', true);
    $noteSmall
      .removeClass('text-muted')
      .addClass('text-danger fw-bold bg-transparent')
      .html(`<i class="bi bi-exclamation-triangle me-1"></i> Límite de ${maxImagenes} imágenes alcanzado`);
    
    if (debug) console.log('Límite alcanzado - input deshabilitado');
  } else {
    // Aún hay espacio
    const espacioRestante = maxImagenes - total;
    $input.prop('disabled', false);
    
    // Actualizar atributo multiple para permitir selección múltiple hasta el límite
    $input.attr('multiple', espacioRestante > 1);
    
    $noteSmall
      .removeClass('text-danger fw-bold bg-transparent')
      .addClass('text-muted')
      .html(`
        <i class="bi bi-info-circle me-1"></i>
        Máximo ${maxImagenes} imágenes (${total}/${maxImagenes})
        ${espacioRestante > 0 ? `- Puedes agregar ${espacioRestante} más` : ''}
      `);
    
    if (debug) console.log(`Espacio disponible: ${espacioRestante} imágenes`);
  }
  return total;
}

function setupMiniaturaEvents() {
  const $miniPreview = $('#simple_miniPreview');
  const $miniInput = $('#simple_miniatura');
  const $miniImg = $('#simple_miniImg');
  const $miniPlaceholder = $('#simple_miniPlaceholder');
  const $removeBtn = $('#simple_removeMini');
  const $miniImgSrc = $miniImg.attr('src');

  // Click en el área para seleccionar archivo
  $miniPreview.off('click').on('click', function() {
    $miniInput.click();
  });

  // Cambio en el input de archivo
  $miniInput.off('change').on('change', function(e) {
    const file = e.target.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = function(event) {
      $miniImg.attr('src', event.target.result).show();
      $miniPlaceholder.hide();
      $removeBtn.removeClass('d-none');
      
      // Guardar referencia para enviar
      editState.nuevaMiniatura = file;
    };
    reader.readAsDataURL(file);
  });

  // Eliminar miniatura
  $removeBtn.off('click').on('click', function(e) {
    e.stopPropagation();
    
    Swal.fire({
      title: '¿Eliminar miniatura?',
      text: 'La imagen se eliminará al guardar el producto',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $miniInput.val('');
        $miniImg.hide().attr('src', '');
        $miniPlaceholder.show();
        $removeBtn.addClass('d-none');
        
        // Marcar para eliminar la existente
        editState.eliminarMiniatura = true;
        delete editState.nuevaMiniatura;
      }
    });
  });

  // Si hay imagen existente, configurar para reemplazo
  if ($miniImgSrc) {
    editState.miniaturaOriginal = $miniImgSrc;
  }
}
function setupProgramarRebaja(producto) {
  const $check = $('#simple_programar_rebaja');
  const $fechasDiv = $('#simple_fechas_rebaja');
  const $fechaInicio = $('#simple_fecha_inicio_rebaja');
  const $fechaFin = $('#simple_fecha_fin_rebaja');
  
  // Función para convertir fecha ISO a YYYY-MM-DD
  function formatDateForInput(dateString) {
    if (!dateString) return '';
    
    // Si ya está en formato YYYY-MM-DD, retornar igual
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
      return dateString;
    }
    
    // Si es formato ISO (2025-10-08T00:00:00.000000Z)
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
  }
  
  // Verificar si ya hay fechas programadas
  const fechaInicioFormateada = formatDateForInput(producto.fecha_inicio_rebaja);
  const fechaFinFormateada = formatDateForInput(producto.fecha_fin_rebaja);
  
  const tieneFechaInicio = fechaInicioFormateada !== '';
  const tieneFechaFin = fechaFinFormateada !== '';
  const tieneProgramacionCompleta = tieneFechaInicio && tieneFechaFin;
  
  if (tieneProgramacionCompleta) {
    // Si tiene ambas fechas, activar checkbox y mostrar
    $check.prop('checked', true);
    $fechasDiv.show();
    
    // Establecer valores formateados
    $fechaInicio.val(fechaInicioFormateada);
    $fechaFin.val(fechaFinFormateada);
  } else {
    $check.prop('checked', false);
    $fechasDiv.hide();
    
    $fechaInicio.val('');
    $fechaFin.val('');
    
    producto.fecha_inicio_rebaja = null;
    producto.fecha_fin_rebaja = null;
  }
  
  // Evento para mostrar/ocultar fechas
  $check.off('change').on('change', function() {
    if (this.checked) {
      $fechasDiv.slideDown();
      if (!$fechaInicio.val()) {
        const hoy = new Date();
        const sieteDiasDespues = new Date();
        sieteDiasDespues.setDate(hoy.getDate() + 7);
        
        $fechaInicio.val(formatDateForInput(hoy.toISOString()));
        $fechaFin.val(formatDateForInput(sieteDiasDespues.toISOString()));
      }
    } else {
      $fechasDiv.slideUp();
      // Limpiar fechas al desmarcar
      $fechaInicio.val('');
      $fechaFin.val('');
    }
  });
  
  // Validación: fecha fin no puede ser menor a fecha inicio
  $fechaInicio.off('change').on('change', function() {
    const inicio = new Date(this.value);
    const fin = new Date($fechaFin.val());
    
    if (this.value && $fechaFin.val() && inicio > fin) {
      Swal.fire({
        title: 'Error',
        text: 'La fecha fin no puede ser anterior a la fecha inicio',
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      this.value = '';
    }
  });
  
  $fechaFin.off('change').on('change', function() {
    const inicio = new Date($fechaInicio.val());
    const fin = new Date(this.value);
    
    if (this.value && $fechaInicio.val() && fin < inicio) {
      Swal.fire({
        title: 'Error',
        text: 'La fecha fin no puede ser anterior a la fecha inicio',
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      this.value = '';
    }
  });
}
// Renderiza categorías y subcategorías
function renderCategoriasSimple(categoriaId, subcategoriaId) {
  const catSelect = $('#simple_categoria');
  const subcatDiv = $('#simple_subcategorias');

  // Llenar categorías
  catSelect.html('<option value="">-- Seleccione --</option>');
  _CATEGORIAS.forEach(cat => {
    catSelect.append(`<option value="${cat.id}" ${cat.id == categoriaId ? 'selected' : ''}>${cat.nombre}</option>`);
  });

  // Mostrar subcategorías
  function loadSubcategorias(catId, selectedSubId) {
    subcatDiv.html('');
    const categoria = _CATEGORIAS.find(c => c.id == catId);
    if (!categoria || !categoria.subcategorias || categoria.subcategorias.length === 0) {
      subcatDiv.html('<span class="text-muted">Sin subcategorías</span>');
      return;
    }

    categoria.subcategorias.forEach(sc => {
      const isChecked = sc.id == selectedSubId ? 'checked' : '';
      subcatDiv.append(`
        <div class="form-check">
          <input class="form-check-input" type="radio" name="subcategoria_id" value="${sc.id}" id="subcat_${sc.id}" ${isChecked}>
          <label class="form-check-label" for="subcat_${sc.id}">${sc.nombre}</label>
        </div>
      `);
    });
  }

  // Cargar subcategorías iniciales
  if (categoriaId) {
    loadSubcategorias(categoriaId, subcategoriaId);
  } else {
    subcatDiv.html('<span class="text-muted">Seleccione una categoría primero</span>');
  }

  // Evento cambio de categoría
  catSelect.off('change').on('change', function () {
    loadSubcategorias(this.value, null);
  });
}
$(document).on('click', '#btnGuardarSimple', function() {
    guardarProductoSimple();
});
async function guardarProductoSimple() {
    // Validar campos obligatorios
    if (!validarProductoSimple()) {
        return;
    }
    
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    
    // --- Datos básicos ---
    formData.append('opcion', 'Editar');
    formData.append('id', $('#simple_id').val());
    formData.append('nombre', $('#simple_nombre').val());
    formData.append('marca', $('#simple_marca').val());
    formData.append('descripcion', $('#simple_descripcion').val());
    const editor = tinymce.get('simple_descripcion_larga');
    if (editor && typeof editor.getContent === 'function') {
        const contenido = editor.getContent();
        formData.set('descripcion_larga', contenido);
    } else {
        formData.set('descripcion_larga', $('#simple_descripcion_larga').val());
    }
    formData.append('estado', $('#simple_estado').val());
    formData.append('subcategoria_id', $('input[name="subcategoria_id"]:checked').val());
    
    // --- Precios ---
    formData.append('precio_regular', $('#simple_precio_regular').val() || 0);
    
    const programarRebaja = $('#simple_programar_rebaja').is(':checked');
    formData.append('programar_rebaja', programarRebaja);
    formData.append('precio_rebajado', $('#simple_precio_rebajado').val() || '');
    
    if (programarRebaja) {
        formData.append('fecha_inicio_rebaja', $('#simple_fecha_inicio_rebaja').val());
        formData.append('fecha_fin_rebaja', $('#simple_fecha_fin_rebaja').val());
    }
    
    // --- Inventario ---
    formData.append('sku', $('#simple_sku').val() || '');
    formData.append('gestion_inventario', $('#simple_gestion_inventario').is(':checked'));
    formData.append('stock', $('#simple_stock').val() || 0);
    formData.append('estado_inventario', $('input[name="estado_inventario"]:checked').val() || 'existe');
    formData.append('backorders', $('input[name="backorders"]:checked').val() || 'no');
    formData.append('vendido_individualmente', $('#simple_vendido_individualmente').is(':checked'));
    
    // --- Envío ---
    formData.append('peso', $('#simple_peso').val() || '');
    formData.append('peso_unidad', $('#simple_peso_unidad').val() || 'kg');
    formData.append('longitud', $('#simple_longitud').val() || '');
    formData.append('anchura', $('#simple_anchura').val() || '');
    formData.append('altura', $('#simple_altura').val() || '');
    
    // --- Avanzado ---
    formData.append('nota_interna', $('#simple_nota_interna').val() || '');
    formData.append('permite_valoraciones', $('#simple_valoraciones').is(':checked'));
    
    // --- MINIATURA ---
    // Marcar si debemos eliminar la miniatura existente
    if (editState.eliminarMiniatura) {
        formData.append('eliminar_miniatura', 'true');
    }
    
    // Si hay nueva miniatura, agregarla
    if (editState.nuevaMiniatura) {
        formData.append('imagen_miniatura', editState.nuevaMiniatura);
    }
    
    // --- IMÁGENES PRINCIPALES ---
    // IDs de imágenes a eliminar
    if (editState.imagenesAEliminar && editState.imagenesAEliminar.length > 0) {
        formData.append('imagenes_eliminar', JSON.stringify(editState.imagenesAEliminar));
    }
    
    // Nuevas imágenes
    if (editState.imagenesNuevas && editState.imagenesNuevas.length > 0) {
        editState.imagenesNuevas.forEach((imgData, index) => {
            formData.append('imagenes[]', imgData.file);
        });
    }
    
    // --- ETIQUETAS ---
    if (editState.productoActual.etiquetas && editState.productoActual.etiquetas.length > 0) {
        const etiquetasIds = editState.productoActual.etiquetas.map(e => e.id);
        formData.append('etiquetas', JSON.stringify(etiquetasIds));
    } else {
        formData.append('etiquetas', JSON.stringify([]));
    }
    
    // --- ATRIBUTOS ---
    if (editState.productoActual.atributos && editState.productoActual.atributos.length > 0) {
        const atributosParaEnviar = editState.productoActual.atributos.map(attr => ({
            atributo_id: attr.id,
            valores: attr.terminos ? attr.terminos.map(t => t.id) : [],
            visible: true,
            variacion: false
        }));
        formData.append('atributos', JSON.stringify(atributosParaEnviar));
    } else {
        formData.append('atributos', JSON.stringify([]));
    }
    
    // --- UPSELLS Y CROSSSELLS ---
    editState.upsells = editState.upsells.filter((prod, index, self) => 
        index === self.findIndex(p => p.id === prod.id)
    );
    editState.crosssells = editState.crosssells.filter((prod, index, self) => 
        index === self.findIndex(p => p.id === prod.id)
    );

    // Log para depuración
    console.log('Enviando upsells:', editState.upsells.map(p => p.id));
    console.log('Enviando crosssells:', editState.crosssells.map(p => p.id));

    // Enviar arrays de IDs
    if (editState.upsells && editState.upsells.length > 0) {
        formData.append('upsells', JSON.stringify(editState.upsells.map(p => p.id)));
    } else {
        formData.append('upsells', JSON.stringify([]));
    }

    if (editState.crosssells && editState.crosssells.length > 0) {
        formData.append('crosssells', JSON.stringify(editState.crosssells.map(p => p.id)));
    } else {
        formData.append('crosssells', JSON.stringify([]));
    }

    try {
        const response = await fetch('productos', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        Swal.close();

        if (data.respuesta === 'ok') {
            await Swal.fire({
                title: '¡Actualizado!',
                text: 'Producto actualizado correctamente',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalProductoSimple'));
            modal.hide();
            
            // Recargar DataTable
            $('#tablaProductos').DataTable().ajax.reload(null, false);
            
            // Limpiar estado
            limpiarEstadoEdicion();
            
        } else {
            Swal.fire('Error', data.mensaje || 'Error al actualizar', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        Swal.close();
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
}

function validarProductoSimple() {
    const nombre = $('#simple_nombre').val().trim();
    if (!nombre) {
        Swal.fire('Validación', 'El nombre del producto es requerido', 'warning');
        return false;
    }
    
    const subcategoria = $('input[name="subcategoria_id"]:checked').val();
    if (!subcategoria) {
        Swal.fire('Validación', 'Debes seleccionar una subcategoría', 'warning');
        return false;
    }
    
    const precio = $('#simple_precio_regular').val();
    if (!precio || parseFloat(precio) <= 0) {
        Swal.fire('Validación', 'El precio regular debe ser mayor a 0', 'warning');
        return false;
    }
    
    return true;
}

function limpiarEstadoEdicion() {
    editState.productoActual = null;
    editState.imagenes = [];
    editState.imagenesNuevas = [];
    editState.imagenesAEliminar = [];
    editState.upsells = [];
    editState.crosssells = [];
    editState.nuevaMiniatura = null;
    editState.eliminarMiniatura = false;
    
    // Limpiar formulario
    document.getElementById('formProductoSimple').reset();
    
    // Limpiar previsualizaciones
    $('#simple_previewContainer').empty();
    $('#simple_miniImg').hide().attr('src', '');
    $('#simple_miniPlaceholder').show();
    $('#simple_removeMini').addClass('d-none');
}