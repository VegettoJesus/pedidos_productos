const editStateAgrupado = {
    productoActual: null,
    imagenes: [],
    imagenesNuevas: [],
    imagenesAEliminar: [],
    hijos: [], 
    upsells: [],
    crosssells: [],
    nuevaMiniatura: null,
    eliminarMiniatura: false
};

async function abrirModalAgrupado(producto) {
    editStateAgrupado.productoActual = producto;
    editStateAgrupado.imagenes = producto.imagenes || [];
    editStateAgrupado.imagenesNuevas = [];
    editStateAgrupado.imagenesAEliminar = [];
    editStateAgrupado.nuevaMiniatura = null;
    editStateAgrupado.eliminarMiniatura = false;
    
    editStateAgrupado.hijos = [];
    if (producto.productos_agrupados && producto.productos_agrupados.length > 0) {
        producto.productos_agrupados.forEach(ag => {
            if (ag.producto_hijo) {
                editStateAgrupado.hijos.push({
                    id: ag.producto_hijo.id,
                    nombre: ag.producto_hijo.nombre,
                    sku: ag.producto_hijo.sku || 'N/A'
                });
            }
        });
    }

    // Cargar upsells y crosssells
    editStateAgrupado.upsells = [];
    editStateAgrupado.crosssells = [];
    
    if (producto.productos_relacionados && producto.productos_relacionados.length > 0) {
        producto.productos_relacionados.forEach(rel => {
            if (rel.pivot && rel.pivot.tipo === 'upsell') {
                editStateAgrupado.upsells.push({
                    id: rel.id,
                    nombre: rel.nombre || 'Producto no encontrado',
                    sku: rel.sku || 'N/A'
                });
            } else if (rel.pivot && rel.pivot.tipo === 'crosssell') {
                editStateAgrupado.crosssells.push({
                    id: rel.id,
                    nombre: rel.nombre || 'Producto no encontrado',
                    sku: rel.sku || 'N/A'
                });
            }
        });
    }

    // Llenar datos básicos
    $('#agrupado_id').val(producto.id);
    $('#agrupado_nombre').val(producto.nombre);
    $('#agrupado_marca').val(producto.marca || '');
    $('#agrupado_descripcion').val(producto.descripcion || '');
    $('#agrupado_estado').val(producto.estado || 'borrador');
    $('#agrupado_sku').val(producto.sku || '');
    $('#agrupado_nota_interna').val(producto.nota_interna || '');
    $('#agrupado_valoraciones').prop('checked', !!producto.permite_valoraciones);

    // Miniatura
    if (producto.imagen_miniatura) {
        $('#agrupado_miniImg').attr('src', "/" + producto.imagen_miniatura).show();
        $('#agrupado_miniPlaceholder').hide();
        $('#agrupado_removeMini').removeClass('d-none');
    }

    // Renderizar imágenes
    renderImagenesAgrupado();
    
    // Renderizar categorías
    const categoriaId = producto.subcategoria ? producto.subcategoria.id_categoria : null;
    const subcategoriaId = producto.subcategoria ? producto.subcategoria.id : null;
    renderCategoriasAgrupado(categoriaId, subcategoriaId);
    
    // Renderizar etiquetas
    renderEtiquetasAgrupado(producto);

    
    // Renderizar productos hijos
    renderHijosAgrupado();
    renderAtributosAgrupado();
    // Configurar eventos
    setupEventosImagenesAgrupado();
    setupMiniaturaEventsAgrupado();
    setupBusquedaHijosAgrupado();
    setupProductoRelacionadoSearchAgrupado('#agrupado_crosssells', 'crosssells');
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalProductoAgrupado'));
    modal.show();
}

// Renderizar productos hijos
function renderHijosAgrupado() {
    const container = $('#agrupado_hijos_container');
    container.empty();
    
    if (editStateAgrupado.hijos.length === 0) {
        container.html('<span class="text-muted small">No hay productos hijos seleccionados</span>');
        return;
    }
    
    editStateAgrupado.hijos.forEach(hijo => {
        const tag = $(`
            <span class="badge bg-primary text-white border-0 px-3 py-2 d-inline-flex align-items-center mb-2 me-2 tag-producto-hijo" 
                  data-product-id="${hijo.id}">
                <span class="me-1 text-truncate" style="max-width: 150px;">
                    ${escapeHtml(hijo.nombre)}
                </span>
                <small class="me-1 opacity-75">
                    (${hijo.sku})
                </small>
                <button type="button" class="btn-close btn-close-white btn-sm ms-1 remove-hijo-btn" 
                        data-id="${hijo.id}" title="Eliminar"></button>
            </span>
        `);
        container.append(tag);
    });
}

// Configurar búsqueda de productos hijos
function setupBusquedaHijosAgrupado() {
    const $input = $('#agrupado_buscar_hijos');
    if (!$input.length) return;
    
    // Limpiar eventos previos
    $input.off('input.hijos');
    
    // Crear dropdown si no existe
    let $dropdown = $('.dropdown-hijos');
    if ($dropdown.length === 0) {
        $dropdown = $(`
            <div class="dropdown-menu p-0 shadow dropdown-hijos" 
                 style="display: none; max-height: 250px; overflow-y: auto; width: 100%;">
            </div>
        `);
        
        if (!$input.parent().hasClass('position-relative')) {
            $input.wrap('<div class="position-relative"></div>');
        }
        $input.after($dropdown);
    }

    let timeout = null;

    $input.on('input.hijos', function() {
        clearTimeout(timeout);
        const term = $(this).val().trim();
        
        if (!term || term.length < 2) {
            $dropdown.hide();
            return;
        }

        $dropdown.html(`
            <div class="dropdown-item text-muted py-2">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span>Buscando...</span>
                </div>
            </div>
        `);
        positionDropdown($input, $dropdown);
        $dropdown.show();

        timeout = setTimeout(async () => {
            try {
                const productoId = $('#agrupado_id').val();
                
                const { data } = await axios.post('productos', {
                    opcion: 'Buscar',
                    query: term,
                    producto_id: productoId,
                    tipo: 'simple' // Solo productos simples pueden ser hijos
                });

                if (data.respuesta === 'ok' && data.productos && data.productos.length > 0) {
                    renderDropdownHijos(data.productos);
                    positionDropdown($input, $dropdown);
                    $dropdown.show();
                } else {
                    $dropdown.html(`
                        <div class="dropdown-item text-muted py-2">
                            <i class="bi bi-search me-2"></i>
                            No se encontraron productos
                        </div>
                    `);
                    positionDropdown($input, $dropdown);
                }
            } catch (err) {
                console.error('Error buscando productos:', err);
                $dropdown.html(`
                    <div class="dropdown-item text-danger py-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error en la búsqueda
                    </div>
                `);
                positionDropdown($input, $dropdown);
            }
        }, 400);
    });

    function renderDropdownHijos(productos) {
        $dropdown.empty();
        
        const selectedIds = editStateAgrupado.hijos.map(p => p.id);
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
            const nombreTruncado = prod.nombre.length > 45 ? prod.nombre.substring(0, 45) + '...' : prod.nombre;
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
                        <i class="bi bi-plus-circle ms-2 text-primary"></i>
                    </div>
                </button>
            `);
            
            $item.on('click', function(e) {
                e.preventDefault();
                addHijoAgrupado(prod);
                $dropdown.hide();
                $input.val('');
            });
            
            $dropdown.append($item);
        });
    }

    // Cerrar dropdown al hacer clic fuera
    $(document).off('click.hijos').on('click.hijos', function(e) {
        if (!$(e.target).closest($input).length && !$(e.target).closest($dropdown).length) {
            $dropdown.hide();
        }
    });

    // Tecla ESC para cerrar
    $input.on('keydown.hijos', function(e) {
        if (e.key === 'Escape') {
            $dropdown.hide();
        }
    });

    $(window).on('resize.hijos', function() {
        positionDropdown($input, $dropdown);
    });
}

// Agregar producto hijo
function addHijoAgrupado(producto) {
    // Verificar si ya existe
    const existe = editStateAgrupado.hijos.some(h => h.id === producto.id);
    if (existe) {
        Swal.fire({
            title: 'Ya agregado',
            text: 'Este producto ya está en la lista de hijos',
            icon: 'info',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }
    
    // Verificar que sea un producto simple (el backend ya debería filtrar)
    editStateAgrupado.hijos.push({
        id: producto.id,
        nombre: producto.nombre,
        sku: producto.sku || 'N/A'
    });
    
    renderHijosAgrupado();
}

// Eliminar producto hijo (evento delegado)
$(document).off('click.remove-hijo').on('click.remove-hijo', '.remove-hijo-btn', function() {
    const productId = parseInt($(this).data('id'));
    
    Swal.fire({
        title: '¿Eliminar producto hijo?',
        text: 'Este producto será removido del grupo',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            editStateAgrupado.hijos = editStateAgrupado.hijos.filter(h => h.id !== productId);
            renderHijosAgrupado();
        }
    });
});

// Renderizar etiquetas para agrupado
function renderEtiquetasAgrupado(producto) {
    const availableDiv = $('#agrupado_availableTags');
    const selectedDiv = $('#agrupado_selectedTags');

    availableDiv.html('');
    selectedDiv.html('');

    const seleccionadas = (producto.etiquetas || []).map(e => e.id);

    // Render disponibles
    _ETIQUETAS.forEach(tag => {
        const checked = seleccionadas.includes(tag.id) ? 'checked' : '';
        availableDiv.append(`
            <div class="form-check">
                <input class="form-check-input tag-available agrupado_tagCheck" type="checkbox" value="${tag.id}" id="agrupado_tag_${tag.id}" ${checked}>
                <label class="form-check-label" for="agrupado_tag_${tag.id}">
                    <span class="text-black">${tag.nombre}</span>
                </label>
            </div>
        `);
    });

    // Render chips seleccionados
    producto.etiquetas?.forEach(t => {
        selectedDiv.append(`
            <span class="badge d-inline-flex align-items-center text-black chip">
                ${t.nombre}
                <button type="button" class="btn-close btn-close-white btn-sm ms-2 remove-tag-agrupado" data-id="${t.id}"></button>
            </span>
        `);
    });

    // Evento check
    $('.agrupado_tagCheck').off('change').on('change', function() {
        const tagId = parseInt(this.value);
        const tag = _ETIQUETAS.find(e => e.id === tagId);
        if (this.checked) {
            if (!editStateAgrupado.productoActual.etiquetas) {
                editStateAgrupado.productoActual.etiquetas = [];
            }
            if (!editStateAgrupado.productoActual.etiquetas.some(e => e.id === tagId)) {
                editStateAgrupado.productoActual.etiquetas.push(tag);
            }
        } else {
            editStateAgrupado.productoActual.etiquetas = 
                editStateAgrupado.productoActual.etiquetas.filter(e => e.id !== tagId);
        }
        renderEtiquetasAgrupado(editStateAgrupado.productoActual);
    });

    // Evento eliminar desde chip
    selectedDiv.find('.remove-tag-agrupado').off('click').on('click', function() {
        const tagId = parseInt($(this).data('id'));
        editStateAgrupado.productoActual.etiquetas = 
            editStateAgrupado.productoActual.etiquetas.filter(e => e.id !== tagId);
        renderEtiquetasAgrupado(editStateAgrupado.productoActual);
    });
}

// Crear nueva etiqueta desde agrupado
$('#agrupado_btnAddTag').off('click').on('click', async function() {
    const val = $('#agrupado_tagInput').val().trim();
    if (!val) return;

    try {
        const { data } = await axios.post('productos', {
            opcion: 'CrearEtiqueta',
            nombre: val
        });

        if (data.respuesta === 'ok') {
            const nueva = data.etiqueta;
            _ETIQUETAS.push(nueva);
            
            if (!editStateAgrupado.productoActual.etiquetas) {
                editStateAgrupado.productoActual.etiquetas = [];
            }
            editStateAgrupado.productoActual.etiquetas.push(nueva);
            
            $('#agrupado_tagInput').val('');
            renderEtiquetasAgrupado(editStateAgrupado.productoActual);
            
            Swal.fire('Etiqueta creada', `"${nueva.nombre}" fue agregada correctamente`, 'success');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Error al crear la etiqueta', 'error');
    }
});

// Renderizar categorías para agrupado
function renderCategoriasAgrupado(categoriaId, subcategoriaId) {
    const catSelect = $('#agrupado_categoria');
    const subcatDiv = $('#agrupado_subcategorias');

    catSelect.html('<option value="">-- Seleccione --</option>');
    _CATEGORIAS.forEach(cat => {
        catSelect.append(`<option value="${cat.id}" ${cat.id == categoriaId ? 'selected' : ''}>${cat.nombre}</option>`);
    });

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
                    <input class="form-check-input" type="radio" name="agrupado_subcategoria_id" value="${sc.id}" id="agrupado_subcat_${sc.id}" ${isChecked}>
                    <label class="form-check-label" for="agrupado_subcat_${sc.id}">${sc.nombre}</label>
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

// Renderizar imágenes para agrupado
function renderImagenesAgrupado() {
    const prev = $('#agrupado_previewContainer');
    prev.html('');
    
    // Imágenes existentes
    editStateAgrupado.imagenes.forEach((img, index) => {
        const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative existing-image');
        preview.html(`
            <img src="/${img.imagen_path}" 
                 class="rounded border" 
                 style="width:80px;height:80px;object-fit:cover;"
                 data-index="${index}">
            <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image-agrupado" 
                    style="transform: translate(30%, -30%); padding: 0.2rem;"
                    data-index="${index}" data-type="existente"></button>
        `);
        prev.append(preview);
    });
    
    // Imágenes nuevas
    editStateAgrupado.imagenesNuevas.forEach((imgData, index) => {
        const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative new-image');
        preview.html(`
            <img src="${imgData.preview}" 
                 class="rounded border" 
                 style="width:80px;height:80px;object-fit:cover;"
                 data-index="${index}">
            <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image-agrupado" 
                    style="transform: translate(30%, -30%); padding: 0.2rem;"
                    data-index="${index}" data-type="nueva"></button>
        `);
        prev.append(preview);
    });

    if (editStateAgrupado.imagenes.length === 0 && editStateAgrupado.imagenesNuevas.length === 0) {
        prev.html('<span class="text-muted">No hay imágenes</span>');
    }

    actualizarContadorImagenesAgrupado();
}

function actualizarContadorImagenesAgrupado() {
    const totalExistentes = editStateAgrupado.imagenes ? editStateAgrupado.imagenes.length : 0;
    const totalNuevas = editStateAgrupado.imagenesNuevas ? editStateAgrupado.imagenesNuevas.length : 0;
    const total = totalExistentes + totalNuevas;
    
    let $noteSmall = $('#agrupado_imagenes').closest('.card-section').find('.note-small');
    if ($noteSmall.length === 0) {
        $noteSmall = $('<div class="note-small mt-1"></div>');
        $('#agrupado_imagenes').after($noteSmall);
    }
    
    // Botón eliminar todas
    let $removeAllBtn = $('#agrupado_removeAllImages');
    if ($removeAllBtn.length === 0) {
        $removeAllBtn = $(`
            <button type="button" id="agrupado_removeAllImages" class="btn btn-sm btn-outline-danger mt-2 d-none">
                <i class="bi bi-trash"></i> Eliminar todas las imágenes
            </button>
        `);
        $noteSmall.after($removeAllBtn);
    }
    
    if (total > 0) {
        $removeAllBtn.removeClass('d-none');
    } else {
        $removeAllBtn.addClass('d-none');
    }
    
    const $input = $('#agrupado_imagenes');
    const maxImagenes = 6;
    
    if (total >= maxImagenes) {
        $input.prop('disabled', true);
        $noteSmall
            .removeClass('text-muted')
            .addClass('text-danger fw-bold')
            .html(`<i class="bi bi-exclamation-triangle me-1"></i> Límite de ${maxImagenes} imágenes alcanzado`);
    } else {
        const espacioRestante = maxImagenes - total;
        $input.prop('disabled', false);
        $input.attr('multiple', espacioRestante > 1);
        $noteSmall
            .removeClass('text-danger fw-bold')
            .addClass('text-muted')
            .html(`
                <i class="bi bi-info-circle me-1"></i>
                Máximo ${maxImagenes} imágenes (${total}/${maxImagenes})
                ${espacioRestante > 0 ? `- Puedes agregar ${espacioRestante} más` : ''}
            `);
    }
    return total;
}

// Configurar eventos de imágenes para agrupado
function setupEventosImagenesAgrupado() {
    $('#agrupado_imagenes').off('change').on('change', function(e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;
        
        const totalImagenes = editStateAgrupado.imagenes.length + editStateAgrupado.imagenesNuevas.length + files.length;
        
        if (totalImagenes > 6) {
            Swal.fire({
                title: 'Límite excedido',
                text: `Ya tienes ${editStateAgrupado.imagenes.length + editStateAgrupado.imagenesNuevas.length} imágenes. Solo puedes agregar ${6 - (editStateAgrupado.imagenes.length + editStateAgrupado.imagenesNuevas.length)} más.`,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            $(this).val('');
            return;
        }

        Array.from(files).forEach(file => {
            if (!file.type.startsWith('image/')) {
                Swal.fire('Error', `El archivo ${file.name} no es una imagen válida`, 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(event) {
                editStateAgrupado.imagenesNuevas.push({
                    file: file,
                    preview: event.target.result,
                    name: file.name
                });
                renderImagenesAgrupado();
            };
            reader.readAsDataURL(file);
        });

        $(this).val('');
    });

    // Eliminar imagen
    $(document).off('click', '#agrupado_previewContainer .remove-image-agrupado').on('click', '#agrupado_previewContainer .remove-image-agrupado', function() {
        const index = parseInt($(this).data('index'));
        const type = $(this).data('type');
        
        Swal.fire({
            title: '¿Eliminar imagen?',
            text: type === 'existente' ? 'Esta imagen se eliminará al guardar' : 'Esta imagen será removida',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                if (type === 'existente') {
                    if (!editStateAgrupado.imagenesAEliminar) {
                        editStateAgrupado.imagenesAEliminar = [];
                    }
                    editStateAgrupado.imagenesAEliminar.push(editStateAgrupado.imagenes[index].id);
                    editStateAgrupado.imagenes.splice(index, 1);
                } else if (type === 'nueva') {
                    editStateAgrupado.imagenesNuevas.splice(index, 1);
                }
                renderImagenesAgrupado();
            }
        });
    });

    // Eliminar todas las imágenes
    $('#agrupado_removeAllImages').off('click').on('click', function() {
        const totalImagenes = editStateAgrupado.imagenes.length + editStateAgrupado.imagenesNuevas.length;
        if (totalImagenes === 0) return;
        
        Swal.fire({
            title: '¿Eliminar todas las imágenes?',
            html: `
                <div class="text-start">
                    <p>Se eliminarán:</p>
                    <ul>
                        ${editStateAgrupado.imagenes.length > 0 ? `<li>${editStateAgrupado.imagenes.length} imágenes existentes</li>` : ''}
                        ${editStateAgrupado.imagenesNuevas.length > 0 ? `<li>${editStateAgrupado.imagenesNuevas.length} imágenes nuevas</li>` : ''}
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
                if (editStateAgrupado.imagenes.length > 0) {
                    if (!editStateAgrupado.imagenesAEliminar) {
                        editStateAgrupado.imagenesAEliminar = [];
                    }
                    editStateAgrupado.imagenes.forEach(img => {
                        if (img.id) {
                            editStateAgrupado.imagenesAEliminar.push(img.id);
                        }
                    });
                }
                
                editStateAgrupado.imagenes = [];
                editStateAgrupado.imagenesNuevas = [];
                $('#agrupado_imagenes').val('');
                renderImagenesAgrupado();
                
                Swal.fire('Eliminadas', 'Todas las imágenes han sido eliminadas', 'success');
            }
        });
    });
}

// Configurar miniatura para agrupado
function setupMiniaturaEventsAgrupado() {
    const $miniPreview = $('#agrupado_miniPreview');
    const $miniInput = $('#agrupado_miniatura');
    const $miniImg = $('#agrupado_miniImg');
    const $miniPlaceholder = $('#agrupado_miniPlaceholder');
    const $removeBtn = $('#agrupado_removeMini');

    $miniPreview.off('click').on('click', function() {
        $miniInput.click();
    });

    $miniInput.off('change').on('change', function(e) {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            $miniImg.attr('src', event.target.result).show();
            $miniPlaceholder.hide();
            $removeBtn.removeClass('d-none');
            editStateAgrupado.nuevaMiniatura = file;
        };
        reader.readAsDataURL(file);
    });

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
                editStateAgrupado.eliminarMiniatura = true;
                delete editStateAgrupado.nuevaMiniatura;
            }
        });
    });
}

// Configurar búsqueda de productos relacionados para agrupado
function setupProductoRelacionadoSearchAgrupado(selector, type) {
    const $input = $(selector);
    if (!$input.length) return;

    const containerId = `agrupado-${type}-tags-container`;
    let $container = $(`#${containerId}`);
    
    if ($container.length === 0) {
        $container = $(`<div id="${containerId}" class="tag-container d-flex flex-wrap gap-2 mt-2 mb-3"></div>`);
        $input.after($container);
    } else {
        $container.empty();
    }

    const dropdownClass = `dropdown-agrupado-${type}`;
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

    $input.off('input.agrupado');
    $(window).off(`resize.agrupado-${type}`);

    let timeout = null;

    $input.on('input.agrupado', function() {
        clearTimeout(timeout);
        const term = $(this).val().trim();
        
        if (!term || term.length < 2) {
            $dropdown.hide();
            return;
        }

        $dropdown.html(`
            <div class="dropdown-item text-muted py-2">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span>Buscando...</span>
                </div>
            </div>
        `);
        positionDropdown($input, $dropdown);
        $dropdown.show();

        timeout = setTimeout(async () => {
            try {
                const productoId = $('#agrupado_id').val();
                
                const { data } = await axios.post('productos', {
                    opcion: 'Buscar',
                    query: term,
                    producto_id: productoId
                });

                if (data.respuesta === 'ok' && data.productos && data.productos.length > 0) {
                    renderDropdown(data.productos);
                    positionDropdown($input, $dropdown);
                    $dropdown.show();
                } else {
                    $dropdown.html(`
                        <div class="dropdown-item text-muted py-2">
                            <i class="bi bi-search me-2"></i>
                            No se encontraron productos
                        </div>
                    `);
                    positionDropdown($input, $dropdown);
                }
            } catch (err) {
                console.error('Error buscando productos:', err);
                $dropdown.html(`
                    <div class="dropdown-item text-danger py-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error en la búsqueda
                    </div>
                `);
                positionDropdown($input, $dropdown);
            }
        }, 400);
    });

    function renderDropdown(productos) {
        $dropdown.empty();
        
        const selectedIds = editStateAgrupado[type] ? editStateAgrupado[type].map(p => p.id) : [];
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
            const nombreTruncado = prod.nombre.length > 45 ? prod.nombre.substring(0, 45) + '...' : prod.nombre;
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
                        <i class="bi bi-plus-circle ms-2 text-primary"></i>
                    </div>
                </button>
            `);
            
            $item.on('click', function(e) {
                e.preventDefault();
                addProductTagAgrupado(prod, type);
                $dropdown.hide();
                $input.val('');
            });
            
            $dropdown.append($item);
        });
    }

    function addProductTagAgrupado(producto, type) {
        const existe = editStateAgrupado[type].some(p => p.id === producto.id);
        if (existe) {
            Swal.fire({
                title: 'Ya agregado',
                text: 'Este producto ya está en la lista',
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }

        editStateAgrupado[type].push({
            id: producto.id,
            nombre: producto.nombre,
            sku: producto.sku || 'N/A'
        });

        updateTagsAgrupado(type);
    }

    function updateTagsAgrupado(type) {
        const containerId = `agrupado-${type}-tags-container`;
        const $container = $(`#${containerId}`);
        
        if (!$container.length) return;

        $container.empty();
        
        const uniqueProducts = [];
        const seenIds = new Set();
        
        editStateAgrupado[type].forEach(producto => {
            if (!seenIds.has(producto.id)) {
                seenIds.add(producto.id);
                uniqueProducts.push(producto);
            }
        });

        editStateAgrupado[type] = uniqueProducts;
        
        uniqueProducts.forEach(producto => {
            const nombreTruncado = truncateTagText(producto.nombre, 30);
            const skuTexto = producto.sku || 'N/A';
            
            const $tag = $(`
                <span class="badge bg-primary text-white border-0 px-3 py-2 d-inline-flex align-items-center mb-2 me-2 tag-producto-agrupado" 
                      data-product-id="${producto.id}"
                      title="${escapeHtml(producto.nombre)} (${skuTexto})">
                    <span class="me-1 text-truncate" style="max-width: 150px;">
                        ${escapeHtml(nombreTruncado)}
                    </span>
                    <small class="me-1 opacity-75">
                        (${truncateTagText(skuTexto, 10)})
                    </small>
                    <button type="button" class="btn-close btn-close-white btn-sm ms-1 remove-tag-agrupado-btn" 
                            data-id="${producto.id}" data-type="${type}"
                            title="Eliminar"></button>
                </span>
            `);
            
            $container.append($tag);
        });

        if (uniqueProducts.length === 0) {
            $container.html('<span class="text-muted small">No hay productos seleccionados</span>');
        }
    }

    $(document).off(`click.agrupado-${type}`).on(`click.agrupado-${type}`, function(e) {
        if (!$(e.target).closest($input).length && !$(e.target).closest($dropdown).length) {
            $dropdown.hide();
        }
    });

    $input.on('keydown.agrupado', function(e) {
        if (e.key === 'Escape') {
            $dropdown.hide();
        }
    });

    $(window).on(`resize.agrupado-${type}`, function() {
        positionDropdown($input, $dropdown);
    });

    loadExistingTagsAgrupado(type);
}

function loadExistingTagsAgrupado(type) {
    const uniqueProducts = [];
    const seenIds = new Set();
    
    editStateAgrupado[type].forEach(producto => {
        if (!seenIds.has(producto.id)) {
            seenIds.add(producto.id);
            uniqueProducts.push(producto);
        }
    });
    
    editStateAgrupado[type] = uniqueProducts;
    
    const containerId = `agrupado-${type}-tags-container`;
    const $container = $(`#${containerId}`);
    $container.empty();
    
    uniqueProducts.forEach(producto => {
        const nombreTruncado = truncateTagText(producto.nombre, 30);
        const skuTexto = producto.sku || 'N/A';
        
        const $tag = $(`
            <span class="badge bg-primary text-white border-0 px-3 py-2 d-inline-flex align-items-center mb-2 me-2 tag-producto-agrupado">
                <span class="me-1 text-truncate" style="max-width: 150px;">
                    ${escapeHtml(nombreTruncado)}
                </span>
                <small class="me-1 opacity-75">
                    (${truncateTagText(skuTexto, 10)})
                </small>
                <button type="button" class="btn-close btn-close-white btn-sm ms-1 remove-tag-agrupado-btn" 
                        data-id="${producto.id}" data-type="${type}"></button>
            </span>
        `);
        $container.append($tag);
    });
}

// Evento para eliminar tags de relacionados
$(document).off('click.remove-tag-agrupado').on('click.remove-tag-agrupado', '.remove-tag-agrupado-btn', function() {
    const productId = parseInt($(this).data('id'));
    const type = $(this).data('type');
    
    editStateAgrupado[type] = editStateAgrupado[type].filter(p => p.id !== productId);
    
    const containerId = `agrupado-${type}-tags-container`;
    const $container = $(`#${containerId}`);
    $container.empty();
    
    if (editStateAgrupado[type].length === 0) {
        $container.html('<span class="text-muted small">No hay productos seleccionados</span>');
    } else {
        editStateAgrupado[type].forEach(producto => {
            const nombreTruncado = truncateTagText(producto.nombre, 30);
            const skuTexto = producto.sku || 'N/A';
            
            const $tag = $(`
                <span class="badge bg-primary text-white border-0 px-3 py-2 d-inline-flex align-items-center mb-2 me-2 tag-producto-agrupado">
                    <span class="me-1 text-truncate" style="max-width: 150px;">
                        ${escapeHtml(nombreTruncado)}
                    </span>
                    <small class="me-1 opacity-75">
                        (${truncateTagText(skuTexto, 10)})
                    </small>
                    <button type="button" class="btn-close btn-close-white btn-sm ms-1 remove-tag-agrupado-btn" 
                            data-id="${producto.id}" data-type="${type}"></button>
                </span>
            `);
            $container.append($tag);
        });
    }
});

// Función helper para posicionar dropdown
function positionDropdown($input, $dropdown) {
    const inputHeight = $input.outerHeight();
    $dropdown.css({
        position: 'absolute',
        top: inputHeight + 5,
        left: 0,
        width: $input.outerWidth(),
        display: 'block',
        zIndex: 1060
    });
}

// Función helper para truncar texto
function truncateTagText(text, maxLength = 25) {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// Guardar producto agrupado
// Guardar producto agrupado - VERSIÓN CORREGIDA
async function guardarProductoAgrupado() {
    // Validar campos obligatorios
    if (!validarProductoAgrupado()) {
        return;
    }
    
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    
    // Datos básicos - ¡CORREGIDO!
    formData.append('opcion', 'Editar_Agrupado'); // Coincide con el backend
    formData.append('id', $('#agrupado_id').val());
    formData.append('nombre', $('#agrupado_nombre').val());
    formData.append('marca', $('#agrupado_marca').val());
    formData.append('descripcion', $('#agrupado_descripcion').val());
    formData.append('estado', $('#agrupado_estado').val());
    formData.append('tipo_producto', 'agrupado');
    
    // ¡CORREGIDO! Usar el mismo nombre que espera el backend
    const subcategoriaId = $('input[name="agrupado_subcategoria_id"]:checked').val();
    formData.append('subcategoria_id', subcategoriaId);
    
    // SKU
    formData.append('sku', $('#agrupado_sku').val() || '');
    
    // Avanzado
    formData.append('nota_interna', $('#agrupado_nota_interna').val() || '');
    formData.append('permite_valoraciones', $('#agrupado_valoraciones').is(':checked') ? '1' : '0');
    
    // Miniatura
    if (editStateAgrupado.eliminarMiniatura) {
        formData.append('eliminar_miniatura', 'true');
    }
    if (editStateAgrupado.nuevaMiniatura) {
        formData.append('imagen_miniatura', editStateAgrupado.nuevaMiniatura);
    }
    
    // Imágenes principales
    if (editStateAgrupado.imagenesAEliminar && editStateAgrupado.imagenesAEliminar.length > 0) {
        formData.append('imagenes_eliminar', JSON.stringify(editStateAgrupado.imagenesAEliminar));
    }
    if (editStateAgrupado.imagenesNuevas && editStateAgrupado.imagenesNuevas.length > 0) {
        editStateAgrupado.imagenesNuevas.forEach((imgData) => {
            formData.append('imagenes[]', imgData.file);
        });
    }
    
    // Etiquetas
    if (editStateAgrupado.productoActual?.etiquetas && editStateAgrupado.productoActual.etiquetas.length > 0) {
        const etiquetasIds = editStateAgrupado.productoActual.etiquetas.map(e => e.id);
        formData.append('etiquetas', JSON.stringify(etiquetasIds));
    } else {
        formData.append('etiquetas', JSON.stringify([]));
    }
    
    // Atributos
    if (editStateAgrupado.productoActual?.atributos && editStateAgrupado.productoActual.atributos.length > 0) {
        const atributosParaEnviar = editStateAgrupado.productoActual.atributos.map(attr => ({
            atributo_id: attr.id,
            valores: attr.terminos ? attr.terminos.map(t => t.id) : [],
            visible: attr.visible !== false,
            variacion: false
        }));
        formData.append('atributos', JSON.stringify(atributosParaEnviar));
    } else {
        formData.append('atributos', JSON.stringify([]));
    }
    
    // Productos hijos (agrupados)
    if (editStateAgrupado.hijos && editStateAgrupado.hijos.length > 0) {
        const hijosIds = editStateAgrupado.hijos.map(h => h.id);
        formData.append('relacionados', JSON.stringify(hijosIds));
    } else {
        formData.append('relacionados', JSON.stringify([]));
    }
    
    editStateAgrupado.crosssells = editStateAgrupado.crosssells.filter((prod, index, self) => 
        index === self.findIndex(p => p.id === prod.id)
    );

    formData.append('crosssells', JSON.stringify(editStateAgrupado.crosssells.map(p => p.id)));

    // Debug - Verificar datos
    console.log('Datos a enviar:');
    console.log('subcategoria_id:', subcategoriaId);
    console.log('hijos:', editStateAgrupado.hijos);
    console.log('upsells:', editStateAgrupado.upsells);
    console.log('crosssells:', editStateAgrupado.crosssells);

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
                text: 'Producto agrupado actualizado correctamente',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalProductoAgrupado'));
            if (modal) modal.hide();
            
            $('#tablaProductos').DataTable().ajax.reload(null, false);
            
            limpiarEstadoAgrupado();
            
        } else {
            console.error('Error del servidor:', data);
            Swal.fire('Error', data.mensaje || 'Error al actualizar', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        Swal.close();
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
}

function validarProductoAgrupado() {
    const nombre = $('#agrupado_nombre').val().trim();
    if (!nombre) {
        Swal.fire('Validación', 'El nombre del producto es requerido', 'warning');
        return false;
    }
    
    const subcategoria = $('input[name="agrupado_subcategoria_id"]:checked').val();
    if (!subcategoria) {
        Swal.fire('Validación', 'Debes seleccionar una subcategoría', 'warning');
        return false;
    }
    
    return true;
}

function limpiarEstadoAgrupado() {
    editStateAgrupado.productoActual = null;
    editStateAgrupado.imagenes = [];
    editStateAgrupado.imagenesNuevas = [];
    editStateAgrupado.imagenesAEliminar = [];
    editStateAgrupado.hijos = [];
    editStateAgrupado.upsells = [];
    editStateAgrupado.crosssells = [];
    editStateAgrupado.nuevaMiniatura = null;
    editStateAgrupado.eliminarMiniatura = false;
    
    document.getElementById('formProductoAgrupado').reset();
    
    $('#agrupado_previewContainer').empty();
    $('#agrupado_miniImg').hide().attr('src', '');
    $('#agrupado_miniPlaceholder').show();
    $('#agrupado_removeMini').addClass('d-none');
    $('#agrupado_hijos_container').empty();
    
    // Limpiar contenedores de tags
    $('#agrupado-upsells-tags-container, #agrupado-crosssells-tags-container').empty();
    
    // LIMPIAR CONTENEDOR DE ATRIBUTOS
    $('#agrupado_atributosContainer').empty();
}

// Evento submit del formulario
$(document).on('submit', '#formProductoAgrupado', function(e) {
    e.preventDefault();
    guardarProductoAgrupado();
});

// Inicializar tooltips cuando se abre el modal
$('#modalProductoAgrupado').on('shown.bs.modal', function() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }
});

// Renderizar atributos para agrupado (igual que simple)
function renderAtributosAgrupado() {
    const container = document.getElementById('agrupado_atributosContainer');
    if (!container) return;

    container.innerHTML = '';

    // --- Selector de atributos existentes ---
    let selectWrap = document.createElement('div');
    selectWrap.className = 'atributos-select-wrap mb-3';
    selectWrap.innerHTML = `
        <label class="form-label">Seleccionar atributo existente</label>
        <select class="form-select mb-2" id="agrupado_select_atributo">
            <option value="">-- Seleccione --</option>
            ${_ATRIBUTOS.map(a => `<option value="${a.id}">${escapeHtml(a.nombre)}</option>`).join('')}
        </select>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="abrirModalCrearAtributoAgrupado()">
                <i class="bi bi-plus-circle"></i> Crear nuevo atributo
            </button>
        </div>
        <div id="agrupado_atributosBlocks" class="mt-3"></div>
    `;
    container.appendChild(selectWrap);

    // --- Listener para añadir atributos existentes ---
    selectWrap.querySelector('#agrupado_select_atributo').addEventListener('change', function() {
        const id = this.value;
        if (!id) return;
        const atributo = _ATRIBUTOS.find(a => String(a.id) === String(id));
        if (atributo) addAtributoBlockAgrupado(atributo);
        this.value = '';
    });

    renderAtributoBlocksAgrupado();
}

// Renderizar bloques de atributos para agrupado
function renderAtributoBlocksAgrupado() {
    const blocks = document.getElementById('agrupado_atributosBlocks');
    if (!blocks) return;

    blocks.innerHTML = '';

    (editStateAgrupado.productoActual.atributos || []).forEach(attr => {
        const block = createAtributoBlockAgrupado(attr);
        blocks.appendChild(block);
    });
}

// Crear bloque de atributo para agrupado
function createAtributoBlockAgrupado(attr) {
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
            <button type="button" class="btn btn-sm btn-outline-primary btnCrearValorAgrupado me-2">
                <i class="bi bi-plus-circle"></i> Crear valor
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary btnSelectAllAgrupado">
                <i class="bi bi-check2-square"></i> Seleccionar todos
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2 btnEliminarAtributoAgrupado">
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
                    if (!attr.terminos) attr.terminos = [];
                    attr.terminos.push({
                        id: term.id,
                        nombre: term.nombre,
                        slug: term.slug
                    });
                    renderAtributoBlocksAgrupado();
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
            renderAtributoBlocksAgrupado();
        });
        chipsDiv.appendChild(chip);
    });

    // --- Botones ---
    wrapper.querySelector('.btnEliminarAtributoAgrupado').addEventListener('click', () => {
        Swal.fire({
            title: '¿Quitar atributo?',
            text: 'Este atributo se eliminará del producto',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                editStateAgrupado.productoActual.atributos = 
                    editStateAgrupado.productoActual.atributos.filter(a => a.id !== attr.id);
                renderAtributoBlocksAgrupado();
            }
        });
    });

    wrapper.querySelector('.btnSelectAllAgrupado').addEventListener('click', () => {
        attr.terminos = (attrFull?.terminos || []).map(t => ({
            id: t.id, nombre: t.nombre, slug: t.slug
        }));
        renderAtributoBlocksAgrupado();
    });

    wrapper.querySelector('.btnCrearValorAgrupado').addEventListener('click', () => {
        abrirModalCrearValorAgrupado(attr.id);
    });

    // Checkbox de visible
    const chkVisible = wrapper.querySelector('.chk-visible');
    chkVisible.addEventListener('change', (e) => {
        attr.visible = e.target.checked;
    });

    return wrapper;
}

// Variable global para controlar si estamos en proceso de crear atributo/valor
let isCreatingAttribute = false;
let isCreatingValue = false;

// Función para abrir modal de crear atributo (CORREGIDA)
async function abrirModalCrearAtributoAgrupado() {
    // Marcar que estamos creando un atributo
    isCreatingAttribute = true;
    
    // Guardar referencia al estado actual ANTES de ocultar el modal
    const estadoActual = editStateAgrupado;
    
    // Verificar que tenemos un producto actual
    if (!estadoActual.productoActual) {
        console.error('Error: No hay producto actual en el estado');
        Swal.fire('Error', 'No se pudo identificar el producto actual', 'error');
        isCreatingAttribute = false;
        return;
    }
    
    // Guardar una copia de seguridad del producto actual
    const productoBackup = JSON.parse(JSON.stringify(estadoActual.productoActual));
    
    // Ocultar temporalmente el modal de Bootstrap
    const modalAgrupado = bootstrap.Modal.getInstance(document.getElementById('modalProductoAgrupado'));
    if (modalAgrupado) {
        modalAgrupado.hide();
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

    // Si se canceló, mostrar nuevamente el modal de Bootstrap
    if (!formValues) {
        if (modalAgrupado) {
            setTimeout(() => {
                modalAgrupado.show();
                isCreatingAttribute = false;
            }, 100);
        }
        return;
    }

    try {
        const { data } = await axios.post('productos', {
            opcion: 'CrearAtributo',
            nombre: formValues.nombre,
            slug: formValues.slug
        });

        if (data.respuesta === 'ok') {
            const nuevoAtributo = data.atributo;
            
            // Actualizar lista global
            _ATRIBUTOS.push(nuevoAtributo);
            
            // RESTAURAR el producto actual desde el backup si se perdió
            if (!editStateAgrupado.productoActual) {
                console.log('Restaurando producto actual desde backup');
                editStateAgrupado.productoActual = productoBackup;
            }
            
            // Verificar nuevamente que tenemos productoActual
            if (!editStateAgrupado.productoActual) {
                console.error('Error: No se pudo restaurar el producto actual');
                throw new Error('No se pudo restaurar el producto');
            }
            
            // Inicializar array de atributos si no existe
            if (!editStateAgrupado.productoActual.atributos) {
                editStateAgrupado.productoActual.atributos = [];
            }
            
            // Verificar si ya existe
            const yaExiste = editStateAgrupado.productoActual.atributos.some(a => a.id === nuevoAtributo.id);
            
            if (!yaExiste) {
                // Agregar el nuevo atributo al producto
                editStateAgrupado.productoActual.atributos.push({
                    id: nuevoAtributo.id,
                    nombre: nuevoAtributo.nombre,
                    slug: nuevoAtributo.slug,
                    terminos: [],
                    visible: true
                });
            }
            
            // Re-renderizar atributos
            renderAtributosAgrupado();
            
            // Seleccionar automáticamente en el dropdown
            setTimeout(() => {
                $('#agrupado_select_atributo').val(nuevoAtributo.id);
            }, 100);
            
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
        console.error('Error al crear atributo:', err);
        Swal.fire('Error', err.message || 'No se pudo crear el atributo', 'error');
    } finally {
        // Mostrar nuevamente el modal de Bootstrap
        if (modalAgrupado) {
            setTimeout(() => {
                modalAgrupado.show();
                isCreatingAttribute = false;
            }, 100);
        }
    }
}

// También corregir la función de crear valor
async function abrirModalCrearValorAgrupado(atributoId) {
    // Marcar que estamos creando un valor
    isCreatingValue = true;
    
    // Guardar referencia al estado actual
    const estadoActual = editStateAgrupado;
    
    // Verificar que tenemos un producto actual
    if (!estadoActual.productoActual) {
        console.error('Error: No hay producto actual en el estado');
        Swal.fire('Error', 'No se pudo identificar el producto actual', 'error');
        isCreatingValue = false;
        return;
    }
    
    // Guardar una copia de seguridad del producto actual
    const productoBackup = JSON.parse(JSON.stringify(estadoActual.productoActual));
    
    const atributo = _ATRIBUTOS.find(a => a.id == atributoId);
    if (!atributo) {
        Swal.fire('Error', 'Atributo no encontrado', 'error');
        isCreatingValue = false;
        return;
    }

    // Ocultar modal de Bootstrap
    const modalAgrupado = bootstrap.Modal.getInstance(document.getElementById('modalProductoAgrupado'));
    if (modalAgrupado) {
        modalAgrupado.hide();
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
            
            return {
                atributo_id: document.getElementById('swal_val_attrId').value,
                nombre,
                slug: document.getElementById('swal_val_slug').value.trim(),
                descripcion: document.getElementById('swal_val_desc').value.trim()
            };
        }
    });

    // Si se canceló, mostrar nuevamente el modal
    if (!formValues) {
        if (modalAgrupado) {
            setTimeout(() => {
                modalAgrupado.show();
                isCreatingValue = false;
            }, 100);
        }
        return;
    }

    try {
        const { data } = await axios.post('productos', {
            opcion: 'CrearValorAtributo',
            ...formValues
        });

        if (data.respuesta === 'ok') {
            const nuevoValor = data.termino;
            
            // Actualizar atributo global
            const attrGlobal = _ATRIBUTOS.find(a => a.id == formValues.atributo_id);
            if (attrGlobal) {
                if (!attrGlobal.terminos) attrGlobal.terminos = [];
                attrGlobal.terminos.push(nuevoValor);
            }
            
            // RESTAURAR el producto actual desde el backup si se perdió
            if (!editStateAgrupado.productoActual) {
                console.log('Restaurando producto actual desde backup');
                editStateAgrupado.productoActual = productoBackup;
            }
            
            // Verificar nuevamente el productoActual
            if (!editStateAgrupado.productoActual) {
                throw new Error('No se pudo restaurar el producto');
            }
            
            // Inicializar atributos si no existen
            if (!editStateAgrupado.productoActual.atributos) {
                editStateAgrupado.productoActual.atributos = [];
            }
            
            // Buscar o crear el atributo en el producto
            let atributoEnProducto = editStateAgrupado.productoActual.atributos.find(a => a.id == formValues.atributo_id);
            
            if (!atributoEnProducto) {
                atributoEnProducto = {
                    id: atributo.id,
                    nombre: atributo.nombre,
                    slug: atributo.slug,
                    terminos: [],
                    visible: true
                };
                editStateAgrupado.productoActual.atributos.push(atributoEnProducto);
            }
            
            // Inicializar terminos si no existen
            if (!atributoEnProducto.terminos) {
                atributoEnProducto.terminos = [];
            }
            
            // Agregar el nuevo valor si no existe
            const yaSeleccionado = atributoEnProducto.terminos.some(t => t.id === nuevoValor.id);
            if (!yaSeleccionado) {
                atributoEnProducto.terminos.push({
                    id: nuevoValor.id,
                    nombre: nuevoValor.nombre,
                    slug: nuevoValor.slug
                });
            }
            
            // Re-renderizar
            renderAtributoBlocksAgrupado();
            
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
        console.error('Error al crear valor:', err);
        Swal.fire('Error', err.message || 'No se pudo crear el valor', 'error');
    } finally {
        // Mostrar nuevamente el modal
        if (modalAgrupado) {
            setTimeout(() => {
                modalAgrupado.show();
                isCreatingValue = false;
            }, 100);
        }
    }
}

// MODIFICAR el evento hidden.bs.modal para NO limpiar si estamos creando atributo/valor
$('#modalProductoAgrupado').on('hidden.bs.modal', function() {
    // Solo limpiar si NO estamos en medio de crear un atributo o valor
    if (!isCreatingAttribute && !isCreatingValue) {
        limpiarEstadoAgrupado();
        $(document).off('click.hijos');
        $(window).off('resize.hijos');
        $(document).off('click.agrupado-upsells');
        $(document).off('click.agrupado-crosssells');
        $(window).off('resize.agrupado-upsells');
        $(window).off('resize.agrupado-crosssells');
    } else {
        console.log('Modal oculto pero NO se limpia el estado porque estamos creando atributo/valor');
    }
});
// Función para agregar atributo existente
function addAtributoBlockAgrupado(atributoObj) {
    if (!editStateAgrupado.productoActual.atributos) {
        editStateAgrupado.productoActual.atributos = [];
    }

    if (editStateAgrupado.productoActual.atributos.some(x => x.id === atributoObj.id)) {
        Swal.fire({
            title: 'Ya agregado',
            text: 'Ese atributo ya fue agregado al producto',
            icon: 'info',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }

    editStateAgrupado.productoActual.atributos.push({
        id: atributoObj.id,
        nombre: atributoObj.nombre,
        slug: atributoObj.slug,
        terminos: [],
        visible: true
    });

    renderAtributoBlocksAgrupado();
}