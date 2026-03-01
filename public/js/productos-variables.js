// ============================================
// EDITAR PRODUCTO VARIABLE
// ============================================

// Extender editState para incluir variaciones
editState.variaciones = [];
editState.variacionesAEliminar = [];
editState.variacionesImagenesEliminar = [];

// Función principal para abrir el modal de producto variable
async function abrirModalVariable(producto) {
    try {
        // Guardar producto en editState
        editState.productoActual = producto;
        editState.imagenes = producto.imagenes || [];
        editState.imagenesNuevas = [];
        editState.imagenesAEliminar = [];
        editState.variaciones = [];
        editState.variacionesAEliminar = [];
        editState.variacionesImagenesEliminar = [];
        editState.upsells = [];
        editState.crosssells = [];

        // Procesar variaciones existentes
        if (producto.variaciones && producto.variaciones.length > 0) {
            // Obtener todos los atributos marcados como variación del producto
            const atributosVariacion = (producto.atributos || [])
                .filter(pa => pa.pivot && pa.pivot.variacion)
                .map(pa => pa.id);
            
            editState.variaciones = producto.variaciones.map(v => {
                // Obtener los IDs de atributos que tiene esta variación
                const atributosExistentes = (v.atributos || []).map(a => String(a.atributo_id));
                
                // Crear array completo de atributos, incluyendo null para los que no tiene
                const atributosCompletos = atributosVariacion.map(atrId => {
                    const attrExistente = (v.atributos || []).find(a => String(a.atributo_id) === String(atrId));
                    return {
                        atrId: atrId,
                        termId: attrExistente ? attrExistente.id : null // null = "Cualquier valor"
                    };
                });
                
                return {
                    id: v.id,
                    sku: v.sku || '',
                    stock: v.stock || 0,
                    price_normal: v.precio_regular || '',
                    price_sale: v.precio_rebajado || '',
                    sale_start: v.fecha_inicio_rebaja || '',
                    sale_end: v.fecha_fin_rebaja || '',
                    weight: v.peso || '',
                    weight_type: v.peso_unidad || 'kg',
                    length: v.longitud || '',
                    width: v.anchura || '',
                    height: v.altura || '',
                    description: v.descripcion || '',
                    backorder: v.backorders ? 'yes' : 'no',
                    atributos: atributosCompletos, // Array completo con todos los atributos
                    images: (v.imagenes || []).map(img => ({
                        id: img.id,
                        url: '/' + img.imagen_path,
                        path: img.imagen_path,
                        isExisting: true
                    }))
                };
            });
        }

        // Procesar productos relacionados (upsells/crosssells)
        if (producto.productos_relacionados && producto.productos_relacionados.length > 0) {
            producto.productos_relacionados.forEach(rel => {
                if (rel.pivot && rel.pivot.tipo === 'upsell') {
                    editState.upsells.push({
                        id: rel.id,
                        nombre: rel.nombre || 'Producto sin nombre',
                        sku: rel.sku || 'N/A'
                    });
                } else if (rel.pivot && rel.pivot.tipo === 'crosssell') {
                    editState.crosssells.push({
                        id: rel.id,
                        nombre: rel.nombre || 'Producto sin nombre',
                        sku: rel.sku || 'N/A'
                    });
                }
            });
        }


        // Configurar todos los componentes
        configurarCamposBasicosVariable(producto);
        configurarMiniaturaVariable(producto);
        configurarImagenesVariable(producto);
        configurarCategoriasVariable(producto);
        configurarEtiquetasVariable(producto);
        configurarAtributosVariable(producto);
        configurarInventarioVariable(producto);
        configurarEnvioVariable(producto);
        configurarAvanzadoVariable(producto);
        configurarRelacionadosVariable();
        
        // Configurar pestañas
        configurarPestanasVariable();

        // Renderizar variaciones
        renderVariacionesVariable();

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalProductoVariable'));
        modal.show();

    } catch (error) {
        console.error('Error al abrir modal variable:', error);
        Swal.fire('Error', 'No se pudo cargar el producto variable', 'error');
    }
}


// ============================================
// CONFIGURACIÓN DE CAMPOS BÁSICOS
// ============================================

function configurarCamposBasicosVariable(producto) {
    $('#variable_id').val(producto.id);
    $('#variable_nombre').val(producto.nombre);
    $('#variable_marca').val(producto.marca || '');
    $('#variable_descripcion').val(producto.descripcion || '');
    $('#variable_estado').val(producto.estado || 'borrador');
}

// ============================================
// CONFIGURACIÓN DE MINIATURA
// ============================================

function configurarMiniaturaVariable(producto) {
    const $miniPreview = $('#variable_miniPreview');
    const $miniInput = $('#variable_miniatura');
    const $miniImg = $('#variable_miniImg');
    const $miniPlaceholder = $('#variable_miniPlaceholder');
    const $removeBtn = $('#variable_removeMini');

    if (producto.imagen_miniatura) {
        $miniImg.attr('src', '/' + producto.imagen_miniatura).show();
        $miniPlaceholder.hide();
        $removeBtn.removeClass('d-none');
    }

    $miniPreview.off('click').on('click', () => $miniInput.click());

    $miniInput.off('change').on('change', function(e) {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            $miniImg.attr('src', event.target.result).show();
            $miniPlaceholder.hide();
            $removeBtn.removeClass('d-none');
            editState.nuevaMiniatura = file;
        };
        reader.readAsDataURL(file);
    });

    $removeBtn.off('click').on('click', function(e) {
        e.stopPropagation();
        Swal.fire({
            title: '¿Eliminar miniatura?',
            text: 'La imagen se eliminará al guardar',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                $miniInput.val('');
                $miniImg.hide().attr('src', '');
                $miniPlaceholder.show();
                $removeBtn.addClass('d-none');
                editState.eliminarMiniatura = true;
                delete editState.nuevaMiniatura;
            }
        });
    });
}

// ============================================
// CONFIGURACIÓN DE IMÁGENES PRINCIPALES
// ============================================

function configurarImagenesVariable(producto) {
    renderImagenesExistentesVariable();
    setupEventosImagenesVariable();
}

function renderImagenesExistentesVariable() {
    const prev = $('#variable_previewContainer');
    prev.empty();

    // Imágenes existentes
    editState.imagenes.forEach((img, index) => {
        const preview = $(`
            <div class="d-inline-block me-2 mb-2 position-relative existing-image">
                <img src="/${img.imagen_path}" class="rounded border" style="width:80px;height:80px;object-fit:cover;">
                <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
                        data-index="${index}" data-type="existente"></button>
            </div>
        `);
        prev.append(preview);
    });

    // Imágenes nuevas
    editState.imagenesNuevas.forEach((imgData, index) => {
        const preview = $(`
            <div class="d-inline-block me-2 mb-2 position-relative new-image">
                <img src="${imgData.preview}" class="rounded border" style="width:80px;height:80px;object-fit:cover;">
                <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
                        data-index="${index}" data-type="nueva"></button>
            </div>
        `);
        prev.append(preview);
    });

    if (editState.imagenes.length === 0 && editState.imagenesNuevas.length === 0) {
        prev.html('<span class="text-muted">No hay imágenes</span>');
    }

    actualizarContadorImagenesVariable();
}

function setupEventosImagenesVariable() {
    $('#variable_imagenes').off('change').on('change', function(e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length + files.length;

        if (totalImagenes > 6) {
            Swal.fire({
                title: 'Límite excedido',
                text: `Solo puedes tener máximo 6 imágenes en total`,
                icon: 'error'
            });
            $(this).val('');
            return;
        }

        Array.from(files).forEach(file => {
            if (!file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                editState.imagenesNuevas.push({
                    file: file,
                    preview: event.target.result,
                    name: file.name
                });
                renderImagenesExistentesVariable();
            };
            reader.readAsDataURL(file);
        });

        $(this).val('');
    });

    $(document).off('click', '#variable_previewContainer .remove-image').on('click', '#variable_previewContainer .remove-image', function() {
        const index = parseInt($(this).data('index'));
        const type = $(this).data('type');

        Swal.fire({
            title: '¿Eliminar imagen?',
            text: type === 'existente' ? 'Esta imagen se eliminará al guardar' : 'Esta imagen será removida',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                if (type === 'existente') {
                    if (!editState.imagenesAEliminar) editState.imagenesAEliminar = [];
                    editState.imagenesAEliminar.push(editState.imagenes[index].id);
                    editState.imagenes.splice(index, 1);
                } else {
                    editState.imagenesNuevas.splice(index, 1);
                }
                renderImagenesExistentesVariable();
            }
        });
    });
}

function actualizarContadorImagenesVariable() {
    const total = (editState.imagenes?.length || 0) + (editState.imagenesNuevas?.length || 0);
    const $noteSmall = $('#variable_imagenes').closest('.card-section').find('.note-small');
    const $removeAllBtn = $('#variable_removeAllImages');

    if (total >= 6) {
        $('#variable_imagenes').prop('disabled', true);
        $noteSmall.html('<i class="bi bi-exclamation-triangle me-1"></i> Límite de 6 imágenes alcanzado');
    } else {
        $('#variable_imagenes').prop('disabled', false);
        $noteSmall.html(`<i class="bi bi-info-circle me-1"></i> Máximo 6 imágenes (${total}/6)`);
    }
}

// ============================================
// CONFIGURACIÓN DE CATEGORÍAS
// ============================================

function configurarCategoriasVariable(producto) {
    const catSelect = $('#variable_categoria');
    const subcatDiv = $('#variable_subcategorias');

    const categoriaId = producto.subcategoria ? producto.subcategoria.id_categoria : null;
    const subcategoriaId = producto.subcategoria ? producto.subcategoria.id : null;

    catSelect.html('<option value="">-- Seleccione --</option>');
    _CATEGORIAS.forEach(cat => {
        catSelect.append(`<option value="${cat.id}" ${cat.id == categoriaId ? 'selected' : ''}>${cat.nombre}</option>`);
    });

    function loadSubcategorias(catId, selectedSubId) {
        subcatDiv.html('');
        const categoria = _CATEGORIAS.find(c => c.id == catId);
        if (!categoria || !categoria.subcategorias?.length) {
            subcatDiv.html('<span class="text-muted">Sin subcategorías</span>');
            return;
        }

        categoria.subcategorias.forEach(sc => {
            subcatDiv.append(`
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="subcategoria_id" value="${sc.id}" id="subcat_${sc.id}" ${sc.id == selectedSubId ? 'checked' : ''}>
                    <label class="form-check-label" for="subcat_${sc.id}">${sc.nombre}</label>
                </div>
            `);
        });
    }

    if (categoriaId) {
        loadSubcategorias(categoriaId, subcategoriaId);
    }

    catSelect.off('change').on('change', function() {
        loadSubcategorias(this.value, null);
    });
}

// ============================================
// CONFIGURACIÓN DE ETIQUETAS
// ============================================

function configurarEtiquetasVariable(producto) {
    renderEtiquetasVariable(producto);
    setupEventosEtiquetasVariable();
}

function renderEtiquetasVariable(producto) {
    const availableDiv = $('#variable_availableTags');
    const selectedDiv = $('#variable_selectedTags');

    availableDiv.empty();
    selectedDiv.empty();

    const seleccionadas = (producto.etiquetas || []).map(e => e.id);

    _ETIQUETAS.forEach(tag => {
        availableDiv.append(`
            <div class="form-check">
                <input class="form-check-input tag-available" type="checkbox" value="${tag.id}" id="tag_${tag.id}" ${seleccionadas.includes(tag.id) ? 'checked' : ''}>
                <label class="form-check-label" for="tag_${tag.id}">${tag.nombre}</label>
            </div>
        `);
    });

    producto.etiquetas?.forEach(t => {
        selectedDiv.append(`
            <span class="badge bg-primary text-white d-inline-flex align-items-center">
                ${t.nombre}
                <button type="button" class="btn-close btn-close-white btn-sm ms-2 remove-tag" data-id="${t.id}"></button>
            </span>
        `);
    });
}

function setupEventosEtiquetasVariable() {
    $(document).off('change', '#variable_availableTags .tag-available').on('change', '#variable_availableTags .tag-available', function() {
        const tagId = parseInt(this.value);
        if (this.checked) {
            const tag = _ETIQUETAS.find(e => e.id === tagId);
            if (!editState.productoActual.etiquetas) editState.productoActual.etiquetas = [];
            if (!editState.productoActual.etiquetas.some(e => e.id === tagId)) {
                editState.productoActual.etiquetas.push(tag);
            }
        } else {
            editState.productoActual.etiquetas = editState.productoActual.etiquetas.filter(e => e.id !== tagId);
        }
        renderEtiquetasVariable(editState.productoActual);
    });

    $(document).off('click', '#variable_selectedTags .remove-tag').on('click', '#variable_selectedTags .remove-tag', function() {
        const tagId = parseInt($(this).data('id'));
        editState.productoActual.etiquetas = editState.productoActual.etiquetas.filter(e => e.id !== tagId);
        $(`#variable_availableTags .tag-available[value="${tagId}"]`).prop('checked', false);
        renderEtiquetasVariable(editState.productoActual);
    });

    $('#variable_btnAddTag').off('click').on('click', async function() {
        const val = $('#variable_tagInput').val().trim();
        if (!val) return;

        try {
            const { data } = await axios.post('productos', {
                opcion: 'CrearEtiqueta',
                nombre: val
            });

            if (data.respuesta === 'ok') {
                const nueva = data.etiqueta;
                _ETIQUETAS.push(nueva);
                
                if (!editState.productoActual.etiquetas) editState.productoActual.etiquetas = [];
                editState.productoActual.etiquetas.push(nueva);
                
                $('#variable_tagInput').val('');
                renderEtiquetasVariable(editState.productoActual);
                
                Swal.fire('Etiqueta creada', `"${nueva.nombre}" fue agregada`, 'success');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Error al crear la etiqueta', 'error');
        }
    });
}

// ============================================
// CONFIGURACIÓN DE ATRIBUTOS (CON CHECKBOX DE VARIACIÓN)
// ============================================

function configurarAtributosVariable(producto) {
    const container = document.getElementById('variable_atributosContainer');
    if (!container) return;

    container.innerHTML = '';

    // Crear un nuevo array para los atributos procesados
    const atributosProcesados = [];
    
    console.log('🔍 Atributos recibidos del servidor:', producto.atributos);
    
    // Procesar CADA atributo
    (producto.atributos || []).forEach(pa => {
        console.log(`📝 Procesando atributo: ${pa.nombre}`, {
            pivot: pa.pivot,
            visible_raw: pa.pivot?.visible,
            variacion_raw: pa.pivot?.variacion
        });
        
        // Obtener valores del pivot
        const visibleValue = pa.pivot?.visible;
        const variacionValue = pa.pivot?.variacion;
        
        // Convertir a booleano: true si es 1, "1", o true
        const visible = visibleValue === 1 || visibleValue === "1" || visibleValue === true;
        const variacion = variacionValue === 1 || variacionValue === "1" || variacionValue === true;
        
        console.log(`📊 Valores convertidos para ${pa.nombre}:`, {
            visible,
            variacion
        });
        
        // Agregar el atributo procesado
        atributosProcesados.push({
            id: pa.id,
            nombre: pa.nombre,
            slug: pa.slug,
            terminos: (pa.terminos || []).map(t => ({
                id: t.id,
                nombre: t.nombre,
                slug: t.slug
            })),
            visible: visible,
            variacion: variacion
        });
    });

    // Asignar los atributos procesados al editState
    editState.productoActual.atributos = atributosProcesados;

    console.log('✅ Estado final de atributos en editState:', 
        editState.productoActual.atributos.map(a => ({
            nombre: a.nombre,
            visible: a.visible,
            variacion: a.variacion
        }))
    );

    // Selector de atributos existentes
    let selectWrap = document.createElement('div');
    selectWrap.className = 'atributos-select-wrap mb-3';
    selectWrap.innerHTML = `
        <label class="form-label">Seleccionar atributo existente</label>
        <select class="form-select mb-2" id="variable_select_atributo">
            <option value="">-- Seleccione --</option>
            ${_ATRIBUTOS.map(a => `<option value="${a.id}">${escapeHtml(a.nombre)}</option>`).join('')}
        </select>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="abrirModalCrearAtributoVariable()">
                <i class="bi bi-plus-circle"></i> Crear nuevo atributo
            </button>
        </div>
        <div id="variable_atributosBlocks" class="mt-3"></div>
    `;
    container.appendChild(selectWrap);

    selectWrap.querySelector('#variable_select_atributo').addEventListener('change', function() {
        const id = this.value;
        if (!id) return;
        const atributo = _ATRIBUTOS.find(a => String(a.id) === String(id));
        if (atributo) addAtributoBlockVariable(atributo);
        this.value = '';
    });

    renderAtributoBlocksVariable();
}

function renderAtributoBlocksVariable() {
    const blocks = document.getElementById('variable_atributosBlocks');
    if (!blocks) return;

    blocks.innerHTML = '';

    (editState.productoActual.atributos || []).forEach(attr => {
        const block = createAtributoBlockVariable(attr);
        blocks.appendChild(block);
    });
}

function createAtributoBlockVariable(attr) {
    const attrFull = _ATRIBUTOS.find(x => String(x.id) === String(attr.id));

    const wrapper = document.createElement('div');
    wrapper.className = 'atributo-block border rounded p-3 mt-3 mb-3 shadow-sm bg-white';
    wrapper.dataset.atributoId = attr.id;
    const isVisible = attr.visible === true;
    const isVariacion = attr.variacion === true;

    wrapper.innerHTML = `
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="mb-1 fw-bold">${escapeHtml(attr.nombre)}</h6>
                <span class="badge bg-light text-secondary border small">${escapeHtml(attr.slug)}</span>
            </div>
            <div class="d-flex flex-column gap-1 text-end">
                <div class="form-check form-switch">
                    <input class="form-check-input chk-visible" type="checkbox" ${isVisible  ? 'checked' : ''}>
                    <label class="form-check-label small">Visible</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input chk-variacion" type="checkbox" ${isVariacion ? 'checked' : ''}>
                    <label class="form-check-label small">Variación</label>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <button type="button" class="btn btn-sm btn-outline-primary btnCrearValorVariable me-2">
                <i class="bi bi-plus-circle"></i> Crear valor
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary btnSelectAll">
                <i class="bi bi-check2-square"></i> Seleccionar todos
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2 btnEliminarAtributo">
                <i class="bi bi-trash"></i> Quitar atributo
            </button>
        </div>

        <div class="valores-area">
            <div class="small fw-semibold mb-2">Valores seleccionados:</div>
            <div class="valores-chips d-flex flex-wrap gap-2 mb-3"></div>
            <div class="small text-muted">Valores disponibles:</div>
            <div class="valores-available d-flex flex-wrap gap-2 mt-2"></div>
        </div>
    `;

    // Render valores disponibles
    const availDiv = wrapper.querySelector('.valores-available');
    if (availDiv && attrFull?.terminos) {
        attrFull.terminos.forEach(term => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-info btn-termino';
            btn.dataset.termId = term.id;
            btn.textContent = term.nombre;
            btn.addEventListener('click', () => {
                if (!attr.valoresSeleccionados) attr.valoresSeleccionados = [];
                
                if (!attr.valoresSeleccionados.some(v => v.id === term.id)) {
                    attr.valoresSeleccionados.push({
                        id: term.id,
                        nombre: term.nombre,
                        slug: term.slug
                    });
                    attr.terminos = attr.valoresSeleccionados;
                    renderAtributoBlocksVariable();
                }
            });
            availDiv.appendChild(btn);
        });
    }

    // Render chips seleccionados
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
            renderAtributoBlocksVariable();
        });
        chipsDiv.appendChild(chip);
    });

    // Eventos
    wrapper.querySelector('.btnEliminarAtributo').addEventListener('click', () => {
        editState.productoActual.atributos = editState.productoActual.atributos.filter(a => a.id !== attr.id);
        renderAtributoBlocksVariable();
    });

    wrapper.querySelector('.btnSelectAll').addEventListener('click', () => {
        attr.terminos = (attrFull?.terminos || []).map(t => ({
            id: t.id, nombre: t.nombre, slug: t.slug
        }));
        renderAtributoBlocksVariable();
    });

    wrapper.querySelector('.btnCrearValorVariable').addEventListener('click', () => {
        abrirModalCrearValorVariable(attr.id);
    });

    const chkVisible = wrapper.querySelector('.chk-visible');
    const chkVariacion = wrapper.querySelector('.chk-variacion');

    if (chkVisible) {
        chkVisible.addEventListener('change', (e) => {
            attr.visible = e.target.checked;
        });
    }

    if (chkVariacion) {
        chkVariacion.addEventListener('change', (e) => {
            attr.variacion = e.target.checked;
        });
    }

    return wrapper;
}

async function abrirModalCrearAtributoVariable() {
    const modalVariable = bootstrap.Modal.getInstance(document.getElementById('modalProductoVariable'));
    if (modalVariable) modalVariable.hide();

    const { value: formValues } = await Swal.fire({
        title: 'Nuevo atributo',
        html: `
            <label class="form-label">Nombre del atributo</label>
            <input id="swal_attr_nombre" class="form-control mb-3" placeholder="Ej: Color, Tamaño" autofocus>
            <label class="form-label">Slug (opcional)</label>
            <input id="swal_attr_slug" class="form-control" placeholder="Ej: color, tamano">
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        preConfirm: () => {
            const nombre = document.getElementById('swal_attr_nombre').value.trim();
            if (!nombre) {
                Swal.showValidationMessage('El nombre es requerido');
                return false;
            }
            return {
                nombre,
                slug: document.getElementById('swal_attr_slug').value.trim()
            };
        }
    });

    if (modalVariable) setTimeout(() => modalVariable.show(), 100);
    if (!formValues) return;

    try {
        const { data } = await axios.post('productos', {
            opcion: 'CrearAtributo',
            ...formValues
        });

        if (data.respuesta === 'ok') {
            const nuevoAtributo = data.atributo;
            _ATRIBUTOS.push(nuevoAtributo);
            
            if (!editState.productoActual.atributos) editState.productoActual.atributos = [];
            
            if (!editState.productoActual.atributos.some(a => a.id === nuevoAtributo.id)) {
                editState.productoActual.atributos.push({
                    id: nuevoAtributo.id,
                    nombre: nuevoAtributo.nombre,
                    slug: nuevoAtributo.slug,
                    terminos: [],
                    visible: true,
                    variacion: false
                });
            }
            
            renderAtributoBlocksVariable();
            
            Swal.fire({
                title: '¡Atributo creado!',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo crear el atributo', 'error');
    }
}

async function abrirModalCrearValorVariable(atributoId) {
    const atributo = _ATRIBUTOS.find(a => a.id == atributoId);
    if (!atributo) return;

    const modalVariable = bootstrap.Modal.getInstance(document.getElementById('modalProductoVariable'));
    if (modalVariable) modalVariable.hide();

    const { value: formValues } = await Swal.fire({
        title: `Nuevo valor para "${atributo.nombre}"`,
        html: `
            <label class="form-label">Nombre del valor</label>
            <input id="swal_val_nombre" class="form-control mb-3" placeholder="Ej: Rojo, Grande" autofocus>
            <label class="form-label">Slug (opcional)</label>
            <input id="swal_val_slug" class="form-control mb-3" placeholder="Ej: rojo, grande">
            <label class="form-label">Descripción (opcional)</label>
            <textarea id="swal_val_desc" class="form-control" rows="2"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        preConfirm: () => {
            const nombre = document.getElementById('swal_val_nombre').value.trim();
            if (!nombre) {
                Swal.showValidationMessage('El nombre es requerido');
                return false;
            }
            return {
                atributo_id: atributoId,
                nombre,
                slug: document.getElementById('swal_val_slug').value.trim(),
                descripcion: document.getElementById('swal_val_desc').value.trim()
            };
        }
    });

    if (modalVariable) setTimeout(() => modalVariable.show(), 100);
    if (!formValues) return;

    try {
        const { data } = await axios.post('productos', {
            opcion: 'CrearValorAtributo',
            ...formValues
        });

        if (data.respuesta === 'ok') {
            const nuevoValor = data.termino;
            
            const attrGlobal = _ATRIBUTOS.find(a => a.id == atributoId);
            if (attrGlobal) {
                if (!attrGlobal.terminos) attrGlobal.terminos = [];
                attrGlobal.terminos.push(nuevoValor);
            }
            
            const attrProducto = editState.productoActual.atributos?.find(a => a.id == atributoId);
            if (attrProducto) {
                if (!attrProducto.terminos) attrProducto.terminos = [];
                if (!attrProducto.terminos.some(t => t.id === nuevoValor.id)) {
                    attrProducto.terminos.push({
                        id: nuevoValor.id,
                        nombre: nuevoValor.nombre,
                        slug: nuevoValor.slug
                    });
                }
            }
            
            renderAtributoBlocksVariable();
            
            Swal.fire({
                title: '¡Valor creado!',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo crear el valor', 'error');
    }
}

function addAtributoBlockVariable(atributoObj) {
    if (!editState.productoActual.atributos) {
        editState.productoActual.atributos = [];
    }

    if (editState.productoActual.atributos.some(x => x.id === atributoObj.id)) {
        Swal.fire('Info', 'Este atributo ya fue agregado', 'info');
        return;
    }

    editState.productoActual.atributos.push({
        id: atributoObj.id,
        nombre: atributoObj.nombre,
        slug: atributoObj.slug,
        terminos: [],
        visible: true,
        variacion: false
    });

    renderAtributoBlocksVariable();
}

// ============================================
// CONFIGURACIÓN DE INVENTARIO
// ============================================

function configurarInventarioVariable(producto) {
    $('#variable_sku').val(producto.sku || '');
    $('#variable_gestion_inventario').prop('checked', !!producto.gestion_inventario);
    $('#variable_stock').val(producto.stock || 0);
    $('#variable_vendido_individualmente').prop('checked', !!producto.vendido_individualmente);

    toggleInventarioDetallesVariable(!!producto.gestion_inventario);

    $('#variable_gestion_inventario').off('change').on('change', function() {
        toggleInventarioDetallesVariable(this.checked);
    });
}

function toggleInventarioDetallesVariable(mostrar) {
    const $detalles = $('#variable_inventario_detalles');
    if (mostrar) {
        $detalles.slideDown();
    } else {
        $detalles.slideUp();
    }
}

// ============================================
// CONFIGURACIÓN DE ENVÍO
// ============================================

function configurarEnvioVariable(producto) {
    $('#variable_peso').val(producto.peso || '');
    $('#variable_peso_unidad').val(producto.peso_unidad || 'kg');
    $('#variable_longitud').val(producto.longitud || '');
    $('#variable_anchura').val(producto.anchura || '');
    $('#variable_altura').val(producto.altura || '');
}

// ============================================
// CONFIGURACIÓN DE AVANZADO
// ============================================

function configurarAvanzadoVariable(producto) {
    $('#variable_nota_interna').val(producto.nota_interna || '');
    $('#variable_valoraciones').prop('checked', producto.permite_valoraciones ?? true);
}

// ============================================
// CONFIGURACIÓN DE RELACIONADOS
// ============================================

function configurarRelacionadosVariable() {
    setupProductoRelacionadoSearchVariable('#variable_upsells', 'upsells');
    setupProductoRelacionadoSearchVariable('#variable_crosssells', 'crosssells');
    loadExistingTagsVariable('upsells');
    loadExistingTagsVariable('crosssells');
}

function setupProductoRelacionadoSearchVariable(selector, type) {
    const $input = $(selector);
    if (!$input.length) return;

    const containerId = `variable_${type}-tags-container`;
    let $container = $(`#${containerId}`);
    
    if ($container.length === 0) {
        $container = $(`<div id="${containerId}" class="tag-container d-flex flex-wrap gap-2 mt-2 mb-3"></div>`);
        $input.after($container);
    } else {
        $container.empty();
    }

    const dropdownClass = `variable-dropdown-${type}`;
    let $dropdown = $(`.${dropdownClass}`);
    
    if ($dropdown.length === 0) {
        $dropdown = $(`
            <div class="dropdown-menu p-0 shadow ${dropdownClass}" 
                 style="display: none; max-height: 250px; overflow-y: auto; width: 100%;">
            </div>
        `);
    } else {
        $dropdown.empty();
    }

    if (!$input.parent().hasClass('position-relative')) {
        $input.wrap('<div class="position-relative"></div>');
    }

    if ($dropdown.parent().length === 0) {
        $input.after($dropdown);
    }

    $input.off('input').on('input', function() {
        clearTimeout(window[`${type}Timeout`]);
        const term = $(this).val().trim();
        
        if (!term || term.length < 2) {
            $dropdown.hide();
            return;
        }

        $dropdown.html('<div class="dropdown-item text-muted">Buscando...</div>');
        positionDropdownVariable($dropdown, $input);
        $dropdown.show();

        window[`${type}Timeout`] = setTimeout(async () => {
            try {
                const productoId = $('#variable_id').val();
                const { data } = await axios.post('productos', {
                    opcion: 'Buscar',
                    query: term,
                    producto_id: productoId
                });

                if (data.respuesta === 'ok' && data.productos?.length > 0) {
                    renderDropdownVariable(data.productos, type, $dropdown);
                    positionDropdownVariable($dropdown, $input);
                } else {
                    $dropdown.html('<div class="dropdown-item text-muted">No se encontraron productos</div>');
                }
            } catch (err) {
                console.error(err);
                $dropdown.html('<div class="dropdown-item text-danger">Error en la búsqueda</div>');
            }
        }, 400);
    });

    function positionDropdownVariable($dropdown, $input) {
        const inputHeight = $input.outerHeight();
        $dropdown.css({
            position: 'absolute',
            top: inputHeight + 5,
            left: 0,
            width: $input.outerWidth(),
            zIndex: 1060
        });
    }

    $(document).off(`click.variable-${type}`).on(`click.variable-${type}`, function(e) {
        if (!$(e.target).closest($input).length && !$(e.target).closest($dropdown).length) {
            $dropdown.hide();
        }
    });

    $(window).off(`resize.variable-${type}`).on(`resize.variable-${type}`, function() {
        if ($dropdown.is(':visible')) {
            positionDropdownVariable($dropdown, $input);
        }
    });
}

function renderDropdownVariable(productos, type, $dropdown) {
    $dropdown.empty();
    
    const selectedIds = editState[type]?.map(p => p.id) || [];
    const productosFiltrados = productos.filter(p => !selectedIds.includes(p.id));
    
    if (productosFiltrados.length === 0) {
        $dropdown.html('<div class="dropdown-item text-muted">No hay productos disponibles</div>');
        return;
    }

    productosFiltrados.forEach(prod => {
        const $item = $(`
            <button type="button" class="dropdown-item text-start py-2">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div>${escapeHtml(prod.nombre)}</div>
                        <div class="small text-muted">SKU: ${prod.sku || 'N/A'} • ID: ${prod.id}</div>
                    </div>
                    <i class="bi bi-plus-circle ms-2 text-primary"></i>
                </div>
            </button>
        `);
        
        $item.on('click', function(e) {
            e.preventDefault();
            addProductTagVariable(prod, type);
            $dropdown.hide();
        });
        
        $dropdown.append($item);
    });
}

function addProductTagVariable(producto, type) {
    if (!editState[type]) editState[type] = [];
    
    if (editState[type].some(p => p.id === producto.id)) {
        Swal.fire('Info', 'Este producto ya está agregado', 'info');
        return;
    }

    editState[type].push({
        id: producto.id,
        nombre: producto.nombre,
        sku: producto.sku || 'N/A'
    });

    updateTagsVariable(type);
    $(`#variable_${type}`).val('');
}

function updateTagsVariable(type) {
    const containerId = `variable_${type}-tags-container`;
    const $container = $(`#${containerId}`);
    
    if (!$container.length) return;
    
    $container.empty();
    
    if (!editState[type]?.length) {
        $container.html('<span class="text-muted small">No hay productos seleccionados</span>');
        return;
    }

    editState[type].forEach(producto => {
        const $tag = $(`
            <span class="badge bg-primary text-white border-0 px-3 py-2 d-inline-flex align-items-center mb-2 me-2">
                <span class="me-1 text-truncate" style="max-width: 150px;">
                    ${escapeHtml(producto.nombre)}
                </span>
                <small class="me-1 opacity-75">(${producto.sku})</small>
                <button type="button" class="btn-close btn-close-white btn-sm ms-1 remove-tag" 
                        data-id="${producto.id}" data-type="${type}"></button>
            </span>
        `);
        $container.append($tag);
    });
}

function loadExistingTagsVariable(type) {
    updateTagsVariable(type);
}

$(document).off('click.remove-tag-variable').on('click.remove-tag-variable', '.remove-tag', function() {
    const productId = parseInt($(this).data('id'));
    const type = $(this).data('type');
    
    if (editState[type]) {
        editState[type] = editState[type].filter(p => p.id !== productId);
        updateTagsVariable(type);
    }
});

// ============================================
// CONFIGURACIÓN DE VARIACIONES
// ============================================

function renderVariacionesVariable() {
    const container = document.getElementById('variable_variacionesContainer');
    if (!container) return;

    container.innerHTML = '';

    if (!editState.variaciones?.length) {
        container.innerHTML = '<div class="text-muted text-center py-4">No hay variaciones. Genera usando los botones superiores.</div>';
        return;
    }

    editState.variaciones.forEach((variacion, index) => {
        const variacionRow = createVariacionRowVariable(variacion, index);
        container.appendChild(variacionRow);
    });

    // Botones de generación
    $('#variable_btnGenerateVariations').off('click').on('click', () => generarVariacionesVariable());
    $('#variable_btnGenerateManual').off('click').on('click', () => agregarVariacionManualVariable());
}

function createVariacionRowVariable(variacion, index) {
    const wrapper = document.createElement('div');
    wrapper.className = 'border rounded mb-3 bg-white shadow-sm';
    wrapper.dataset.variationIndex = index;

    // Obtener atributos marcados como variación
    const activeAtrs = (editState.productoActual.atributos || []).filter(a => a.variacion && a.terminos?.length);

    // Generar selects HTML para los atributos de variación
    // En la generación de selectsHtml, modifica la condición selected
    const selectsHtml = activeAtrs.map((a, i) => {
        const selectedTerm = variacion.atributos?.find(attr => String(attr.atrId) === String(a.id));
        const options = (a.terminos || []).map(t => `
            <option value="${t.id}" ${selectedTerm && String(selectedTerm.termId) === String(t.id) ? 'selected' : ''}>
                ${escapeHtml(t.nombre)}
            </option>
        `).join('');
        
        return `
            <div class="me-2" style="min-width:120px;">
                <label class="form-label small mb-1">${escapeHtml(a.nombre)}</label>
                <select class="form-select form-select-sm variation-attr" data-atr-id="${a.id}">
                    <option value="0" ${!selectedTerm || selectedTerm.termId === null ? 'selected' : ''}>Cualquier ${escapeHtml(a.nombre)}</option>
                    ${options}
                </select>
            </div>
        `;
    }).join('');

    // Si no hay atributos de variación, mostrar mensaje
    if (!selectsHtml) {
        wrapper.innerHTML = `
            <div class="p-3 text-muted">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No hay atributos marcados como variación. Ve a la pestaña "Atributos" y marca al menos uno como variación.
            </div>
        `;
        return wrapper;
    }

    // Construir HTML completo de la variación
    wrapper.innerHTML = `
        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded-top flex-wrap">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                ${selectsHtml}
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-body" aria-expanded="false">
                    <i class="bi bi-chevron-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-variation">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        <div class="variation-body collapse">
            <div class="p-3">
                <!-- ID oculto para variaciones existentes -->
                ${variacion.id ? `<input type="hidden" class="variation-id" value="${variacion.id}">` : ''}
                
                <!-- Imágenes y SKU -->
                <div class="d-flex gap-3 align-items-start mb-3">
                    <div class="variation-images" style="max-width: 300px;">
                        <label class="form-label small mb-1 d-block">Imágenes (máx 6)</label>
                        <input type="file" class="form-control form-control-sm variation-image-input" accept="image/*" multiple>
                        <div class="image-preview mt-2 d-flex flex-wrap gap-2"></div>
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label small mb-1">SKU</label>
                        <input type="text" class="form-control form-control-sm variation-sku" placeholder="SKU" value="${escapeHtml(variacion.sku || '')}">
                    </div>
                </div>

                <!-- Precios -->
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Precio normal</label>
                        <input type="number" step="0.01" class="form-control form-control-sm variation-price-normal" value="${variacion.price_normal || ''}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Precio rebajado</label>
                        <input type="number" step="0.01" class="form-control form-control-sm variation-price-sale" value="${variacion.price_sale || ''}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1 d-flex align-items-center gap-2">
                            <input type="checkbox" class="form-check-input schedule-sale" ${variacion.sale_start || variacion.sale_end ? 'checked' : ''}> Reprogramar
                        </label>
                        <div class="schedule-dates mt-1 ${variacion.sale_start || variacion.sale_end ? '' : 'd-none'}">
                            <input type="date" class="form-control form-control-sm variation-sale-start mb-1" value="${variacion.sale_start || ''}">
                            <input type="date" class="form-control form-control-sm variation-sale-end" value="${variacion.sale_end || ''}">
                        </div>
                    </div>
                </div>

                <!-- Stock y Backorder -->
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Cantidad en inventario</label>
                        <input type="number" class="form-control form-control-sm variation-stock" value="${variacion.stock || 0}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1 d-block">¿Permitir reservas?</label>
                        <div class="d-flex gap-2">
                            <div class="form-check">
                                <input class="form-check-input allow-backorder" type="radio" name="backorder_${index}" value="no" ${variacion.backorder !== 'yes' ? 'checked' : ''}>
                                <label class="form-check-label small">No</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input allow-backorder" type="radio" name="backorder_${index}" value="yes" ${variacion.backorder === 'yes' ? 'checked' : ''}>
                                <label class="form-check-label small">Sí</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peso y dimensiones -->
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Peso</label>
                        <input type="number" class="form-control form-control-sm variation-weight" value="${variacion.weight || ''}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Unidad</label>
                        <select class="form-select form-select-sm variation-weight-type">
                            <option value="kg" ${variacion.weight_type === 'kg' ? 'selected' : ''}>kg</option>
                            <option value="g" ${variacion.weight_type === 'g' ? 'selected' : ''}>g</option>
                            <option value="lb" ${variacion.weight_type === 'lb' ? 'selected' : ''}>lb</option>
                            <option value="oz" ${variacion.weight_type === 'oz' ? 'selected' : ''}>oz</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1 d-block">Dimensiones (cm)</label>
                        <div class="d-flex gap-2">
                            <input type="number" class="form-control form-control-sm variation-length" placeholder="Largo" value="${variacion.length || ''}">
                            <input type="number" class="form-control form-control-sm variation-width" placeholder="Ancho" value="${variacion.width || ''}">
                            <input type="number" class="form-control form-control-sm variation-height" placeholder="Alto" value="${variacion.height || ''}">
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label class="form-label small mb-1">Descripción de la variación</label>
                    <textarea class="form-control form-control-sm variation-description" rows="2">${variacion.description || ''}</textarea>
                </div>
            </div>
        </div>
    `;

    // Configurar eventos
    const toggleBtn = wrapper.querySelector('.btn-toggle-body');
    const body = wrapper.querySelector('.variation-body');
    
    if (toggleBtn && body) {
        toggleBtn.addEventListener('click', () => {
            body.classList.toggle('show');
            const icon = toggleBtn.querySelector('i');
            icon.classList.toggle('bi-chevron-down');
            icon.classList.toggle('bi-chevron-up');
        });
    }

    // Evento eliminar
    wrapper.querySelector('.btn-remove-variation').addEventListener('click', () => {
        if (variacion.id) {
            if (!editState.variacionesAEliminar) editState.variacionesAEliminar = [];
            editState.variacionesAEliminar.push(variacion.id);
        }
        editState.variaciones.splice(index, 1);
        renderVariacionesVariable();
    });

    // Guardar cambios en tiempo real
    const variacionActual = editState.variaciones[index];

    // Atributos
    wrapper.querySelectorAll('.variation-attr').forEach(sel => {
      sel.addEventListener('change', () => {
          if (!variacionActual.atributos) variacionActual.atributos = [];
          const atrId = sel.dataset.atrId;
          const termId = sel.value;
          
          const pos = variacionActual.atributos.findIndex(a => String(a.atrId) === String(atrId));
          if (pos >= 0) {
              if (termId) {
                  variacionActual.atributos[pos].termId = termId === "0" ? null : termId;
              } else {
                  variacionActual.atributos.splice(pos, 1);
              }
          } else if (termId) {
              variacionActual.atributos.push({ 
                  atrId, 
                  termId: termId === "0" ? null : termId 
              });
          }
      });
    });

    // SKU
    wrapper.querySelector('.variation-sku').addEventListener('input', e => variacionActual.sku = e.target.value);

    // Stock
    wrapper.querySelector('.variation-stock').addEventListener('input', e => variacionActual.stock = e.target.value);

    // Precios
    wrapper.querySelector('.variation-price-normal').addEventListener('input', e => variacionActual.price_normal = e.target.value);
    wrapper.querySelector('.variation-price-sale').addEventListener('input', e => variacionActual.price_sale = e.target.value);

    // Reprogramar precios
    const scheduleCheckbox = wrapper.querySelector('.schedule-sale');
    const datesDiv = wrapper.querySelector('.schedule-dates');
    if (scheduleCheckbox && datesDiv) {
        scheduleCheckbox.addEventListener('change', () => {
            datesDiv.classList.toggle('d-none', !scheduleCheckbox.checked);
            if (!scheduleCheckbox.checked) {
                variacionActual.sale_start = '';
                variacionActual.sale_end = '';
            }
        });
    }

    wrapper.querySelector('.variation-sale-start')?.addEventListener('input', e => variacionActual.sale_start = e.target.value);
    wrapper.querySelector('.variation-sale-end')?.addEventListener('input', e => variacionActual.sale_end = e.target.value);

    // Peso y dimensiones
    wrapper.querySelector('.variation-weight').addEventListener('input', e => variacionActual.weight = e.target.value);
    wrapper.querySelector('.variation-weight-type').addEventListener('change', e => variacionActual.weight_type = e.target.value);
    wrapper.querySelector('.variation-length').addEventListener('input', e => variacionActual.length = e.target.value);
    wrapper.querySelector('.variation-width').addEventListener('input', e => variacionActual.width = e.target.value);
    wrapper.querySelector('.variation-height').addEventListener('input', e => variacionActual.height = e.target.value);

    // Descripción
    wrapper.querySelector('.variation-description').addEventListener('input', e => variacionActual.description = e.target.value);

    // Backorder
    wrapper.querySelectorAll('.allow-backorder').forEach(radio => {
        radio.addEventListener('change', () => variacionActual.backorder = radio.value);
    });

    // Imágenes
    const imgInput = wrapper.querySelector('.variation-image-input');
    const imgPreview = wrapper.querySelector('.image-preview');
    
    if (!variacionActual.images) variacionActual.images = [];

    // Renderizar imágenes existentes
    function renderImages() {
        imgPreview.innerHTML = '';
        
        if (variacionActual.images.length === 0) {
            imgPreview.innerHTML = '<span class="text-muted small">No hay imágenes</span>';
            return;
        }

        variacionActual.images.forEach(img => {
            const imgWrap = document.createElement('div');
            imgWrap.className = 'position-relative';
            imgWrap.style.width = '60px';
            imgWrap.style.height = '60px';
            
            const src = img.isExisting ? img.url : URL.createObjectURL(img.file);
            
            imgWrap.innerHTML = `
                <img src="${src}" class="img-thumbnail w-100 h-100" style="object-fit: cover;">
                <button type="button" class="btn-close position-absolute top-0 end-0 btn-remove-img" style="background: #fff; border-radius:50%;"></button>
            `;
            
            imgWrap.querySelector('.btn-remove-img').addEventListener('click', () => {
                if (img.isExisting) {
                    if (!editState.variacionesImagenesEliminar) editState.variacionesImagenesEliminar = [];
                    editState.variacionesImagenesEliminar.push(img.id);
                }
                variacionActual.images = variacionActual.images.filter(i => i !== img);
                renderImages();
            });
            
            imgPreview.appendChild(imgWrap);
        });
    }

    imgInput.addEventListener('change', e => {
        const files = Array.from(e.target.files);
        
        files.forEach(file => {
            if (variacionActual.images.length < 6) {
                if (file && file.size > 0) {
                    variacionActual.images.push({
                        file: file,
                        name: file.name,
                        isExisting: false
                    });
                    renderImages();
                }
            }
        });
        imgInput.value = '';
    });

    renderImages();

    return wrapper;
}

function generarVariacionesVariable() {
    const attrs = (editState.productoActual.atributos || []).filter(a => a.variacion && a.terminos?.length);
    
    if (attrs.length < 1) {
        Swal.fire('Atención', 'Marca al menos un atributo como variación con valores seleccionados', 'warning');
        return;
    }

    // Crear arrays de valores por atributo
    const arrays = attrs.map(a => {
        // Valores normales
        const valores = a.terminos.map(v => ({
            atrId: a.id,
            termId: v.id,
            nombre: v.nombre
        }));
        
        // Agregar la opción "Cualquier" al inicio
        return [
            { atrId: a.id, termId: null, nombre: `Cualquier ${a.nombre}` },
            ...valores
        ];
    });

    // Producto cartesiano
    function cartesian(arr) {
        return arr.reduce((a, b) => a.flatMap(d => b.map(e => d.concat([e]))), [[]]);
    }

    const combos = cartesian(arrays);

    // Crear nuevas variaciones
    const nuevasVariaciones = combos.map(combo => ({
        atributos: combo.map(c => ({ atrId: c.atrId, termId: c.termId })),
        sku: '',
        stock: 0,
        price_normal: '',
        price_sale: '',
        sale_start: '',
        sale_end: '',
        weight: '',
        weight_type: 'kg',
        length: '',
        width: '',
        height: '',
        description: '',
        backorder: 'no',
        images: []
    }));

    editState.variaciones = nuevasVariaciones;
    renderVariacionesVariable();

    Swal.fire({
        title: 'Variaciones generadas',
        text: `${nuevasVariaciones.length} variación(es) generadas`,
        icon: 'success',
        timer: 2000
    });
}

function agregarVariacionManualVariable() {
    const activos = (editState.productoActual.atributos || []).filter(a => a.variacion && a.terminos?.length);
    
    if (activos.length === 0) {
        Swal.fire('Atención', 'Debes activar al menos un atributo como variación', 'warning');
        return;
    }

    editState.variaciones.push({
        atributos: activos.map(a => ({ 
            atrId: a.id, 
            termId: null // null = "Cualquier valor"
        })),
        sku: '',
        stock: 0,
        price_normal: '',
        price_sale: '',
        sale_start: '',
        sale_end: '',
        weight: '',
        weight_type: 'kg',
        length: '',
        width: '',
        height: '',
        description: '',
        backorder: 'no',
        images: []
    });

    renderVariacionesVariable();
}

// ============================================
// CONFIGURACIÓN DE PESTAÑAS
// ============================================

function configurarPestanasVariable() {
    // Activar primera pestaña por defecto
    $('#variable-inventario').addClass('show active');
    
    // Inicializar tooltips de Bootstrap si existen
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }
}

// ============================================
// GUARDAR PRODUCTO VARIABLE
// ============================================

$(document).on('click', '#btnGuardarVariable', function(e) {
    e.preventDefault();
    guardarProductoVariable();
});

async function guardarProductoVariable() {
    // Validar campos obligatorios
    if (!validarProductoVariable()) {
        return;
    }

    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    const formData = new FormData();

    // --- Datos básicos ---
    formData.append('opcion', 'Editar_Variable');
    formData.append('id', $('#variable_id').val());
    formData.append('nombre', $('#variable_nombre').val());
    formData.append('marca', $('#variable_marca').val());
    formData.append('descripcion', $('#variable_descripcion').val());
    formData.append('estado', $('#variable_estado').val());
    formData.append('tipo_producto', 'variable');
    formData.append('subcategoria_id', $('input[name="subcategoria_id"]:checked').val());

    // --- Inventario ---
    formData.append('sku', $('#variable_sku').val() || '');
    formData.append('gestion_inventario', $('#variable_gestion_inventario').is(':checked'));
    formData.append('stock', $('#variable_stock').val() || 0);
    formData.append('vendido_individualmente', $('#variable_vendido_individualmente').is(':checked'));

    // --- Envío ---
    formData.append('peso', $('#variable_peso').val() || '');
    formData.append('peso_unidad', $('#variable_peso_unidad').val() || 'kg');
    formData.append('longitud', $('#variable_longitud').val() || '');
    formData.append('anchura', $('#variable_anchura').val() || '');
    formData.append('altura', $('#variable_altura').val() || '');

    // --- Avanzado ---
    formData.append('nota_interna', $('#variable_nota_interna').val() || '');
    formData.append('permite_valoraciones', $('#variable_valoraciones').is(':checked'));

    // --- MINIATURA ---
    if (editState.eliminarMiniatura) {
        formData.append('eliminar_miniatura', 'true');
    }
    if (editState.nuevaMiniatura) {
        formData.append('imagen_miniatura', editState.nuevaMiniatura);
    }

    // --- IMÁGENES PRINCIPALES ---
    if (editState.imagenesAEliminar?.length > 0) {
        formData.append('imagenes_eliminar', JSON.stringify(editState.imagenesAEliminar));
    }
    if (editState.imagenesNuevas?.length > 0) {
        editState.imagenesNuevas.forEach(imgData => {
            formData.append('imagenes[]', imgData.file);
        });
    }

    // --- ETIQUETAS ---
    const etiquetasIds = editState.productoActual.etiquetas?.map(e => e.id) || [];
    formData.append('etiquetas', JSON.stringify(etiquetasIds));

    // --- ATRIBUTOS (con visible y variacion) ---
    if (editState.productoActual.atributos?.length > 0) {
        const atributosParaEnviar = editState.productoActual.atributos.map(attr => ({
            atributo_id: attr.id,
            valores: attr.valoresSeleccionados?.map(v => v.id) || [], 
            visible: attr.visible ?? true,
            variacion: attr.variacion ?? false
        }));
        formData.append('atributos', JSON.stringify(atributosParaEnviar));
    } else {
        formData.append('atributos', JSON.stringify([]));
    }

    // --- VARIACIONES ---
    if (editState.variaciones?.length > 0) {
        const variacionesParaEnviar = editState.variaciones.map((variacion, index) => {
            const variacionData = {
                id: variacion.id || null,
                sku: variacion.sku || '',
                stock: variacion.stock || 0,
                price_normal: variacion.price_normal || '',
                price_sale: variacion.price_sale || '',
                sale_start: variacion.sale_start || '',
                sale_end: variacion.sale_end || '',
                weight: variacion.weight || '',
                weight_type: variacion.weight_type || 'kg',
                length: variacion.length || '',
                width: variacion.width || '',
                height: variacion.height || '',
                description: variacion.description || '',
                backorder: variacion.backorder || 'no',
                atributos: variacion.atributos || []
            };

            // Agregar imágenes de variación
            if (variacion.images?.length > 0) {
                variacion.images.forEach(img => {
                    if (!img.isExisting) {
                        formData.append(`variation_images_${index}[]`, img.file);
                    }
                });
            }

            return variacionData;
        });

        formData.append('variaciones', JSON.stringify(variacionesParaEnviar));
    } else {
        formData.append('variaciones', JSON.stringify([]));
    }

    // --- VARIACIONES A ELIMINAR ---
    if (editState.variacionesAEliminar?.length > 0) {
        formData.append('variaciones_eliminar', JSON.stringify(editState.variacionesAEliminar));
    }

    // --- IMÁGENES DE VARIACIONES A ELIMINAR ---
    if (editState.variacionesImagenesEliminar?.length > 0) {
        formData.append('variaciones_imagenes_eliminar', JSON.stringify(editState.variacionesImagenesEliminar));
    }

    // --- UPSELLS Y CROSSSELLS ---
    // Limpiar duplicados
    editState.upsells = eliminarDuplicados(editState.upsells || []);
    editState.crosssells = eliminarDuplicados(editState.crosssells || []);

    formData.append('upsells', JSON.stringify(editState.upsells.map(p => p.id)));
    formData.append('crosssells', JSON.stringify(editState.crosssells.map(p => p.id)));

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
                text: 'Producto variable actualizado correctamente',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalProductoVariable'));
            if (modal) modal.hide();

            $('#tablaProductos').DataTable().ajax.reload(null, false);
            limpiarEstadoVariable();

        } else {
            Swal.fire('Error', data.mensaje || 'Error al actualizar', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        Swal.close();
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
}

function eliminarDuplicados(array) {
    const seen = new Set();
    return array.filter(item => {
        if (seen.has(item.id)) return false;
        seen.add(item.id);
        return true;
    });
}

function validarProductoVariable() {
    const nombre = $('#variable_nombre').val().trim();
    if (!nombre) {
        Swal.fire('Validación', 'El nombre del producto es requerido', 'warning');
        return false;
    }

    const subcategoria = $('input[name="subcategoria_id"]:checked').val();
    if (!subcategoria) {
        Swal.fire('Validación', 'Debes seleccionar una subcategoría', 'warning');
        return false;
    }

    // Validar que haya al menos un atributo marcado como variación si hay variaciones
    if (editState.variaciones?.length > 0) {
        const atributosVariacion = (editState.productoActual.atributos || []).filter(a => a.variacion);
        if (atributosVariacion.length === 0) {
            Swal.fire('Validación', 'Para tener variaciones, debes marcar al menos un atributo como "Variación"', 'warning');
            return false;
        }

        // Validar que las variaciones tengan atributos completos
        for (let i = 0; i < editState.variaciones.length; i++) {
            const v = editState.variaciones[i];
            if (!v.atributos || v.atributos.length !== atributosVariacion.length) {
                Swal.fire('Validación', `La variación #${i+1} no tiene todos los atributos seleccionados`, 'warning');
                return false;
            }
            // Ya no validamos que todos tengan termId, porque null es válido (Cualquier valor)
        }
    }

    return true;
}

function limpiarEstadoVariable() {
    editState.productoActual = null;
    editState.imagenes = [];
    editState.imagenesNuevas = [];
    editState.imagenesAEliminar = [];
    editState.variaciones = [];
    editState.variacionesAEliminar = [];
    editState.variacionesImagenesEliminar = [];
    editState.upsells = [];
    editState.crosssells = [];
    editState.nuevaMiniatura = null;
    editState.eliminarMiniatura = false;

    document.getElementById('formProductoVariable')?.reset();
    $('#variable_previewContainer').empty();
    $('#variable_variacionesContainer').empty();
    $('#variable_miniImg').hide().attr('src', '');
    $('#variable_miniPlaceholder').show();
    $('#variable_removeMini').addClass('d-none');
}