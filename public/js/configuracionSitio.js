let seccionesHome = [];
let modalConfigSeccion = null;
let sortableInstance = null;
let productosSeleccionados = []; 
let timeoutBusqueda = null;

function inicializarSortable() {
    const container = document.getElementById('listaSeccionesHome');
    
    if (!container) {
        console.error('No se encontró el contenedor listaSeccionesHome');
        return;
    }
    
    if (sortableInstance) {
        sortableInstance.destroy();
    }
    
    if (typeof Sortable === 'undefined') {
        console.error('Sortable.js no está cargado. Incluye la librería Sortable.js');
        mostrarError('Error', 'La librería de arrastre no está cargada. Recarga la página.');
        return;
    }
    
    try {
        sortableInstance = new Sortable(container, {
            animation: 150,
            handle: '.seccion-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onStart: function(evt) {
                evt.item.classList.add('sortable-dragging');
            },
            onEnd: function(evt) {
                evt.item.classList.remove('sortable-dragging');
                actualizarOrdenVisual();
            }
        });
    } catch (error) {
        console.error('Error al inicializar Sortable:', error);
    }
}

function cargarSeccionesHome() {
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'ListarHome',
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        beforeSend: function() {
            $('#listaSeccionesHome').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando secciones...</p>
                </div>
            `);
        },
        success: function(response) {
            if (response && response.success) {
                seccionesHome = response.data || [];
                renderizarSecciones();
                inicializarSortable();
            } else {
                mostrarError('Error', 'No se pudieron cargar las secciones');
            }
        },
        error: function() {
            $('#listaSeccionesHome').html(`
                <div class="text-center py-4">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                    <p class="mt-2 text-danger">Error al cargar las secciones</p>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="cargarSeccionesHome()">
                        Reintentar
                    </button>
                </div>
            `);
        }
    });
}

function renderizarSecciones() {
    const container = $('#listaSeccionesHome');
    container.empty();
    
    if (seccionesHome.length === 0) {
        container.html(`
            <div class="text-center py-4">
                <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                <p class="mt-2 text-muted">No hay secciones configuradas</p>
            </div>
        `);
        return;
    }
    
    seccionesHome.sort((a, b) => a.orden - b.orden);
    
    seccionesHome.forEach((seccion, index) => {
        const card = crearCardSeccion(seccion);
        container.append(card);
    });
    
    $('.btn-config-seccion').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const seccionId = $(this).data('id');
        abrirModalConfiguracion(seccionId);
    });
    
    $('.seccion-toggle').off('change').on('change', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const seccionId = $(this).data('id');
        const mostrar = $(this).is(':checked');
        actualizarSeccion(seccionId, { mostrar: mostrar });
    });
}

function crearCardSeccion(seccion) {
    const icono = seccion.icono || 'bi-grid-fill';
    const nombre = seccion.nombre_legible || seccion.seccion;
    const activa = seccion.mostrar;
    
    return `
        <div class="card seccion-card mb-3 border-${activa ? 'primary' : 'secondary'}" 
             data-id="${seccion.id}" data-seccion="${seccion.seccion}">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <!-- Handle para arrastrar -->
                    <div class="seccion-handle me-3" style="cursor: move;">
                        <i class="bi bi-grip-vertical text-muted" style="font-size: 1.2rem;"></i>
                    </div>
                    
                    <!-- Icono y nombre -->
                    <div class="me-3">
                        <i class="bi ${icono} fs-4 text-primary"></i>
                    </div>
                    
                    <!-- Información de la sección -->
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">${nombre}</h5>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-${activa ? 'success' : 'secondary'} me-2">
                                        <i class="bi bi-${activa ? 'eye' : 'eye-slash'} me-1"></i>
                                        ${activa ? 'Activa' : 'Inactiva'}
                                    </span>
                                    <span class="badge bg-info me-2">
                                        <i class="bi bi-${seccion.tipo === 'productos' ? 'box' : 'card-text'} me-1"></i>
                                        ${seccion.numero_elementos} elementos
                                    </span>
                                    <span class="badge bg-light text-dark badge-orden">
                                        <i class="bi bi-sort-numeric-down me-1"></i>
                                        Orden: ${seccion.orden}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="d-flex align-items-center">
                                <!-- Toggle activar/desactivar -->
                                <div class="form-check form-switch me-3">
                                    <input class="form-check-input seccion-toggle" 
                                           type="checkbox" 
                                           role="switch" 
                                           data-id="${seccion.id}"
                                           ${activa ? 'checked' : ''}>
                                </div>
                                
                                <!-- Botón configurar -->
                                <button type="button" class="btn btn-outline-primary btn-sm me-2 btn-config-seccion" 
                                        data-id="${seccion.id}"
                                        title="Configurar">
                                    <i class="bi bi-gear"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function abrirModalConfiguracion(seccionId) {
    const seccion = seccionesHome.find(s => s.id == seccionId);
    if (!seccion) {
        console.error('Sección no encontrada:', seccionId);
        return;
    }
    productosSeleccionados = [];
    $('#seccion_id').val(seccion.id);
    $('#seccion_numero_elementos').val(seccion.numero_elementos);
    $('#seccion_orden').val(seccion.orden);
    $('#seccion_mostrar').prop('checked', seccion.mostrar);
    $('#modalConfigSeccionTitle').html(`
        <i class="bi ${seccion.icono || 'bi-gear'} me-2"></i>
        Configurar: ${seccion.nombre_legible || seccion.seccion}
    `);
    
    cargarConfiguracionEspecifica(seccion);
    if (!modalConfigSeccion) {
        const modalElement = document.getElementById('modalConfigSeccion');
        if (modalElement) {
            modalConfigSeccion = new bootstrap.Modal(modalElement);
            modalElement.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                }
            });
            modalElement.addEventListener('hidden.bs.modal', function() {
                limpiarAutocomplete();
            });
        }
    }
    
    if (modalConfigSeccion) {
        modalConfigSeccion.show();
    }
}

function cargarConfiguracionEspecifica(seccion) {
    const container = $('#configEspecifica');
    const config = seccion.configuracion_json || {};
    
    let contenido = '';
    
    switch(seccion.seccion) {
        case 'destacados':
            if (config.productos_ids && Array.isArray(config.productos_ids) && config.productos_ids.length > 0) {
                cargarProductosSeleccionados(config.productos_ids);
            }
            
            contenido = `
                <div class="border-top pt-3 mt-3">
                    <h6 class="mb-3">
                        <i class="bi bi-stars me-1"></i>Configuración de Destacados
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Criterio de selección</label>
                            <select class="form-select" id="config_criterio" name="configuracion_json[criterio]">
                                <option value="manual" ${config.criterio === 'manual' ? 'selected' : ''}>Manual</option>
                                <option value="rating" ${config.criterio === 'rating' ? 'selected' : ''}>Por calificación</option>
                                <option value="ventas" ${config.criterio === 'ventas' ? 'selected' : ''}>Por ventas</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mostrar precio</label>
                            <select class="form-select" id="config_mostrar_precio" name="configuracion_json[mostrar_precio]">
                                <option value="true" ${config.mostrar_precio !== false ? 'selected' : ''}>Sí</option>
                                <option value="false" ${config.mostrar_precio === false ? 'selected' : ''}>No</option>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">Selección de productos (solo si criterio es manual)</label>
                            
                            <!-- Campo de búsqueda -->
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="buscar_producto" 
                                       placeholder="Buscar productos por nombre, SKU o ID..."
                                       ${config.criterio !== 'manual' ? 'disabled' : ''}>
                                <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusqueda" title="Limpiar búsqueda">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            
                            <!-- Lista de resultados -->
                            <div id="resultadosProductos" class="mb-3" style="display: none;">
                                <div class="card">
                                    <div class="card-header py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold">Resultados de búsqueda</span>
                                            <button type="button" class="btn btn-sm btn-link p-0" id="btnCerrarResultados">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                                        <div id="listaResultados" class="list-group list-group-flush">
                                            <!-- Los resultados se cargarán aquí -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Productos seleccionados -->
                            <div id="productosSeleccionadosContainer" class="${productosSeleccionados.length > 0 ? '' : 'd-none'}">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Productos seleccionados (${productosSeleccionados.length})
                                            </span>
                                            <button type="button" class="btn btn-sm btn-light p-0" id="btnLimpiarSeleccion" title="Limpiar selección">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                                        <div id="listaProductosSeleccionados" class="list-group list-group-flush">
                                            ${renderizarProductosSeleccionados()}
                                        </div>
                                    </div>
                                    <div class="card-footer py-2 bg-light">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Los productos se mostrarán en el orden que aparecen aquí.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campo oculto para almacenar los IDs -->
                            <input type="hidden" 
                                   id="config_productos_ids" 
                                   name="configuracion_json[productos_ids]"
                                   value="${config.productos_ids ? JSON.stringify(config.productos_ids) : '[]'}">
                        </div>
                    </div>
                </div>
            `;
            break;
            
        case 'ofertas':
            contenido = `
                <div class="border-top pt-3 mt-3">
                    <h6 class="mb-3">
                        <i class="bi bi-percent me-1"></i>Configuración de Ofertas
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Descuento mínimo (%)</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="config_descuento_minimo" 
                                   name="configuracion_json[descuento_minimo]"
                                   min="1" 
                                   max="99"
                                   value="${config.descuento_minimo || 15}">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ordenar por</label>
                            <select class="form-select" id="config_ordenar_por" name="configuracion_json[ordenar_por]">
                                <option value="porcentaje_descuento" ${config.ordenar_por === 'porcentaje_descuento' ? 'selected' : ''}>% Descuento</option>
                                <option value="precio_final" ${config.ordenar_por === 'precio_final' ? 'selected' : ''}>Precio Final</option>
                                <option value="fecha_fin" ${config.ordenar_por === 'fecha_fin' ? 'selected' : ''}>Fecha de fin</option>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="config_mostrar_temporizador" 
                                       name="configuracion_json[mostrar_temporizador]"
                                       ${config.mostrar_temporizador !== false ? 'checked' : ''}>
                                <label class="form-check-label" for="config_mostrar_temporizador">
                                    Mostrar temporizador de oferta
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;
            
        default:
            contenido = `
                <div class="border-top pt-3 mt-3">
                    <h6 class="mb-3">
                        <i class="bi bi-gear me-1"></i>Configuración Avanzada
                    </h6>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Esta sección no tiene configuración específica adicional.
                    </div>
                </div>
            `;
    }
    
    container.html(contenido);
    if (seccion.seccion === 'destacados') {
        inicializarAutocompleteProductos(config.criterio);
    }
}

function cargarProductosSeleccionados(ids) {
    if (!ids || !Array.isArray(ids) || ids.length === 0) {
        productosSeleccionados = [];
        return;
    }
    
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'BuscarProductos',
            excluir_ids: [],
            limite: 50,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        beforeSend: function() {
            $('#listaProductosSeleccionados').html(`
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="small mt-2">Cargando productos seleccionados...</p>
                </div>
            `);
        },
        success: function(response) {
            if (response && response.success) {
                productosSeleccionados = response.productos.filter(producto => 
                    ids.includes(producto.id)
                );
                
                productosSeleccionados.sort((a, b) => {
                    return ids.indexOf(a.id) - ids.indexOf(b.id);
                });
                
                actualizarListaProductosSeleccionados();
            } else {
                productosSeleccionados = [];
                mostrarError('Error', 'No se pudieron cargar los productos seleccionados');
            }
        },
        error: function() {
            productosSeleccionados = [];
            mostrarError('Error', 'Error al cargar los productos seleccionados');
        }
    });
}

function renderizarProductosSeleccionados() {
    if (productosSeleccionados.length === 0) {
        return `
            <div class="text-center py-3">
                <i class="bi bi-inbox text-muted" style="font-size: 1.5rem;"></i>
                <p class="small text-muted mt-2 mb-0">No hay productos seleccionados</p>
            </div>
        `;
    }
    
    return productosSeleccionados.map(producto => `
        <div class="list-group-item py-2 producto-seleccionado-item" data-id="${producto.id}">
            <div class="d-flex align-items-center">
                <!-- Imagen -->
                <div class="me-3 flex-shrink-0">
                    <img src="${producto.imagen}" 
                         alt="${producto.nombre}" 
                         class="rounded" 
                         style="width: 40px; height: 40px; object-fit: cover;">
                </div>
                
                <!-- Información -->
                <div class="flex-grow-1 me-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1 small fw-bold">${producto.nombre}</h6>
                            <div class="small text-muted">
                                <span class="me-2">ID: ${producto.id}</span>
                                <span>SKU: ${producto.sku}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            ${producto.tiene_oferta ? 
                                `<span class="badge bg-success me-1">OFERTA</span>` : 
                                ''
                            }
                            <span class="badge ${producto.tiene_oferta ? 'bg-danger' : 'bg-primary'}">
                                $${producto.tiene_oferta ? producto.precio_rebajado : producto.precio_regular}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Botón eliminar -->
                <div class="flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-producto" 
                            data-id="${producto.id}"
                            title="Eliminar producto">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function actualizarListaProductosSeleccionados() {
    const container = $('#productosSeleccionadosContainer');
    const lista = $('#listaProductosSeleccionados');
    const contador = $('#productosSeleccionadosContainer .card-header span');
    const inputHidden = $('#config_productos_ids');
    const limite = parseInt($('#seccion_numero_elementos').val()) || 8;
    
    if (contador.length) {
        contador.html(`
            <i class="bi bi-check-circle me-1"></i>
            Productos seleccionados (${productosSeleccionados.length}/${limite})
        `);
        
        if (productosSeleccionados.length > limite) {
            contador.parent().addClass('bg-warning');
            contador.find('i').removeClass('bi-check-circle').addClass('bi-exclamation-triangle');
        } else {
            contador.parent().removeClass('bg-warning');
            contador.find('i').removeClass('bi-exclamation-triangle').addClass('bi-check-circle');
        }
    }
    
    lista.html(renderizarProductosSeleccionados());
    const ids = productosSeleccionados.map(p => p.id);
    inputHidden.val(JSON.stringify(ids));
    if (productosSeleccionados.length > 0) {
        container.removeClass('d-none');
    } else {
        container.addClass('d-none');
    }
    if ($('#mensajeLimiteProductos').length) {
        actualizarMensajeLimiteProductos(limite);
    }
    $('.btn-eliminar-producto').off('click').on('click', function() {
        const productoId = parseInt($(this).data('id'));
        eliminarProductoSeleccionado(productoId);
    });
}

function inicializarAutocompleteProductos(criterioSeleccionado) {
    const campoBusqueda = $('#buscar_producto');
    const criterioSelect = $('#config_criterio');
    const resultadosContainer = $('#resultadosProductos');
    const listaResultados = $('#listaResultados');
    if (criterioSeleccionado !== 'manual') {
        campoBusqueda.prop('disabled', true);
        campoBusqueda.attr('placeholder', 'Seleccione "Manual" para buscar productos');
        $('#productosSeleccionadosContainer').addClass('d-none');
    } else {
        campoBusqueda.prop('disabled', false);
        campoBusqueda.attr('placeholder', 'Buscar productos por nombre, SKU o ID...');
    }

    $('#seccion_numero_elementos').off('change').on('change', function() {
        ajustarProductosSegunLimite();
    });

    criterioSelect.off('change').on('change', function() {
        const valor = $(this).val();
        if (valor === 'manual') {
            const limite = parseInt($('#seccion_numero_elementos').val()) || 8;
            actualizarMensajeLimiteProductos(limite);
            campoBusqueda.prop('disabled', false);
            campoBusqueda.attr('placeholder', 'Buscar productos por nombre, SKU o ID...');
            $('#productosSeleccionadosContainer').removeClass('d-none');

            if (productosSeleccionados.length > 0) {
                Swal.fire({
                    title: '¿Limpiar selección?',
                    text: 'Al cambiar de criterio se eliminarán los productos seleccionados manualmente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, limpiar',
                    cancelButtonText: 'No, mantener'
                }).then((result) => {
                    if (result.isConfirmed) {
                        productosSeleccionados = [];
                        actualizarListaProductosSeleccionados();
                    }
                });
            }
        } else {
            $('#mensajeLimiteProductos').remove();
            campoBusqueda.prop('disabled', true);
            campoBusqueda.attr('placeholder', 'Seleccione "Manual" para buscar productos');
            $('#productosSeleccionadosContainer').addClass('d-none');
            if (productosSeleccionados.length > 0) {
                Swal.fire({
                    title: 'Criterio cambiado',
                    text: 'Los productos seleccionados manualmente se han limpiado.',
                    icon: 'info',
                    timer: 1500,
                    showConfirmButton: false
                });
                productosSeleccionados = [];
                actualizarListaProductosSeleccionados();
            }
        }
    });
    
    campoBusqueda.off('input').on('input', function() {
        const termino = $(this).val().trim();
        
        if (timeoutBusqueda) {
            clearTimeout(timeoutBusqueda);
        }
        
        if (termino === '') {
            resultadosContainer.hide();
            return;
        }
        
        timeoutBusqueda = setTimeout(() => {
            buscarProductos(termino);
        }, 300);
    });
    
    $('#btnLimpiarBusqueda').off('click').on('click', function() {
        campoBusqueda.val('');
        resultadosContainer.hide();
        campoBusqueda.focus();
    });
    
    $('#btnCerrarResultados').off('click').on('click', function() {
        resultadosContainer.hide();
    });
    
    $('#btnLimpiarSeleccion').off('click').on('click', function() {
        Swal.fire({
            title: '¿Limpiar todos los productos?',
            text: 'Se eliminarán todos los productos seleccionados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                productosSeleccionados = [];
                actualizarListaProductosSeleccionados();
            }
        });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#resultadosProductos, #buscar_producto').length) {
            resultadosContainer.hide();
        }
    });
}

function buscarProductos(termino) {
    const listaResultados = $('#listaResultados');
    const resultadosContainer = $('#resultadosProductos');
    const idsExcluir = productosSeleccionados.map(p => p.id);
    
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'BuscarProductos',
            busqueda: termino,
            excluir_ids: idsExcluir,
            limite: 10,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        beforeSend: function() {
            listaResultados.html(`
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="small mt-2">Buscando productos...</p>
                </div>
            `);
            resultadosContainer.show();
        },
        success: function(response) {
            if (response && response.success && response.productos.length > 0) {
                listaResultados.html(renderizarResultadosBusqueda(response.productos));
            } else {
                listaResultados.html(`
                    <div class="text-center py-3">
                        <i class="bi bi-search text-muted" style="font-size: 1.5rem;"></i>
                        <p class="small text-muted mt-2 mb-0">No se encontraron productos</p>
                    </div>
                `);
                resultadosContainer.show();
            }
        },
        error: function() {
            listaResultados.html(`
                <div class="text-center py-3">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 1.5rem;"></i>
                    <p class="small text-danger mt-2 mb-0">Error al buscar productos</p>
                </div>
            `);
            resultadosContainer.show();
        }
    });
}

function renderizarResultadosBusqueda(productos) {
    if (productos.length === 0) {
        return '';
    }
    
    return productos.map(producto => `
        <div class="list-group-item py-2 producto-resultado-item" data-id="${producto.id}">
            <div class="d-flex align-items-center">
                <!-- Imagen -->
                <div class="me-3 flex-shrink-0">
                    <img src="${producto.imagen}" 
                         alt="${producto.nombre}" 
                         class="rounded" 
                         style="width: 40px; height: 40px; object-fit: cover;">
                </div>
                
                <!-- Información -->
                <div class="flex-grow-1 me-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1 small fw-bold">${producto.nombre}</h6>
                            <div class="small text-muted">
                                <span class="me-2">ID: ${producto.id}</span>
                                <span>SKU: ${producto.sku}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            ${producto.tiene_oferta ? 
                                `<span class="badge bg-success me-1">OFERTA</span>` : 
                                ''
                            }
                            <span class="badge ${producto.tiene_oferta ? 'bg-danger' : 'bg-primary'}">
                                $${producto.tiene_oferta ? producto.precio_rebajado : producto.precio_regular}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Botón agregar -->
                <div class="flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-outline-success btn-agregar-producto" 
                            data-id="${producto.id}"
                            data-nombre="${producto.nombre}"
                            data-sku="${producto.sku}"
                            data-imagen="${producto.imagen}"
                            data-precio="${producto.tiene_oferta ? producto.precio_rebajado : producto.precio_regular}"
                            data-tiene-oferta="${producto.tiene_oferta}"
                            title="Agregar producto">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function agregarProductoSeleccionado(productoData) {
    const numeroElementos = parseInt($('#seccion_numero_elementos').val()) || 8;
    const existe = productosSeleccionados.some(p => p.id === productoData.id);
    
    if (existe) {
        Swal.fire({
            icon: 'warning',
            title: 'Producto ya seleccionado',
            text: 'Este producto ya está en la lista de seleccionados.',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }
    
    if (productosSeleccionados.length >= numeroElementos) {
        Swal.fire({
            icon: 'warning',
            title: 'Límite alcanzado',
            html: `
                <div class="text-start">
                    <p>Ya has seleccionado el máximo de productos permitidos:</p>
                    <ul class="small mb-0">
                        <li>Máximo permitido: <strong>${numeroElementos}</strong> productos</li>
                        <li>Actualmente seleccionados: <strong>${productosSeleccionados.length}</strong></li>
                    </ul>
                    <p class="mt-2 mb-0 small">Para agregar más productos, aumenta el "Número de elementos" o elimina algunos productos de la lista.</p>
                </div>
            `,
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    productosSeleccionados.push({
        id: productoData.id,
        nombre: productoData.nombre,
        sku: productoData.sku,
        imagen: productoData.imagen,
        precio_regular: productoData.precio,
        tiene_oferta: productoData.tiene_oferta === 'true',
        precio_rebajado: productoData.tiene_oferta === 'true' ? productoData.precio : null
    });
    
    actualizarListaProductosSeleccionados();
    $('#buscar_producto').val('');
    $('#resultadosProductos').hide();
    Swal.fire({
        icon: 'success',
        title: 'Producto agregado',
        text: 'El producto ha sido agregado a la selección.',
        timer: 1500,
        showConfirmButton: false
    });
}

function ajustarProductosSegunLimite() {
    const numeroElementos = parseInt($('#seccion_numero_elementos').val()) || 8;
    const criterio = $('#config_criterio').val();
    
    if (criterio === 'manual') {
        if (productosSeleccionados.length > numeroElementos) {
            const excedente = productosSeleccionados.length - numeroElementos;
            
            Swal.fire({
                icon: 'warning',
                title: 'Ajustando productos seleccionados',
                html: `
                    <div class="text-start">
                        <p>Has reducido el "Número de elementos" a <strong>${numeroElementos}</strong>.</p>
                        <p>Actualmente tienes <strong>${productosSeleccionados.length}</strong> productos seleccionados.</p>
                        <p>Se eliminarán los últimos <strong>${excedente}</strong> productos de la lista.</p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Aceptar y eliminar',
                cancelButtonText: 'Cancelar cambio'
            }).then((result) => {
                if (result.isConfirmed) {
                    productosSeleccionados = productosSeleccionados.slice(0, numeroElementos);
                    actualizarListaProductosSeleccionados();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Productos ajustados',
                        text: `Se mantuvieron los primeros ${numeroElementos} productos seleccionados.`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    const seccionId = $('#seccion_id').val();
                    const seccion = seccionesHome.find(s => s.id == seccionId);
                    if (seccion) {
                        $('#seccion_numero_elementos').val(seccion.numero_elementos);
                    }
                }
            });
        }
        
        actualizarMensajeLimiteProductos(numeroElementos);
    }
}

function actualizarMensajeLimiteProductos(limite) {
    let mensajeElement = $('#mensajeLimiteProductos');
    
    if (mensajeElement.length === 0) {
        const container = $('#buscar_producto').closest('.mb-3');
        container.after(`
            <div class="alert alert-info py-2 mb-3 small" id="mensajeLimiteProductos">
                <i class="bi bi-info-circle me-1"></i>
                <span id="textoLimiteProductos">
                    Límite: Puedes seleccionar hasta ${limite} productos.
                </span>
            </div>
        `);
        mensajeElement = $('#mensajeLimiteProductos');
    }
    
    const texto = $('#textoLimiteProductos');
    const seleccionados = productosSeleccionados.length;
    const disponibles = Math.max(0, limite - seleccionados);
    
    if (disponibles === 0) {
        texto.html(`
            <strong>Límite alcanzado:</strong> Has seleccionado ${seleccionados}/${limite} productos. 
            No puedes agregar más productos.
        `);
        mensajeElement.removeClass('alert-info').addClass('alert-warning');
    } else {
        texto.html(`
            <strong>Límite:</strong> Puedes seleccionar hasta ${limite} productos. 
            <strong>Seleccionados:</strong> ${seleccionados}/${limite} 
            <strong>Disponibles:</strong> ${disponibles}
        `);
        mensajeElement.removeClass('alert-warning').addClass('alert-info');
    }
}

function eliminarProductoSeleccionado(productoId) {
    Swal.fire({
        title: '¿Eliminar producto?',
        text: 'Este producto será removido de la selección.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            productosSeleccionados = productosSeleccionados.filter(p => p.id !== productoId);
            actualizarListaProductosSeleccionados();
            Swal.fire({
                icon: 'success',
                title: 'Producto eliminado',
                text: 'El producto ha sido removido de la selección.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

function limpiarAutocomplete() {
    productosSeleccionados = [];
    if (timeoutBusqueda) {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = null;
    }
}

function actualizarOrdenVisual() {
    $('#listaSeccionesHome .seccion-card').each(function(index) {
        const card = $(this);
        const seccionId = card.data('id');
        const orden = index + 1;
        
        card.find('.badge-orden').html(`
            <i class="bi bi-sort-numeric-down me-1"></i>
            Orden: ${orden}
        `);
        
        const seccionIndex = seccionesHome.findIndex(s => s.id == seccionId);
        if (seccionIndex !== -1) {
            seccionesHome[seccionIndex].orden = orden;
        }
    });
    
    Swal.fire({
        icon: 'info',
        title: 'Orden actualizado',
        text: 'El orden ha sido modificado. Recuerda guardar los cambios.',
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

function validarFormulario() {
        let isValid = true;
        
        const requiredFields = ['titulo_site', 'abreviatura_titulo', 'descripcion_corta', 'email_admin', 'max_entradas_home'];
        
        requiredFields.forEach(field => {
            const element = $(`#${field}`);
            if (!element.val() || !element.val().trim()) {
                element.addClass('is-invalid');
                isValid = false;
            } else {
                element.removeClass('is-invalid');
            }
        });

        const email = $('#email_admin').val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            $('#email_admin').addClass('is-invalid');
            $('#email_admin').siblings('.invalid-feedback').text('Por favor ingrese un email válido');
            isValid = false;
        }

        const maxEntradas = parseInt($('#max_entradas_home').val());
        if (maxEntradas < 1 || maxEntradas > 50 || isNaN(maxEntradas)) {
            $('#max_entradas_home').addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            Swal.fire({
                icon: 'warning',
                title: 'Formulario incompleto',
                text: 'Por favor complete todos los campos requeridos correctamente',
                confirmButtonText: 'Entendido'
            });
        }

        return isValid;
    }

function guardarConfiguracion() {
    if (!validarFormulario()) {  
        return;
    }

    Swal.fire({
        title: '¿Guardar todos los cambios?',
        html: `
            <div class="text-start">
                <p>Se guardarán:</p>
                <ul class="small mb-0">
                    <li>✓ Configuración general del sitio</li>
                    <li>✓ Layout y orden de secciones</li>
                    <li>✓ Configuraciones específicas por sección</li>
                </ul>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar todo',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            guardarTodo();
        }
    });
}

function guardarTodo() {
    const configuracionData = {
        titulo_site: $('#titulo_site').val().trim(),
        abreviatura_titulo: $('#abreviatura_titulo').val().trim(),
        descripcion_corta: $('#descripcion_corta').val().trim(),
        icono_site: $('#icono_site').val().trim(),
        email_admin: $('#email_admin').val().trim(),
        footer_text: $('#footer_text').val().trim(),
        max_entradas_home: parseInt($('#max_entradas_home').val()) || 12,
        _token: $('meta[name="csrf-token"]').attr('content'),
        opcion: 'Guardar'
    };

    const seccionesFormatted = seccionesHome.map(seccion => {
        const configuracion = seccion.configuracion_json || {};
        if (configuracion.productos_ids && !Array.isArray(configuracion.productos_ids)) {
            try {
                configuracion.productos_ids = JSON.parse(configuracion.productos_ids);
            } catch (e) {
                configuracion.productos_ids = [];
            }
        }
        
        return {
            id: parseInt(seccion.id),
            numero_elementos: parseInt(seccion.numero_elementos),
            orden: parseInt(seccion.orden),
            mostrar: seccion.mostrar ? 1 : 0,
            configuracion_json: configuracion
        };
    });

    const layoutData = {
        secciones: seccionesFormatted,
        _token: $('meta[name="csrf-token"]').attr('content'),
        opcion: 'GuardarHome'
    };

    Swal.fire({
        title: 'Guardando...',
        html: `
            <div class="text-center">
                <div class="spinner-border mb-3" role="status"></div>
                <p class="mb-1">Guardando configuración general...</p>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         style="width: 50%"></div>
                </div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: configuracionData,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        Swal.update({
                            html: `
                                <div class="text-center">
                                    <div class="spinner-border mb-3" role="status"></div>
                                    <p class="mb-1">✓ Configuración general guardada</p>
                                    <p class="mb-1">Guardando layout del home...</p>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             style="width: 75%"></div>
                                    </div>
                                </div>
                            `
                        });
                        
                        $.ajax({
                            url: 'configuracionSitio',
                            method: 'POST',
                            data: JSON.stringify(layoutData), 
                            contentType: 'application/json',   
                            dataType: 'json',
                            success: function(responseLayout) {
                                Swal.close();
                                
                                if (responseLayout && responseLayout.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Todo guardado!',
                                        html: `
                                            <div class="text-start">
                                                <p>Todos los cambios se han guardado correctamente:</p>
                                                <ul class="small mb-0">
                                                    <li>✓ Configuración general del sitio</li>
                                                    <li>✓ Layout y orden de secciones</li>
                                                    <li>✓ Configuraciones específicas por sección</li>
                                                </ul>
                                            </div>
                                        `,
                                        timer: 3000,
                                        showConfirmButton: false
                                    });
                                    
                                    cargarConfiguracion();
                                    cargarSeccionesHome();
                                    
                                } else {
                                    mostrarError('Error al guardar layout', responseLayout?.message || 'No se pudo guardar el layout');
                                }
                            },
                            error: function(xhr) {
                                Swal.close();
                                mostrarError('Error', 'Error al guardar el layout del home: ' + xhr.responseText);
                            }
                        });
                        
                    } else {
                        Swal.close();
                        mostrarError('Error al guardar', response?.message || 'No se pudo guardar la configuración');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    mostrarError('Error', 'Error al guardar la configuración general');
                }
            });
        }
    });
}

function guardarConfiguracionSeccion() {
    const seccionId = $('#seccion_id').val();
    const numeroElementos = parseInt($('#seccion_numero_elementos').val());
    
    const data = {
        id: parseInt(seccionId),
        numero_elementos: parseInt($('#seccion_numero_elementos').val()),
        orden: parseInt($('#seccion_orden').val()),
        mostrar: $('#seccion_mostrar').is(':checked'),
        configuracion_json: {}
    };
    
    const config = {};
    const criterio = $('#config_criterio').val();
    
    const seccionExistente = seccionesHome.find(s => s.id == seccionId);
    const configExistente = seccionExistente?.configuracion_json || {};
    if (criterio === 'manual' && productosSeleccionados.length > numeroElementos) {
        Swal.fire({
            icon: 'error',
            title: 'Exceso de productos seleccionados',
            html: `
                <div class="text-start">
                    <p>No puedes guardar la configuración porque:</p>
                    <ul class="small mb-0">
                        <li>Número de elementos configurado: <strong>${numeroElementos}</strong></li>
                        <li>Productos seleccionados: <strong>${productosSeleccionados.length}</strong></li>
                        <li>Excedente: <strong>${productosSeleccionados.length - numeroElementos}</strong> productos</li>
                    </ul>
                    <p class="mt-2 mb-0 small">Por favor:</p>
                    <ol class="small mb-0">
                        <li>Aumenta el "Número de elementos" a ${productosSeleccionados.length} o más, o</li>
                        <li>Elimina algunos productos de la selección</li>
                    </ol>
                </div>
            `,
            confirmButtonText: 'Entendido'
        });
        return;
    }

    if (criterio) {
        config.criterio = criterio;
        
        if (criterio === 'manual') {
            const idsInput = $('#config_productos_ids').val();
            let productosIds = [];
            
            if (idsInput && idsInput.trim() !== '') {
                try {
                    productosIds = JSON.parse(idsInput);
                } catch (e) {
                    console.error('Error al parsear productos_ids:', e);
                    productosIds = productosSeleccionados.map(p => p.id);
                }
            } else {
                productosIds = productosSeleccionados.map(p => p.id);
            }
            
            config.productos_ids = productosIds;
            
            if (configExistente.mostrar_precio !== undefined) {
                config.mostrar_precio = configExistente.mostrar_precio;
            }
            if (configExistente.mostrar_boton !== undefined) {
                config.mostrar_boton = configExistente.mostrar_boton;
            }
        } else {
            
            config.productos_ids = [];
            Object.keys(configExistente).forEach(key => {
                if (key !== 'productos_ids') {
                    config[key] = configExistente[key];
                }
            });
        }
    }
    
    const descuentoMinimo = $('#config_descuento_minimo').val();
    if (descuentoMinimo) {
        config.descuento_minimo = parseInt(descuentoMinimo);
    }
    
    const mostrarTemporizador = $('#config_mostrar_temporizador');
    if (mostrarTemporizador.length) {
        config.mostrar_temporizador = mostrarTemporizador.is(':checked');
    }
    
    const mostrarPrecio = $('#config_mostrar_precio').val();
    if (mostrarPrecio) {
        config.mostrar_precio = mostrarPrecio === 'true';
    }
    
    Object.keys(configExistente).forEach(key => {
        if (!config.hasOwnProperty(key) && key !== 'productos_ids') {
            config[key] = configExistente[key];
        }
    });
    
    data.configuracion_json = config;
    
    const seccionIndex = seccionesHome.findIndex(s => s.id == seccionId);
    if (seccionIndex !== -1) {
        seccionesHome[seccionIndex] = { 
            ...seccionesHome[seccionIndex], 
            ...data,
            configuracion_json: config
        };
        
        renderizarSecciones();
        Swal.fire({
            icon: 'success',
            title: '¡Configuración actualizada!',
            html: `
                <div class="text-start">
                    <p>Se actualizó la sección.</p>
                    <p class="small text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Recuerda guardar los cambios usando el botón "Guardar Cambios" principal.
                    </p>
                </div>
            `,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
    
    if (modalConfigSeccion) {
        modalConfigSeccion.hide();
    }
}

function actualizarSeccion(seccionId, cambios) {
    const seccionIndex = seccionesHome.findIndex(s => s.id == seccionId);
    if (seccionIndex !== -1) {
        seccionesHome[seccionIndex] = { ...seccionesHome[seccionIndex], ...cambios };
        const card = $(`.seccion-card[data-id="${seccionId}"]`);
        if (cambios.mostrar !== undefined) {
            card.removeClass('border-primary border-secondary')
                .addClass(cambios.mostrar ? 'border-primary' : 'border-secondary');
            
            const badge = card.find('.badge.bg-success, .badge.bg-secondary');
            badge.removeClass('bg-success bg-secondary')
                 .addClass(cambios.mostrar ? 'bg-success' : 'bg-secondary')
                 .html(`
                    <i class="bi bi-${cambios.mostrar ? 'eye' : 'eye-slash'} me-1"></i>
                    ${cambios.mostrar ? 'Activa' : 'Inactiva'}
                 `);
        }
    }
}

function mostrarError(titulo, mensaje) {
    Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensaje,
        confirmButtonText: 'Entendido'
    });
}

function inicializarModuloLayout() {
    cargarSeccionesHome();
    $(document).ready(function() {
        const modalElement = document.getElementById('modalConfigSeccion');
        if (modalElement) {
            modalConfigSeccion = new bootstrap.Modal(modalElement);
            
            modalElement.addEventListener('submit', function(e) {
                e.preventDefault();
                return false;
            });
            
            $(document).on('click', '.btn-agregar-producto', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const productoData = {
                    id: parseInt($(this).data('id')),
                    nombre: $(this).data('nombre'),
                    sku: $(this).data('sku'),
                    imagen: $(this).data('imagen'),
                    precio: $(this).data('precio'),
                    tiene_oferta: $(this).data('tiene-oferta')
                };
                
                agregarProductoSeleccionado(productoData);
            });
        }
        
        $('#btnGuardarConfigSeccion').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            guardarConfiguracionSeccion();
        });
        
        $('#btnRecargarSecciones').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            cargarSeccionesHome();
        });
        
        $(document).on('change', '.seccion-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const seccionId = $(this).data('id');
            const mostrar = $(this).is(':checked');
            actualizarSeccion(seccionId, { mostrar: mostrar });
            Swal.fire({
                icon: 'info',
                title: 'Cambio aplicado',
                text: 'El cambio se ha aplicado localmente. Recuerda guardar.',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });
    });
}

$(document).ready(function() {
    let configuracionActual = null;
    let iconoSeleccionado = null;
    let modalIcono = null;
    setTimeout(() => {
        const modalElement = document.getElementById('modalIcono');
        if (modalElement) {
            modalIcono = new bootstrap.Modal(modalElement);
        }
    }, 100);

    cargarConfiguracion();
    inicializarModuloLayout();
    inicializarEventos();
    inicializarContadores();
    cargarFooterCompleto();
    inicializarSelectoresContacto();
    function cargarConfiguracion() {
        $.ajax({
            url: 'configuracionSitio',
            method: 'POST',
            data: {
                opcion: 'Listar', 
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Cargando...',
                    text: 'Obteniendo configuración actual',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.close();
                if (response && response.success !== undefined) {
                    if (response.success) {
                        configuracionActual = response.data || {};
                        llenarFormulario(configuracionActual);
                    } else {
                        mostrarError('Error al cargar', response.message || 'No se pudo cargar la configuración');
                    }
                } else if (response && response.respuesta === 'success') {
                    configuracionActual = response.data || {};
                    llenarFormulario(configuracionActual);
                } else {
                    mostrarError('Error inesperado', 'Formato de respuesta no válido');
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonText: 'Reintentar'
                }).then(() => {
                    cargarConfiguracion();
                });
            }
        });
    }

    function llenarFormulario(data) {
        $('#titulo_site').val(data.titulo_site || '');
        $('#abreviatura_titulo').val(data.abreviatura_titulo || '');
        $('#descripcion_corta').val(data.descripcion_corta || '');
        $('#email_admin').val(data.email_admin || '');
        $('#icono_site').val(data.icono_site || '');
        if (data.icono_site) {
            mostrarPreviewIcono(data.icono_site);
        } else {
            $('#previewIconoContainer').hide();
        }
        $('#max_entradas_home').val(data.max_entradas_home || 12);
        $('#footer_text').val(data.footer_text || '');
        
        actualizarContadores();
    }

    function mostrarPreviewIcono(ruta) {
        if (ruta && ruta.trim() !== '') {
            $('#previewIcono').attr('src', ruta);
            $('#previewIconoContainer').show();
            const nombreArchivo = ruta.split('/').pop();
            $('#iconoInfo').html(`
                <strong>Archivo:</strong> ${nombreArchivo}<br>
                <strong>Ruta:</strong> ${ruta}
            `);
        }
    }

    function inicializarEventos() {
        $('#btnGuardar, #btnGuardarFooter').on('click', guardarConfiguracion);
    
        $('#btnRestablecer').on('click', function() {
            if (!configuracionActual) {
                Swal.fire({
                    icon: 'info',
                    title: 'Cargando...',
                    text: 'Espere mientras se cargan los datos',
                    timer: 1500,
                    showConfirmButton: false
                });
                return;
            }

            Swal.fire({
                title: '¿Restablecer todos los cambios?',
                html: `
                    <div class="text-start">
                        <p>Se restablecerán:</p>
                        <ul class="small mb-0">
                            <li>✓ Configuración general del sitio</li>
                            <li>✓ Layout y orden de secciones</li>
                            <li>✓ Configuraciones específicas por sección</li>
                        </ul>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, restablecer todo',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    cargarConfiguracion();
                    cargarSeccionesHome();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Restablecido',
                        text: 'Todos los valores se han restablecido',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });

        $('#btnRemoverIcono').on('click', function() {
            $('#icono_site').val('');
            $('#previewIconoContainer').hide();
        });

        $('#btnExaminarIcono').on('click', function() {
            if (modalIcono) {
                cargarIconosExistentes();
                modalIcono.show();
            }
        });

        $('#btnSeleccionarIcono').on('click', function() {
            if (iconoSeleccionado) {
                $('#icono_site').val(iconoSeleccionado);
                mostrarPreviewIcono(iconoSeleccionado);
                if (modalIcono) {
                    modalIcono.hide();
                }
                iconoSeleccionado = null;
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Seleccione un icono',
                    text: 'Por favor seleccione un icono de la lista',
                    confirmButtonText: 'Entendido'
                });
            }
        });

        $('input, textarea').on('input', function() {
            $(this).removeClass('is-invalid');
            actualizarContadores();
        });

        $('#fileIcono').on('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                subirIcono(e.target.files[0]);
            }
        });
    }

    function inicializarContadores() {
        $('#descripcion_corta').on('input', function() {
            const length = $(this).val().length;
            $('#contadorDescripcion').text(`${length}/500`);
        });

        $('#footer_text').on('input', function() {
            const length = $(this).val().length;
            $('#contadorFooter').text(`${length}/1000`);
        });
    }

    function actualizarContadores() {
        $('#contadorDescripcion').text(`${$('#descripcion_corta').val().length}/500`);
        $('#contadorFooter').text(`${$('#footer_text').val().length}/1000`);
    }

    function cargarIconosExistentes() {
        $.ajax({
            url: 'configuracionSitio',
            method: 'POST',
            data: {
                opcion: 'Iconos', 
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            beforeSend: function() {
                $('#listaIconos').html(`
                    <div class="col-12 text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                `);
            },
            success: function(response) {
                const lista = $('#listaIconos');
                lista.empty();
                
                if (response && response.success && response.iconos) {
                    if (response.iconos.length > 0) {
                        response.iconos.forEach(icono => {
                            const card = `
                                <div class="col-6 col-sm-4 col-lg-3 mb-2">
                                    <div class="card icono-card border ${iconoSeleccionado === icono.ruta ? 'border-primary' : 'border-light'}" 
                                         data-ruta="${icono.ruta}" style="cursor: pointer; height: 100px;">
                                        <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                            <img src="${icono.ruta}" alt="${icono.nombre}" 
                                                 class="img-fluid mb-1" style="width: 24px; height: 24px; object-fit: contain;">
                                            <p class="small text-truncate mb-0" title="${icono.nombre}">
                                                ${icono.nombre}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            lista.append(card);
                        });

                        $('.icono-card').off('click').on('click', function() {
                            $('.icono-card').removeClass('border-primary').addClass('border-light');
                            $(this).removeClass('border-light').addClass('border-primary');
                            iconoSeleccionado = $(this).data('ruta');
                        });
                    } else {
                        lista.html(`
                            <div class="col-12 text-center py-4">
                                <i class="bi bi-folder-x text-muted" style="font-size: 2rem;"></i>
                                <p class="mt-2 text-muted">No se encontraron iconos</p>
                            </div>
                        `);
                    }
                } else {
                    lista.html(`
                        <div class="col-12 text-center py-4">
                            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                            <p class="mt-2 text-warning">Error al cargar iconos</p>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                $('#listaIconos').html(`
                    <div class="col-12 text-center py-4">
                        <i class="bi bi-wifi-off text-danger" style="font-size: 2rem;"></i>
                        <p class="mt-2 text-danger">Error de conexión</p>
                    </div>
                `);
            }
        });
    }

    function subirIcono(file) {
        if (!file) return;

        const validExtensions = ['.ico', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.webp'];
        const fileName = file.name.toLowerCase();
        const isValidExtension = validExtensions.some(ext => fileName.endsWith(ext));
        
        if (!isValidExtension) {
            Swal.fire({
                icon: 'error',
                title: 'Formato no válido',
                text: 'Solo se permiten: .ico, .png, .jpg, .jpeg, .gif, .svg, .webp',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        if (file.size > 2 * 1024 * 1024) { 
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: 'El tamaño máximo es 2MB',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        const formData = new FormData();
        formData.append('icono', file);
        formData.append('opcion', 'SubirIcono');
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
            url: 'configuracionSitio',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Subiendo...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function(response) {
                Swal.close();
                
                if (response && response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Subido!',
                        text: response.message || 'Icono subido correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        cargarIconosExistentes();
                        if (response.ruta) {
                            iconoSeleccionado = response.ruta;
                        }
                    });
                } else {
                    mostrarError('Error al subir', response?.message || 'No se pudo subir el icono');
                }
            },
            error: function(xhr) {
                Swal.close();
                mostrarError('Error', 'Error al subir el archivo');
            }
        });
    }

    function mostrarError(titulo, mensaje) {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    $('#formConfiguracionSitio').on('keypress', function(e) {
        if (e.which === 13 && !$(e.target).is('textarea')) {
            e.preventDefault();
        }
    });
});

// ===================== FOOTER GLOBAL VARIABLES =====================
let footerColumns = [];
let sortableFooter = null;
let currentColumnIdForLinks = null;
let currentColumnIdForContact = null;
let currentColumnIdForSocial = null;

// ===================== CARGAR DATOS INICIALES =====================
function cargarFooterCompleto() {
    console.log('🔄 cargarFooterCompleto llamada');
    cargarColumnasFooter();
}

function cargarColumnasFooter() {
    console.log('📡 Haciendo petición ListarFooterColumns...');
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'ListarFooterColumns',
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        beforeSend: () => {
            $('#listaColumnasFooter').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Cargando columnas...</p>
                </div>
            `);
        },
        success: (response) => {
            console.log('✅ Respuesta ListarFooterColumns:', response);
            if (response.success) {
                footerColumns = response.data;
                renderizarColumnasFooter();
                inicializarSortableFooter();
                actualizarSelectoresColumnas();
            } else {
                mostrarError('Error', 'No se pudieron cargar las columnas');
            }
        },
        error: (xhr) => {
            console.error('❌ Error en ListarFooterColumns:', xhr);
            $('#listaColumnasFooter').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error al cargar columnas.
                    <button class="btn btn-sm btn-link" onclick="cargarColumnasFooter()">Reintentar</button>
                </div>
            `);
        }
    });
}

function renderizarColumnasFooter() {
    const container = $('#listaColumnasFooter');
    container.empty();
    if (footerColumns.length === 0) {
        container.html(`
            <div class="text-center py-4">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-2">No hay columnas definidas. ¡Crea una!</p>
            </div>
        `);
        return;
    }
    footerColumns.sort((a,b) => a.sort_order - b.sort_order);
    footerColumns.forEach(col => {
        const iconHtml = getIconHtml(col.icon);
        const card = `
            <div class="card mb-2 seccion-card" data-id="${col.id}">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <div class="seccion-handle me-2" style="cursor: move;">
                            <i class="bi bi-grip-vertical text-muted"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <div>
                                    ${iconHtml}
                                    <strong>${escapeHtml(col.title)}</strong>
                                    <span class="badge bg-secondary ms-2">${col.column_type === 'links' ? 'Enlaces' : 'Mixta'}</span>
                                    ${!col.active ? '<span class="badge bg-danger ms-1">Inactiva</span>' : ''}
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar-columna" data-id="${col.id}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-columna" data-id="${col.id}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    ${col.icon ? `<button type="button" class="btn btn-sm btn-outline-warning btn-limpiar-icono-columna" data-id="${col.id}" title="Eliminar icono">
                                        <i class="bi bi-eraser"></i>
                                    </button>` : ''}
                                </div>
                            </div>
                            <small class="text-muted">Orden: ${col.sort_order}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.append(card);
    });

    // Bind events con prevención de propagación
    $('.btn-editar-columna').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        editarColumna($(this).data('id'));
    });
    
    $('.btn-eliminar-columna').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        eliminarColumna($(this).data('id'));
    });

    $('.btn-limpiar-icono-columna').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        limpiarIconoColumna($(this).data('id'));
    });
}

function inicializarSortableFooter() {
    const container = document.getElementById('listaColumnasFooter');
    if (!container) return;
    if (sortableFooter) sortableFooter.destroy();
    if (typeof Sortable !== 'undefined') {
        sortableFooter = new Sortable(container, {
            animation: 150,
            handle: '.seccion-handle',
            onEnd: function() {
                const newOrder = [];
                $('#listaColumnasFooter .card').each((idx, el) => {
                    newOrder.push($(el).data('id'));
                });
                actualizarOrdenColumnas(newOrder);
            }
        });
    }
}

function actualizarOrdenColumnas(orderedIds) {
    const updates = orderedIds.map((id, idx) => ({ id, sort_order: idx }));
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'OrdenarFooterColumns',
            orden: updates,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: (response) => {
            if (response.success) {
                toastr.success('Orden actualizado');
                cargarColumnasFooter(); // recargar
            } else {
                mostrarError('Error', response.message);
            }
        }
    });
}

function editarColumna(id) {
    const col = footerColumns.find(c => c.id == id);
    if (!col) return;

    let tipoIcono = 'libreria';
    let archivoSeleccionado = null;
    let rutaImagenActual = '';
    let iconoSeleccionado = '';

    if (col.icon && (col.icon.startsWith('/') || col.icon.match(/\.(ico|png|jpg|jpeg|gif|svg|webp)$/i))) {
        tipoIcono = 'imagen';
        rutaImagenActual = col.icon;
    } else if (col.icon) {
        tipoIcono = 'libreria';
        const match = col.icon.match(/bi bi-(.+)/);
        if (match) iconoSeleccionado = match[1];
    }

    Swal.fire({
        title: 'Editar Columna',
        html: `
            <div style="text-align: left">
                <div class="mb-3">
                    <label class="form-label fw-bold">Título</label>
                    <input type="text" id="col-title" class="swal2-input" value="${escapeHtml(col.title)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de columna</label>
                    <select id="col-type" class="swal2-select">
                        <option value="links" ${col.column_type === 'links' ? 'selected' : ''}>Solo enlaces</option>
                        <option value="mixed" ${col.column_type === 'mixed' ? 'selected' : ''}>Mixta (contacto + redes)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Ícono (opcional)</label>
                    <div class="d-flex gap-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoIconoCol" id="tipoLibreriaCol" value="libreria" ${tipoIcono === 'libreria' ? 'checked' : ''}>
                            <label class="form-check-label" for="tipoLibreriaCol">Ícono de librería</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoIconoCol" id="tipoImagenCol" value="imagen" ${tipoIcono === 'imagen' ? 'checked' : ''}>
                            <label class="form-check-label" for="tipoImagenCol">Imagen personalizada (.ico)</label>
                        </div>
                    </div>
                </div>

                <div id="panelLibreriaCol" class="mb-3" style="${tipoIcono === 'libreria' ? '' : 'display: none;'}">
                    <label class="form-label fw-bold">Selecciona un ícono</label>
                    <div style="margin-bottom:10px;">
                        <input id="buscadorIconosCol" class="swal2-input" placeholder="Buscar icono...">
                    </div>
                    <div id="iconPickerCol" class="icon-grid-modern">
                        <div class="text-center w-100 p-3">Cargando íconos...</div>
                    </div>
                    <input type="hidden" id="iconoColHidden" value="${iconoSeleccionado}">
                </div>

                <div id="panelImagenCol" class="mb-3" style="${tipoIcono === 'imagen' ? '' : 'display: none;'}">
                    <label class="form-label fw-bold">Subir icono (.ico)</label>
                    <div class="upload-area" id="uploadAreaCol">
                        <i class="bi bi-cloud-upload fs-1 text-secondary"></i>
                        <p class="mt-2 mb-0">Haz clic o arrastra un archivo .ico</p>
                        <input type="file" id="uploadIconoCol" accept=".ico" style="display: none;">
                    </div>
                    <div id="previewImagenCol" style="${rutaImagenActual ? 'display: flex;' : 'display: none;'}">
                        <img id="imgPreviewCol" src="${rutaImagenActual || ''}" style="width: 40px; height: 40px;">
                        <span class="flex-grow-1">${rutaImagenActual ? rutaImagenActual.split('/').pop() : ''}</span>
                        <button type="button" id="btnQuitarArchivoCol" class="btn btn-sm btn-outline-danger" ${rutaImagenActual ? '' : 'style="display: none;"'}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="new-col-active" ${col.active ? 'checked' : ''}>
                    <label class="form-check-label">Activa</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        width: '700px',
        didOpen: () => {
            // Misma lógica de eventos que en btnNuevaColumna, pero con los mismos IDs
            const iconPicker = document.getElementById('iconPickerCol');
            const hiddenIcon = document.getElementById('iconoColHidden');
            const buscador = document.getElementById('buscadorIconosCol');
            const panelLibreria = $('#panelLibreriaCol');
            const panelImagen = $('#panelImagenCol');
            const radioLibreria = $('#tipoLibreriaCol');
            const radioImagen = $('#tipoImagenCol');
            const uploadArea = document.getElementById('uploadAreaCol');
            const fileInput = document.getElementById('uploadIconoCol');
            const previewDiv = $('#previewImagenCol');
            const imgPreview = $('#imgPreviewCol');
            const nombreArchivoSpan = $('#previewImagenCol span.flex-grow-1');
            const btnQuitar = $('#btnQuitarArchivoCol');
            console.log(hiddenIcon)
            function cargarGrid(filtro = '') {
                if (!iconPicker) return;
                const iconos = window.iconosDisponibles || [];
                const termino = filtro.toLowerCase();
                let iconosFiltrados = iconos;
                if (termino) {
                    iconosFiltrados = iconos.filter(icono =>
                        icono.nombre.toLowerCase().includes(termino) ||
                        icono.icono.toLowerCase().includes(termino)
                    );
                }
                if (iconosFiltrados.length === 0) {
                    iconPicker.innerHTML = `<div class="text-center w-100 p-3">No se encontraron iconos con el término "${escapeHtml(filtro)}"</div>`;
                    return;
                }
                let html = '';
                iconosFiltrados.forEach(icono => {
                    const iconClass = `bi bi-${icono.icono}`;
                    const selected = (hiddenIcon.value === icono.icono) ? 'selected' : '';
                    html += `
                        <button type="button" class="icon-btn-modern ${selected}" data-icon="${icono.icono}">
                            <i class="${iconClass}"></i>
                            <span>${icono.nombre}</span>
                        </button>
                    `;
                });
                iconPicker.innerHTML = html;
                iconPicker.querySelectorAll('.icon-btn-modern').forEach(btn => {
                    btn.addEventListener('click', () => {
                        iconPicker.querySelectorAll('.icon-btn-modern').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        hiddenIcon.value = btn.dataset.icon;
                    });
                });
            }
            cargarGrid();

            if (buscador) {
                buscador.addEventListener('input', () => cargarGrid(buscador.value));
            }

            radioLibreria.on('change', function() {
                if ($(this).is(':checked')) {
                    tipoIcono = 'libreria';
                    panelLibreria.show();
                    panelImagen.hide();
                }
            });
            radioImagen.on('change', function() {
                if ($(this).is(':checked')) {
                    tipoIcono = 'imagen';
                    panelLibreria.hide();
                    panelImagen.show();
                }
            });

            function mostrarPreview(file) {
                if (file && file.name.toLowerCase().endsWith('.ico')) {
                    const objectUrl = URL.createObjectURL(file);
                    imgPreview.attr('src', objectUrl);
                    nombreArchivoSpan.text(file.name);
                    previewDiv.show();
                    btnQuitar.show();
                    archivoSeleccionado = file;
                } else {
                    toastr.error('Formato no válido. Solo se permiten archivos .ico');
                    fileInput.value = '';
                }
            }

            function limpiarPreview() {
                previewDiv.hide();
                btnQuitar.hide();
                imgPreview.attr('src', '');
                nombreArchivoSpan.text('');
                archivoSeleccionado = null;
                fileInput.value = '';
            }

            uploadArea.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    mostrarPreview(e.target.files[0]);
                }
            });
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const file = e.dataTransfer.files[0];
                if (file) mostrarPreview(file);
            });
            btnQuitar.on('click', () => limpiarPreview());
        },
        preConfirm: () => {
            const title = $('#col-title').val();
            const columnType = $('#col-type').val();
            let iconValue = '';

            if (!title) {
                Swal.showValidationMessage('El título es obligatorio');
                return false;
            }

            if (tipoIcono === 'libreria') {
                const iconName = $('#iconoColHidden').val();
                if (iconName) iconValue = `bi bi-${iconName}`;
            } else {
                if (!archivoSeleccionado && !rutaImagenActual) {
                    Swal.showValidationMessage('Debes seleccionar un archivo .ico o mantener la imagen existente');
                    return false;
                }
            }

            return {
                title: title,
                column_type: columnType,
                active: $('#new-col-active').is(':checked'),
                icon: iconValue,
                archivo: archivoSeleccionado,
                rutaExistente: rutaImagenActual
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            const formData = new FormData();
            formData.append('opcion', 'GuardarFooterColumn');
            formData.append('id', id);
            formData.append('title', data.title);
            formData.append('column_type', data.column_type);
            formData.append('active', data.active ? 1 : 0);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            if (tipoIcono === 'libreria') {
                if (data.icon) formData.append('icon', data.icon);
                else formData.append('icon', '');
            } else {
                if (data.archivo) formData.append('icono_archivo', data.archivo);
                else if (data.rutaExistente) formData.append('icon', data.rutaExistente);
                else formData.append('icon', '');
            }

            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Columna actualizada');
                        cargarFooterCompleto();
                    } else {
                        mostrarError('Error', response.message || 'No se pudo guardar');
                    }
                },
                error: (xhr) => {
                    console.error(xhr);
                    mostrarError('Error', 'Error al guardar la columna');
                }
            });
        }
    });
}

function guardarColumna(id, data) {
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'GuardarFooterColumn',
            id: id,
            title: data.title,
            column_type: data.column_type,
            active: data.active ? 1 : 0,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: (response) => {
            console.log('Respuesta guardar columna:', response);
            if (response.success) {
                toastr.success('Columna guardada');
                cargarFooterCompleto(); // ✅ Recargar la lista
            } else {
                mostrarError('Error', response.message);
            }
        },
        error: (xhr) => {
            console.error('Error AJAX:', xhr);
            mostrarError('Error', 'No se pudo guardar la columna');
        }
    });
}

function eliminarColumna(id) {
    Swal.fire({
        title: '¿Eliminar columna?',
        text: 'Se borrarán todos sus enlaces, contacto y redes sociales asociados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: {
                    opcion: 'EliminarFooterColumn',
                    id: id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Columna eliminada');
                        cargarFooterCompleto(); // ✅ Recargar
                    } else {
                        mostrarError('Error', response.message);
                    }
                }
            });
        }
    });
}

function actualizarSelectoresColumnas() {
    const colLinks = footerColumns.filter(c => c.column_type === 'links' && c.active);
    const colMixed = footerColumns.filter(c => c.column_type === 'mixed' && c.active);
    
    let optsLinks = '<option value="">-- Seleccionar columna --</option>';
    colLinks.forEach(c => optsLinks += `<option value="${c.id}">${escapeHtml(c.title)}</option>`);
    $('#selectColumnaLinks').html(optsLinks);
    
    let optsMixed = '<option value="">-- Seleccionar columna --</option>';
    colMixed.forEach(c => optsMixed += `<option value="${c.id}">${escapeHtml(c.title)}</option>`);
    $('#selectColumnaContact, #selectColumnaSocial').html(optsMixed);
    
    // Eventos cambio
    $('#selectColumnaLinks').off('change').on('change', function() {
        currentColumnIdForLinks = $(this).val();
        $('#btnNuevoEnlace').prop('disabled', !currentColumnIdForLinks);
        if (currentColumnIdForLinks) cargarEnlaces(currentColumnIdForLinks);
        else $('#listaEnlacesFooter').html('<div class="text-center py-3 text-muted">Seleccione una columna para ver sus enlaces.</div>');
    });
    
    $('#selectColumnaContact').off('change').on('change', function() {
        currentColumnIdForContact = $(this).val();
        if (currentColumnIdForContact) cargarContacto(currentColumnIdForContact);
        else $('#formContactoFooter')[0].reset();
    });
    
    $('#selectColumnaSocial').off('change').on('change', function() {
        currentColumnIdForSocial = $(this).val();
        $('#btnNuevaRedSocial').prop('disabled', !currentColumnIdForSocial);
        if (currentColumnIdForSocial) cargarRedesSociales(currentColumnIdForSocial);
        else $('#listaRedesSociales').html('<div class="text-center py-3 text-muted">Seleccione una columna para ver sus redes.</div>');
    });
}

function cargarEnlaces(columnId) {
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'ListarFooterLinks',
            column_id: columnId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: (response) => {
            if (response.success) {
                renderizarEnlaces(response.data);
            } else {
                $('#listaEnlacesFooter').html('<div class="alert alert-danger">Error al cargar enlaces</div>');
            }
        }
    });
}

function renderizarEnlaces(enlaces) {
    const container = $('#listaEnlacesFooter');
    if (enlaces.length === 0) {
        container.html('<div class="text-center py-3 text-muted">No hay enlaces en esta columna. ¡Agrega uno!</div>');
        return;
    }
    let html = '';
    enlaces.sort((a,b) => a.sort_order - b.sort_order).forEach(link => {
        const iconHtml = getIconHtml(link.icon);
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center" data-id="${link.id}">
                <div>
                    ${iconHtml}
                    <strong>${escapeHtml(link.text)}</strong><br>
                    <small class="text-muted">${escapeHtml(link.url)}</small>
                    ${!link.active ? '<span class="badge bg-secondary ms-2">Inactivo</span>' : ''}
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-1 editar-enlace" data-id="${link.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger eliminar-enlace" data-id="${link.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                    ${link.icon ? `<button type="button" class="btn btn-sm btn-outline-warning btn-limpiar-icono-enlace" data-id="${link.id}" title="Eliminar icono">
                        <i class="bi bi-eraser"></i>
                    </button>` : ''}
                </div>
            </div>
        `;
    });
    container.html(html);
    $('.editar-enlace').click(function() { editarEnlace($(this).data('id')); });
    $('.eliminar-enlace').click(function() { eliminarEnlace($(this).data('id')); });
    $('.btn-limpiar-icono-enlace').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        limpiarIconoEnlace($(this).data('id'));
    });
}

// Editar enlace existente
function editarEnlace(id) {
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: { opcion: 'ObtenerFooterLink', id: id, _token: $('meta[name="csrf-token"]').attr('content') },
        dataType: 'json',
        success: (response) => {
            if (response.success) {
                const link = response.data;
                let tipoIcono = 'libreria';
                let archivoSeleccionado = null;
                let rutaImagenActual = '';
                let iconoSeleccionado = '';

                if (link.icon && (link.icon.startsWith('/') || link.icon.match(/\.(ico|png|jpg|jpeg|gif|svg|webp)$/i))) {
                    tipoIcono = 'imagen';
                    rutaImagenActual = link.icon;
                } else if (link.icon) {
                    tipoIcono = 'libreria';
                    const match = link.icon.match(/bi bi-(.+)/);
                    if (match) iconoSeleccionado = match[1];
                }

                Swal.fire({
                    title: 'Editar Enlace',
                    html: `
                        <div style="text-align: left">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Texto</label>
                                <input type="text" id="link-text" class="swal2-input" value="${escapeHtml(link.text)}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">URL</label>
                                <input type="url" id="link-url" class="swal2-input" value="${escapeHtml(link.url)}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ícono (opcional)</label>
                                <div class="d-flex gap-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipoIconoEnlace" id="tipoLibreriaEnlace" value="libreria" ${tipoIcono === 'libreria' ? 'checked' : ''}>
                                        <label class="form-check-label" for="tipoLibreriaEnlace">Ícono de librería</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipoIconoEnlace" id="tipoImagenEnlace" value="imagen" ${tipoIcono === 'imagen' ? 'checked' : ''}>
                                        <label class="form-check-label" for="tipoImagenEnlace">Imagen .ico</label>
                                    </div>
                                </div>
                            </div>

                            <div id="panelLibreriaEnlace" class="mb-3" style="${tipoIcono === 'libreria' ? '' : 'display: none;'}">
                                <label class="form-label fw-bold">Selecciona un ícono</label>
                                <div style="margin-bottom:10px;">
                                    <input id="buscadorIconosEnlace" class="swal2-input" placeholder="Buscar icono...">
                                </div>
                                <div id="iconPickerEnlace" class="icon-grid-modern">
                                    <div class="text-center w-100 p-3">Cargando íconos...</div>
                                </div>
                                <input type="hidden" id="iconoEnlaceHidden" value="${iconoSeleccionado}">
                            </div>

                            <div id="panelImagenEnlace" class="mb-3" style="${tipoIcono === 'imagen' ? '' : 'display: none;'}">
                                <label class="form-label fw-bold">Subir icono (.ico)</label>
                                <div class="upload-area" id="uploadAreaEnlace">
                                    <i class="bi bi-cloud-upload fs-1 text-secondary"></i>
                                    <p class="mt-2 mb-0">Haz clic o arrastra .ico</p>
                                    <input type="file" id="uploadIconoEnlace" accept=".ico" style="display: none;">
                                </div>
                                <div id="previewImagenEnlace" style="${rutaImagenActual ? 'display: flex;' : 'display: none;'}">
                                    <img id="imgPreviewEnlace" src="${rutaImagenActual || ''}" style="width:40px; height:40px;">
                                    <span class="flex-grow-1">${rutaImagenActual ? rutaImagenActual.split('/').pop() : ''}</span>
                                    <button type="button" id="btnQuitarArchivoEnlace" class="btn btn-sm btn-outline-danger" ${rutaImagenActual ? '' : 'style="display: none;"'}>Quitar</button>
                                </div>
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="new-link-active" ${link.active ? 'checked' : ''}>
                                <label class="form-check-label">Activo</label>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    width: '700px',
                    didOpen: () => {
                        const iconPicker = document.getElementById('iconPickerEnlace');
                        const hiddenIcon = document.getElementById('iconoEnlaceHidden');
                        const buscador = document.getElementById('buscadorIconosEnlace');
                        const panelLibreria = $('#panelLibreriaEnlace');
                        const panelImagen = $('#panelImagenEnlace');
                        const radioLibreria = $('#tipoLibreriaEnlace');
                        const radioImagen = $('#tipoImagenEnlace');
                        const uploadArea = document.getElementById('uploadAreaEnlace');
                        const fileInput = document.getElementById('uploadIconoEnlace');
                        const previewDiv = $('#previewImagenEnlace');
                        const imgPreview = $('#imgPreviewEnlace');
                        const nombreArchivoSpan = $('#nombreArchivoEnlace');
                        const btnQuitar = $('#btnQuitarArchivoEnlace');

                        function cargarGrid(filtro = '') {
                            if (!iconPicker) return;
                            const iconos = window.iconosDisponibles || [];
                            const termino = filtro.toLowerCase();
                            let iconosFiltrados = iconos;
                            if (termino) {
                                iconosFiltrados = iconos.filter(icono =>
                                    icono.nombre.toLowerCase().includes(termino) ||
                                    icono.icono.toLowerCase().includes(termino)
                                );
                            }
                            if (iconosFiltrados.length === 0) {
                                iconPicker.innerHTML = `<div class="text-center w-100 p-3">No se encontraron iconos con el término "${escapeHtml(filtro)}"</div>`;
                                return;
                            }
                            let html = '';
                            iconosFiltrados.forEach(icono => {
                                const iconClass = `bi bi-${icono.icono}`;
                                const selected = (hiddenIcon.value === icono.icono) ? 'selected' : '';
                                html += `
                                    <button type="button" class="icon-btn-modern ${selected}" data-icon="${icono.icono}">
                                        <i class="${iconClass}"></i>
                                        <span>${icono.nombre}</span>
                                    </button>
                                `;
                            });
                            iconPicker.innerHTML = html;
                            iconPicker.querySelectorAll('.icon-btn-modern').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    iconPicker.querySelectorAll('.icon-btn-modern').forEach(b => b.classList.remove('selected'));
                                    btn.classList.add('selected');
                                    hiddenIcon.value = btn.dataset.icon;
                                });
                            });
                        }
                        cargarGrid();

                        if (buscador) {
                            buscador.addEventListener('input', () => cargarGrid(buscador.value));
                        }

                        radioLibreria.on('change', function() {
                            if ($(this).is(':checked')) {
                                tipoIcono = 'libreria';
                                panelLibreria.show();
                                panelImagen.hide();
                            }
                        });
                        radioImagen.on('change', function() {
                            if ($(this).is(':checked')) {
                                tipoIcono = 'imagen';
                                panelLibreria.hide();
                                panelImagen.show();
                            }
                        });

                        function mostrarPreview(file) {
                            if (file && file.name.toLowerCase().endsWith('.ico')) {
                                const objectUrl = URL.createObjectURL(file);
                                imgPreview.attr('src', objectUrl);
                                nombreArchivoSpan.text(file.name);
                                previewDiv.show();
                                btnQuitar.show();
                                archivoSeleccionado = file;
                            } else {
                                toastr.error('Formato no válido. Solo se permiten archivos .ico');
                                fileInput.value = '';
                            }
                        }

                        function limpiarPreview() {
                            previewDiv.hide();
                            btnQuitar.hide();
                            imgPreview.attr('src', '');
                            nombreArchivoSpan.text('');
                            archivoSeleccionado = null;
                            fileInput.value = '';
                        }

                        uploadArea.addEventListener('click', () => fileInput.click());
                        fileInput.addEventListener('change', (e) => {
                            if (e.target.files && e.target.files[0]) {
                                mostrarPreview(e.target.files[0]);
                            }
                        });
                        uploadArea.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            uploadArea.classList.add('dragover');
                        });
                        uploadArea.addEventListener('dragleave', () => {
                            uploadArea.classList.remove('dragover');
                        });
                        uploadArea.addEventListener('drop', (e) => {
                            e.preventDefault();
                            uploadArea.classList.remove('dragover');
                            const file = e.dataTransfer.files[0];
                            if (file) mostrarPreview(file);
                        });
                        btnQuitar.on('click', () => limpiarPreview());
                    },
                    preConfirm: () => {
                        const text = $('#link-text').val();
                        const url = $('#link-url').val();
                        if (!text || !url) {
                            Swal.showValidationMessage('Texto y URL son obligatorios');
                            return false;
                        }
                        let iconValue = '';
                        if (tipoIcono === 'libreria') {
                            const iconName = $('#iconoEnlaceHidden').val();
                            if (iconName) iconValue = `bi bi-${iconName}`;
                        } else {
                            if (!archivoSeleccionado && !rutaImagenActual) {
                                Swal.showValidationMessage('Debes seleccionar un archivo .ico o mantener la imagen existente');
                                return false;
                            }
                        }
                        return {
                            text: text,
                            url: url,
                            active: $('#new-link-active').is(':checked'),
                            icon: iconValue,
                            archivo: archivoSeleccionado,
                            rutaExistente: rutaImagenActual
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const data = result.value;
                        const formData = new FormData();
                        formData.append('opcion', 'GuardarFooterLink');
                        formData.append('id', id);
                        formData.append('column_id', currentColumnIdForLinks);
                        formData.append('text', data.text);
                        formData.append('url', data.url);
                        formData.append('active', data.active ? 1 : 0);
                        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                        if (tipoIcono === 'libreria') {
                            if (data.icon) formData.append('icon', data.icon);
                            else formData.append('icon', '');
                        } else {
                            if (data.archivo) formData.append('icono_archivo', data.archivo);
                            else if (data.rutaExistente) formData.append('icon', data.rutaExistente);
                            else formData.append('icon', '');
                        }

                        $.ajax({
                            url: 'configuracionSitio',
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            success: (response) => {
                                if (response.success) {
                                    toastr.success('Enlace actualizado');
                                    cargarEnlaces(currentColumnIdForLinks);
                                } else {
                                    mostrarError('Error', response.message || 'No se pudo guardar');
                                }
                            },
                            error: (xhr) => {
                                console.error(xhr);
                                mostrarError('Error', 'Error al guardar el enlace');
                            }
                        });
                    }
                });
            }
        }
    });
}

function guardarEnlace(id, data) {
    console.log(data)
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'GuardarFooterLink',
            id: id,
            column_id: currentColumnIdForLinks,
            text: data.text,
            url: data.url,
            active: data.active ? 1 : 0, 
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: (response) => {
            if (response.success) {
                toastr.success('Enlace guardado');
                cargarEnlaces(currentColumnIdForLinks);
            } else {
                mostrarError('Error', response.message || 'No se pudo guardar');
            }
        }
    });
}

function eliminarEnlace(id) {
    Swal.fire({
        title: '¿Eliminar enlace?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: {
                    opcion: 'EliminarFooterLink',
                    id: id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Enlace eliminado');
                        cargarEnlaces(currentColumnIdForLinks);
                    } else {
                        mostrarError('Error', response.message);
                    }
                }
            });
        }
    });
}

// Botón "Agregar Enlace"
$('#btnNuevoEnlace').click(function() {
    if (!currentColumnIdForLinks) return;

    let tipoIcono = 'libreria';
    let archivoSeleccionado = null;

    Swal.fire({
        title: 'Nuevo Enlace',
        html: `
            <div style="text-align: left">
                <div class="mb-3">
                    <label class="form-label fw-bold">Texto del enlace</label>
                    <input type="text" id="link-text" class="swal2-input" placeholder="Ej: Inicio" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">URL</label>
                    <input type="url" id="link-url" class="swal2-input" placeholder="https://ejemplo.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Ícono (opcional)</label>
                    <div class="d-flex gap-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoIconoEnlace" id="tipoLibreriaEnlace" value="libreria" checked>
                            <label class="form-check-label" for="tipoLibreriaEnlace">Ícono de librería</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoIconoEnlace" id="tipoImagenEnlace" value="imagen">
                            <label class="form-check-label" for="tipoImagenEnlace">Imagen personalizada (.ico)</label>
                        </div>
                    </div>
                </div>

                <div id="panelLibreriaEnlace" class="mb-3">
                    <label class="form-label fw-bold">Selecciona un ícono</label>
                    <div style="margin-bottom:10px;">
                        <input id="buscadorIconosEnlace" class="swal2-input" placeholder="Buscar icono...">
                    </div>
                    <div id="iconPickerEnlace" class="icon-grid-modern">
                        <div class="text-center w-100 p-3">Cargando íconos...</div>
                    </div>
                    <input type="hidden" id="iconoEnlaceHidden" value="">
                </div>

                <div id="panelImagenEnlace" style="display: none;" class="mb-3">
                    <label class="form-label fw-bold">Subir icono (.ico)</label>
                    <div class="upload-area" id="uploadAreaEnlace">
                        <i class="bi bi-cloud-upload fs-1 text-secondary"></i>
                        <p class="mt-2 mb-0">Haz clic o arrastra un archivo .ico</p>
                        <input type="file" id="uploadIconoEnlace" accept=".ico" style="display: none;">
                    </div>
                    <div id="previewImagenEnlace" style="display: none;">
                        <img id="imgPreviewEnlace" src="" style="width: 40px; height: 40px;">
                        <span class="flex-grow-1"></span>
                        <button type="button" id="btnQuitarArchivoEnlace" class="btn btn-sm btn-outline-danger" style="display: none;">Quitar</button>
                    </div>
                </div>

                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="new-link-active" checked>
                    <label class="form-check-label">Activo</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Crear',
        cancelButtonText: 'Cancelar',
        width: '700px',
        didOpen: () => {
            const iconPicker = document.getElementById('iconPickerEnlace');
            const hiddenIcon = document.getElementById('iconoEnlaceHidden');
            const buscador = document.getElementById('buscadorIconosEnlace');
            const panelLibreria = $('#panelLibreriaEnlace');
            const panelImagen = $('#panelImagenEnlace');
            const radioLibreria = $('#tipoLibreriaEnlace');
            const radioImagen = $('#tipoImagenEnlace');
            const uploadArea = document.getElementById('uploadAreaEnlace');
            const fileInput = document.getElementById('uploadIconoEnlace');
            const previewDiv = $('#previewImagenEnlace');
            const imgPreview = $('#imgPreviewEnlace');
            const nombreArchivoSpan = $('#nombreArchivoEnlace');
            const btnQuitar = $('#btnQuitarArchivoEnlace');

            function cargarGrid(filtro = '') {
                if (!iconPicker) return;
                const iconos = window.iconosDisponibles || [];
                const termino = filtro.toLowerCase();
                let iconosFiltrados = iconos;
                if (termino) {
                    iconosFiltrados = iconos.filter(icono =>
                        icono.nombre.toLowerCase().includes(termino) ||
                        icono.icono.toLowerCase().includes(termino)
                    );
                }
                if (iconosFiltrados.length === 0) {
                    iconPicker.innerHTML = `<div class="text-center w-100 p-3">No se encontraron iconos con el término "${escapeHtml(filtro)}"</div>`;
                    return;
                }
                let html = '';
                iconosFiltrados.forEach(icono => {
                    const iconClass = `bi bi-${icono.icono}`;
                    const selected = (hiddenIcon.value === icono.icono) ? 'selected' : '';
                    html += `
                        <button type="button" class="icon-btn-modern ${selected}" data-icon="${icono.icono}">
                            <i class="${iconClass}"></i>
                            <span>${icono.nombre}</span>
                        </button>
                    `;
                });
                iconPicker.innerHTML = html;
                iconPicker.querySelectorAll('.icon-btn-modern').forEach(btn => {
                    btn.addEventListener('click', () => {
                        iconPicker.querySelectorAll('.icon-btn-modern').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        hiddenIcon.value = btn.dataset.icon;
                    });
                });
            }
            cargarGrid();

            if (buscador) {
                buscador.addEventListener('input', () => cargarGrid(buscador.value));
            }

            radioLibreria.on('change', function() {
                if ($(this).is(':checked')) {
                    tipoIcono = 'libreria';
                    panelLibreria.show();
                    panelImagen.hide();
                }
            });
            radioImagen.on('change', function() {
                if ($(this).is(':checked')) {
                    tipoIcono = 'imagen';
                    panelLibreria.hide();
                    panelImagen.show();
                }
            });

            function mostrarPreview(file) {
                if (file && file.name.toLowerCase().endsWith('.ico')) {
                    const objectUrl = URL.createObjectURL(file);
                    imgPreview.attr('src', objectUrl);
                    nombreArchivoSpan.text(file.name);
                    previewDiv.show();
                    btnQuitar.show();
                    archivoSeleccionado = file;
                } else {
                    toastr.error('Formato no válido. Solo se permiten archivos .ico');
                    fileInput.value = '';
                }
            }

            function limpiarPreview() {
                previewDiv.hide();
                btnQuitar.hide();
                imgPreview.attr('src', '');
                nombreArchivoSpan.text('');
                archivoSeleccionado = null;
                fileInput.value = '';
            }

            uploadArea.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    mostrarPreview(e.target.files[0]);
                }
            });
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const file = e.dataTransfer.files[0];
                if (file) mostrarPreview(file);
            });
            btnQuitar.on('click', () => limpiarPreview());
        },
        preConfirm: () => {
            const text = $('#link-text').val();
            const url = $('#link-url').val();
            if (!text || !url) {
                Swal.showValidationMessage('Texto y URL son obligatorios');
                return false;
            }
            let iconValue = '';
            if (tipoIcono === 'libreria') {
                const iconName = $('#iconoEnlaceHidden').val();
                if (iconName) iconValue = `bi bi-${iconName}`;
            } else {
                if (!archivoSeleccionado) {
                    Swal.showValidationMessage('Debes seleccionar un archivo .ico');
                    return false;
                }
            }
            return { text, url, active: $('#new-link-active').is(':checked'), icon: iconValue, archivo: archivoSeleccionado };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            const formData = new FormData();
            formData.append('opcion', 'GuardarFooterLink');
            formData.append('column_id', currentColumnIdForLinks);
            formData.append('text', data.text);
            formData.append('url', data.url);
            formData.append('active', data.active ? 1 : 0);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            if (tipoIcono === 'libreria' && data.icon) {
                formData.append('icon', data.icon);
            } else if (tipoIcono === 'imagen' && data.archivo) {
                formData.append('icono_archivo', data.archivo);
            }

            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Enlace creado');
                        cargarEnlaces(currentColumnIdForLinks);
                    } else {
                        mostrarError('Error', response.message || 'No se pudo guardar');
                    }
                },
                error: (xhr) => {
                    console.error(xhr);
                    mostrarError('Error', 'Error al guardar el enlace');
                }
            });
        }
    });
});
// Función genérica para inicializar un selector de ícono (para teléfono, email o dirección)
function initContactIconSelector(tipo, contenedorId, hiddenId, buscadorId, uploadAreaId, fileInputId, previewDivId, imgPreviewId, nombreArchivoId, btnQuitarId, radioLibreriaId, radioImagenId, panelLibreriaId, panelImagenId) {
    let archivoSeleccionado = null;
    let rutaImagenActual = ''; // para cuando se carga un icono ya guardado

    // Función para cargar el grid (reutiliza generarGridIconos)
    function cargarGrid(filtro = '') {
        const container = document.getElementById(contenedorId);
        if (!container) return;
        const iconos = window.iconosDisponibles || [];
        const termino = filtro.toLowerCase();
        let iconosFiltrados = iconos;
        if (termino) {
            iconosFiltrados = iconos.filter(icono =>
                icono.nombre.toLowerCase().includes(termino) ||
                icono.icono.toLowerCase().includes(termino)
            );
        }
        if (iconosFiltrados.length === 0) {
            container.innerHTML = `<div class="text-center w-100 p-3">No se encontraron iconos con el término "${escapeHtml(filtro)}"</div>`;
            return;
        }
        let html = '';
        const currentHidden = document.getElementById(hiddenId);
        const valorActual = currentHidden ? currentHidden.value : '';
        iconosFiltrados.forEach(icono => {
            const iconClass = `bi bi-${icono.icono}`;
            const selected = (valorActual === icono.icono) ? 'selected' : '';
            html += `
                <button type="button" class="icon-btn-modern ${selected}" data-icon="${icono.icono}">
                    <i class="${iconClass}"></i>
                    <span>${icono.nombre}</span>
                </button>
            `;
        });
        container.innerHTML = html;
        // Bind eventos de click
        container.querySelectorAll('.icon-btn-modern').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.icon-btn-modern').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                if (currentHidden) currentHidden.value = btn.dataset.icon;
                // Si se selecciona un icono de librería, limpiamos cualquier archivo pendiente
                archivoSeleccionado = null;
                rutaImagenActual = '';
                $(`#${previewDivId}`).hide();
            });
        });
    }

    // Buscador
    const buscador = document.getElementById(buscadorId);
    if (buscador) {
        buscador.addEventListener('input', () => cargarGrid(buscador.value));
    }

    // Cambio entre pestañas
    $(`#${radioLibreriaId}`).on('change', function() {
        if ($(this).is(':checked')) {
            $(`#${panelLibreriaId}`).show();
            $(`#${panelImagenId}`).hide();
            cargarGrid();
        }
    });
    $(`#${radioImagenId}`).on('change', function() {
        if ($(this).is(':checked')) {
            $(`#${panelLibreriaId}`).hide();
            $(`#${panelImagenId}`).show();
        }
    });

    // Subida de archivo
    const uploadArea = document.getElementById(uploadAreaId);
    const fileInput = document.getElementById(fileInputId);
    const previewDiv = $(`#${previewDivId}`);
    const imgPreview = $(`#${imgPreviewId}`);
    const nombreArchivoSpan = $(`#${nombreArchivoId}`);
    const btnQuitar = $(`#${btnQuitarId}`);

    function mostrarPreview(file) {
        if (file && file.name.toLowerCase().endsWith('.ico')) {
            const objectUrl = URL.createObjectURL(file);
            imgPreview.attr('src', objectUrl);
            nombreArchivoSpan.text(file.name);
            previewDiv.show();
            btnQuitar.show();
            archivoSeleccionado = file;
            // Limpiar el hidden para que no se envíe el icono de librería
            $(`#${hiddenId}`).val('');
            rutaImagenActual = '';
        } else {
            toastr.error('Solo se permiten archivos .ico');
            fileInput.value = '';
        }
    }

    function limpiarPreview() {
        previewDiv.hide();
        btnQuitar.hide();
        imgPreview.attr('src', '');
        nombreArchivoSpan.text('');
        archivoSeleccionado = null;
        fileInput.value = '';
        // No borramos rutaImagenActual aquí porque podría ser la imagen existente
    }

    if (uploadArea) {
        uploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) mostrarPreview(e.target.files[0]);
        });
        uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('dragover'); });
        uploadArea.addEventListener('dragleave', () => { uploadArea.classList.remove('dragover'); });
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file) mostrarPreview(file);
        });
        btnQuitar.on('click', () => limpiarPreview());
    }

    return {
        cargarGrid: (filtro = '') => cargarGrid(filtro),
        setExistingIcon: (iconValue) => {
            if (!iconValue) {
                $(`#${hiddenId}`).val('');
                rutaImagenActual = '';
                archivoSeleccionado = null;
                $(`#${radioLibreriaId}`).prop('checked', true);
                $(`#${radioImagenId}`).prop('checked', false);
                $(`#${panelLibreriaId}`).show();
                $(`#${panelImagenId}`).hide();
                $(`#${previewDivId}`).hide();
                cargarGrid('');
                return;
            }
            if (iconValue.startsWith('/') || iconValue.match(/\.(ico|png|jpg|jpeg|gif|svg|webp)$/i)) {
                // Es imagen
                rutaImagenActual = iconValue;
                archivoSeleccionado = null;
                $(`#${radioLibreriaId}`).prop('checked', false);
                $(`#${radioImagenId}`).prop('checked', true);
                $(`#${panelLibreriaId}`).hide();
                $(`#${panelImagenId}`).show();
                imgPreview.attr('src', iconValue);
                nombreArchivoSpan.text(iconValue.split('/').pop());
                previewDiv.show();
                btnQuitar.show();
                $(`#${hiddenId}`).val('');
            } else {
                // Es clase CSS
                const match = iconValue.match(/bi bi-(.+)/);
                if (match) {
                    $(`#${hiddenId}`).val(match[1]);
                    $(`#${radioLibreriaId}`).prop('checked', true);
                    $(`#${radioImagenId}`).prop('checked', false);
                    $(`#${panelLibreriaId}`).show();
                    $(`#${panelImagenId}`).hide();
                    cargarGrid();
                }
                rutaImagenActual = '';
                archivoSeleccionado = null;
                previewDiv.hide();
            }
        },
        getValueToSend: () => {
            if ($(`#${radioLibreriaId}`).is(':checked')) {
                const iconName = $(`#${hiddenId}`).val();
                if (iconName) return { type: 'class', value: `bi bi-${iconName}` };
                return { type: 'class', value: null };
            } else {
                if (archivoSeleccionado) return { type: 'file', file: archivoSeleccionado };
                if (rutaImagenActual) return { type: 'class', value: rutaImagenActual };
                return { type: 'class', value: null };
            }
        }
    };
}

// Inicializar los tres selectores
let phoneSelector, emailSelector, addressSelector;

function inicializarSelectoresContacto() {
    phoneSelector = initContactIconSelector(
        'phone', 'iconPickerPhone', 'iconoPhoneHidden', 'buscadorPhone',
        'uploadAreaPhone', 'uploadIconoPhone', 'previewPhoneImagen', 'imgPreviewPhone',
        'nombreArchivoPhone', 'btnQuitarPhone', 'tipoPhoneLibreria', 'tipoPhoneImagen',
        'panelPhoneLibreria', 'panelPhoneImagen'
    );
    emailSelector = initContactIconSelector(
        'email', 'iconPickerEmail', 'iconoEmailHidden', 'buscadorEmail',
        'uploadAreaEmail', 'uploadIconoEmail', 'previewEmailImagen', 'imgPreviewEmail',
        'nombreArchivoEmail', 'btnQuitarEmail', 'tipoEmailLibreria', 'tipoEmailImagen',
        'panelEmailLibreria', 'panelEmailImagen'
    );
    addressSelector = initContactIconSelector(
        'address', 'iconPickerAddress', 'iconoAddressHidden', 'buscadorAddress',
        'uploadAreaAddress', 'uploadIconoAddress', 'previewAddressImagen', 'imgPreviewAddress',
        'nombreArchivoAddress', 'btnQuitarAddress', 'tipoAddressLibreria', 'tipoAddressImagen',
        'panelAddressLibreria', 'panelAddressImagen'
    );
}

function cargarContacto(columnId) {
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'ObtenerFooterContact',
            column_id: columnId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: (response) => {
            if (response.success && response.data) {
                const data = response.data;
                $('#contact_phone').val(data.phone || '');
                $('#contact_email').val(data.email || '');
                $('#contact_address').val(data.address || '');
                // Cargar iconos existentes
                if (phoneSelector) phoneSelector.setExistingIcon(data.phone_icon);
                if (emailSelector) emailSelector.setExistingIcon(data.email_icon);
                if (addressSelector) addressSelector.setExistingIcon(data.address_icon);
            } else {
                // Limpiar formulario
                $('#contact_phone, #contact_email, #contact_address').val('');
                if (phoneSelector) phoneSelector.setExistingIcon(null);
                if (emailSelector) emailSelector.setExistingIcon(null);
                if (addressSelector) addressSelector.setExistingIcon(null);
            }
        }
    });
}

function guardarContacto() {
    if (!currentColumnIdForContact) {
        toastr.warning('Selecciona una columna primero');
        return;
    }

    const formData = new FormData();
    formData.append('opcion', 'GuardarFooterContact');
    formData.append('column_id', currentColumnIdForContact);
    formData.append('phone', $('#contact_phone').val());
    formData.append('email', $('#contact_email').val());
    formData.append('address', $('#contact_address').val());
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

    // Función para agregar icono de un campo
    function agregarIcono(selector, fieldName) {
        const result = selector.getValueToSend();
        if (result.type === 'class') {
            if (result.value) formData.append(`${fieldName}_icon`, result.value);
        } else if (result.type === 'file') {
            formData.append(`${fieldName}_icono_archivo`, result.file);
        }
    }

    if (phoneSelector) agregarIcono(phoneSelector, 'phone');
    if (emailSelector) agregarIcono(emailSelector, 'email');
    if (addressSelector) agregarIcono(addressSelector, 'address');

    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: (response) => {
            if (response.success) {
                toastr.success('Contacto guardado');
                cargarContacto(currentColumnIdForContact); // recargar
            } else {
                mostrarError('Error', response.message);
            }
        },
        error: (xhr) => {
            console.error(xhr);
            mostrarError('Error', 'Error al guardar el contacto');
        }
    });
}

$('#btnGuardarContacto').click(guardarContacto);
function cargarRedesSociales(columnId) {
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'ListarFooterSocial',
            column_id: columnId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: (response) => {
            if (response.success) {
                renderizarRedesSociales(response.data);
            } else {
                $('#listaRedesSociales').html('<div class="alert alert-danger">Error al cargar redes</div>');
            }
        }
    });
}

function renderizarRedesSociales(redes) {
    const container = $('#listaRedesSociales');
    if (redes.length === 0) {
        container.html('<div class="text-center py-3 text-muted">No hay redes sociales en esta columna. ¡Agrega una!</div>');
        return;
    }
    let html = '';
    redes.sort((a,b) => a.sort_order - b.sort_order).forEach(red => {
        let iconHtml = '';
        // Si el icono contiene una ruta (empieza con / o contiene .ico, .png, etc.)
        if (red.icon && (red.icon.startsWith('/') || red.icon.match(/\.(ico|png|jpg|jpeg|gif|svg|webp)$/i))) {
            iconHtml = `<img src="${red.icon}" alt="icono" style="width: 20px; height: 20px; object-fit: contain;">`;
        } else {
            iconHtml = `<i class="${red.icon} me-2"></i>`;
        }
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center" data-id="${red.id}">
                <div>
                    ${iconHtml}
                    <strong>${escapeHtml(red.name)}</strong><br>
                    <small class="text-muted">${escapeHtml(red.url)}</small>
                    ${!red.active ? '<span class="badge bg-secondary ms-2">Inactivo</span>' : ''}
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-1 editar-red" data-id="${red.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger eliminar-red" data-id="${red.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    container.html(html);
    $('.editar-red').click(function() { editarRedSocial($(this).data('id')); });
    $('.eliminar-red').click(function() { eliminarRedSocial($(this).data('id')); });
}

function editarRedSocial(id) {
    // Primero obtener los datos actuales
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'ObtenerFooterSocial',
            id: id,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: (response) => {
            if (response.success) {
                const red = response.data;
                let tipoIcono = 'libreria';
                let iconoSeleccionado = ''; // nombre del ícono de librería (ej. 'facebook')
                let rutaImagenActual = '';   // ruta de la imagen si existe
                let archivoSeleccionado = null;

                // Determinar tipo y valor actual
                if (red.icon && (red.icon.startsWith('/') || red.icon.match(/\.(ico|png|jpg|jpeg|gif|svg|webp)$/i))) {
                    tipoIcono = 'imagen';
                    rutaImagenActual = red.icon;
                } else {
                    tipoIcono = 'libreria';
                    // Extraer el nombre del ícono de la clase CSS, ej: "bi bi-facebook" -> "facebook"
                    const match = red.icon.match(/bi bi-(.+)/)
                    if (match) iconoSeleccionado = match[1];
                }

                // Abrir modal con los datos cargados
                Swal.fire({
                    title: 'Editar Red Social',
                    html: `
                        <div style="text-align: left">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre de la red social</label>
                                <input type="text" id="red-name" class="swal2-input" value="${escapeHtml(red.name)}" placeholder="Ej: Facebook" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tipo de ícono</label>
                                <div class="d-flex gap-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipoIconoSocial" id="tipoLibreriaSocial" value="libreria" ${tipoIcono === 'libreria' ? 'checked' : ''}>
                                        <label class="form-check-label" for="tipoLibreriaSocial">Ícono de librería</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipoIconoSocial" id="tipoImagenSocial" value="imagen" ${tipoIcono === 'imagen' ? 'checked' : ''}>
                                        <label class="form-check-label" for="tipoImagenSocial">Imagen personalizada (.ico)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel de íconos de librería (con buscador) -->
                            <div id="panelLibreriaSocial" class="mb-3" style="${tipoIcono === 'libreria' ? '' : 'display: none;'}">
                                <label class="form-label fw-bold">Selecciona un ícono</label>
                                <div style="margin-bottom:10px;">
                                    <input id="buscadorIconosSocial" class="swal2-input" placeholder="Buscar icono..."
                                        style="border:2px solid #007bff; border-radius:10px; padding:8px;">
                                </div>
                                <div id="iconPickerSocial" class="icon-grid-modern">
                                    <div class="text-center w-100 p-3">Cargando íconos...</div>
                                </div>
                                <input type="hidden" id="iconoSocialHidden" value="${iconoSeleccionado}">
                            </div>

                            <!-- Panel de subida de imagen .ico -->
                            <div id="panelImagenSocial" class="mb-3" style="${tipoIcono === 'imagen' ? '' : 'display: none;'}">
                                <label class="form-label fw-bold">Subir icono (.ico)</label>
                                <div class="upload-area" id="uploadAreaSocial">
                                    <i class="bi bi-cloud-upload fs-1 text-secondary"></i>
                                    <p class="mt-2 mb-0">Haz clic o arrastra un archivo .ico</p>
                                    <input type="file" id="uploadIconoSocial" accept=".ico" style="display: none;">
                                </div>
                                <div id="previewImagenSocial" style="${rutaImagenActual ? 'display: flex;' : 'display: none;'}">
                                    <img id="imgPreviewSocial" src="${rutaImagenActual || ''}" style="width: 40px; height: 40px; object-fit: contain;">
                                    <span id="nombreArchivoSocial" class="flex-grow-1">${rutaImagenActual ? rutaImagenActual.split('/').pop() : ''}</span>
                                    <button type="button" id="btnQuitarArchivo" class="btn btn-sm btn-outline-danger" ${rutaImagenActual ? '' : 'style="display: none;"'}>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">URL de la red social</label>
                                <input type="url" id="red-url" class="swal2-input" value="${escapeHtml(red.url)}" placeholder="https://facebook.com/miPagina" required>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="new-red-active" ${red.active ? 'checked' : ''}>
                                <label class="form-check-label">Activo</label>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    width: '700px',
                    didOpen: () => {
                        const iconPicker = document.getElementById('iconPickerSocial');
                        const hiddenIcon = document.getElementById('iconoSocialHidden');
                        const buscador = document.getElementById('buscadorIconosSocial');
                        const panelLibreria = $('#panelLibreriaSocial');
                        const panelImagen = $('#panelImagenSocial');
                        const radioLibreria = $('#tipoLibreriaSocial');
                        const radioImagen = $('#tipoImagenSocial');
                        const uploadArea = document.getElementById('uploadAreaSocial');
                        const fileInput = document.getElementById('uploadIconoSocial');
                        const previewDiv = $('#previewImagenSocial');
                        const imgPreview = $('#imgPreviewSocial');
                        const nombreArchivoSpan = $('#nombreArchivoSocial');
                        const btnQuitar = $('#btnQuitarArchivo');

                        // Cargar grid de íconos
                        function cargarGrid(filtro = '') {
                            if (!iconPicker) return;
                            const iconos = window.iconosDisponibles || [];
                            const termino = filtro.toLowerCase();
                            let iconosFiltrados = iconos;
                            if (termino) {
                                iconosFiltrados = iconos.filter(icono =>
                                    icono.nombre.toLowerCase().includes(termino) ||
                                    icono.icono.toLowerCase().includes(termino)
                                );
                            }
                            if (iconosFiltrados.length === 0) {
                                iconPicker.innerHTML = `<div class="text-center w-100 p-3">No se encontraron iconos con el término "${escapeHtml(filtro)}"</div>`;
                                return;
                            }
                            let html = '';
                            iconosFiltrados.forEach(icono => {
                                const iconClass = `bi bi-${icono.icono}`;
                                const selected = (hiddenIcon.value === icono.icono) ? 'selected' : '';
                                html += `
                                    <button type="button" class="icon-btn-modern ${selected}" data-icon="${icono.icono}">
                                        <i class="${iconClass}"></i>
                                        <span>${icono.nombre}</span>
                                    </button>
                                `;
                            });
                            iconPicker.innerHTML = html;
                            iconPicker.querySelectorAll('.icon-btn-modern').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    iconPicker.querySelectorAll('.icon-btn-modern').forEach(b => b.classList.remove('selected'));
                                    btn.classList.add('selected');
                                    hiddenIcon.value = btn.dataset.icon;
                                });
                            });
                        }
                        cargarGrid();

                        if (buscador) {
                            buscador.addEventListener('input', () => cargarGrid(buscador.value));
                        }

                        // Cambio entre pestañas
                        radioLibreria.on('change', function() {
                            if ($(this).is(':checked')) {
                                tipoIcono = 'libreria';
                                panelLibreria.show();
                                panelImagen.hide();
                            }
                        });
                        radioImagen.on('change', function() {
                            if ($(this).is(':checked')) {
                                tipoIcono = 'imagen';
                                panelLibreria.hide();
                                panelImagen.show();
                            }
                        });

                        // Manejo de archivo (solo previsualización local)
                        function mostrarPreview(file) {
                            if (file && file.name.toLowerCase().endsWith('.ico')) {
                                const objectUrl = URL.createObjectURL(file);
                                imgPreview.attr('src', objectUrl);
                                nombreArchivoSpan.text(file.name);
                                previewDiv.show();
                                btnQuitar.show();
                                archivoSeleccionado = file;
                                // Limpiar la ruta anterior para que no se envíe
                                rutaImagenActual = '';
                            } else {
                                toastr.error('Formato no válido. Solo se permiten archivos .ico');
                                fileInput.value = '';
                            }
                        }

                        function limpiarPreview() {
                            previewDiv.hide();
                            btnQuitar.hide();
                            imgPreview.attr('src', '');
                            nombreArchivoSpan.text('');
                            archivoSeleccionado = null;
                            fileInput.value = '';
                            // Si había una imagen anterior, mantenerla? Mejor mantenerla y si el usuario sube otra se reemplaza.
                            // No hacemos nada con rutaImagenActual aquí, se conserva.
                        }

                        uploadArea.addEventListener('click', () => fileInput.click());

                        fileInput.addEventListener('change', (e) => {
                            if (e.target.files && e.target.files[0]) {
                                mostrarPreview(e.target.files[0]);
                            }
                        });

                        uploadArea.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            uploadArea.classList.add('dragover');
                        });
                        uploadArea.addEventListener('dragleave', () => {
                            uploadArea.classList.remove('dragover');
                        });
                        uploadArea.addEventListener('drop', (e) => {
                            e.preventDefault();
                            uploadArea.classList.remove('dragover');
                            const file = e.dataTransfer.files[0];
                            if (file) mostrarPreview(file);
                        });

                        btnQuitar.on('click', () => limpiarPreview());
                    },
                    preConfirm: () => {
                        const name = $('#red-name').val();
                        const url = $('#red-url').val();
                        let iconValue = '';

                        if (!name || !url) {
                            Swal.showValidationMessage('Nombre y URL son obligatorios');
                            return false;
                        }

                        if (tipoIcono === 'libreria') {
                            const iconName = $('#iconoSocialHidden').val();
                            if (!iconName) {
                                Swal.showValidationMessage('Selecciona un ícono de la lista');
                                return false;
                            }
                            iconValue = `bi bi-${iconName}`;
                            return { name, icon: iconValue, url, active: $('#new-red-active').is(':checked'), archivo: null };
                        } else {
                            // Si el usuario seleccionó un nuevo archivo, usarlo; si no, mantener la ruta anterior
                            if (archivoSeleccionado) {
                                return { name, icon: null, url, active: $('#new-red-active').is(':checked'), archivo: archivoSeleccionado };
                            } else if (rutaImagenActual) {
                                return { name, icon: rutaImagenActual, url, active: $('#new-red-active').is(':checked'), archivo: null };
                            } else {
                                Swal.showValidationMessage('Debes seleccionar un archivo .ico o mantener la imagen existente');
                                return false;
                            }
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const data = result.value;
                        const formData = new FormData();
                        formData.append('opcion', 'GuardarFooterSocial');
                        formData.append('id', id);
                        formData.append('column_id', currentColumnIdForSocial);
                        formData.append('name', data.name);
                        formData.append('url', data.url);
                        formData.append('active', data.active ? 1 : 0);
                        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                        if (data.archivo) {
                            formData.append('icono_archivo', data.archivo);
                        } else {
                            formData.append('icon', data.icon);
                        }

                        $.ajax({
                            url: 'configuracionSitio',
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            success: (response) => {
                                if (response.success) {
                                    toastr.success('Red social actualizada');
                                    cargarRedesSociales(currentColumnIdForSocial);
                                } else {
                                    mostrarError('Error', response.message || 'No se pudo guardar');
                                }
                            },
                            error: (xhr) => {
                                console.error(xhr);
                                mostrarError('Error', 'Error al guardar la red social');
                            }
                        });
                    }
                });
            }
        }
    });
}

function guardarRedSocial(id, data) {
    $.ajax({
        url: 'configuracionSitio',
        method: 'POST',
        data: {
            opcion: 'GuardarFooterSocial',
            id: id,
            column_id: currentColumnIdForSocial,
            name: data.name,
            icon: data.icon,
            url: data.url,
            active: data.active ? 1 : 0,  
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json',
        success: (response) => {
            if (response.success) {
                toastr.success('Red social guardada');
                cargarRedesSociales(currentColumnIdForSocial);
            } else {
                mostrarError('Error', response.message);
            }
        }
    });
}

function eliminarRedSocial(id) {
    Swal.fire({
        title: '¿Eliminar red social?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: {
                    opcion: 'EliminarFooterSocial',
                    id: id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Red social eliminada');
                        cargarRedesSociales(currentColumnIdForSocial);
                    } else {
                        mostrarError('Error', response.message);
                    }
                }
            });
        }
    });
}

$('#btnNuevaRedSocial').click(function() {
    if (!currentColumnIdForSocial) {
        toastr.warning('Selecciona una columna primero');
        return;
    }

    let tipoIcono = 'libreria';
    let archivoSeleccionado = null; 

    Swal.fire({
        title: 'Nueva Red Social',
        html: `
            <div style="text-align: left">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de la red social</label>
                    <input type="text" id="red-name" class="swal2-input" placeholder="Ej: Facebook" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de ícono</label>
                    <div class="d-flex gap-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoIconoSocial" id="tipoLibreriaSocial" value="libreria" checked>
                            <label class="form-check-label" for="tipoLibreriaSocial">Ícono de librería</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoIconoSocial" id="tipoImagenSocial" value="imagen">
                            <label class="form-check-label" for="tipoImagenSocial">Imagen personalizada (.ico)</label>
                        </div>
                    </div>
                </div>

                <!-- Panel de íconos de librería (con buscador) -->
                <div id="panelLibreriaSocial" class="mb-3">
                    <label class="form-label fw-bold">Selecciona un ícono</label>
                    <div style="margin-bottom:10px;">
                        <input id="buscadorIconosSocial" class="swal2-input" placeholder="Buscar icono..."
                            style="border:2px solid #007bff; border-radius:10px; padding:8px;">
                    </div>
                    <div id="iconPickerSocial" class="icon-grid-modern">
                        <div class="text-center w-100 p-3">Cargando íconos...</div>
                    </div>
                    <input type="hidden" id="iconoSocialHidden" value="">
                </div>

                <!-- Panel de subida de imagen .ico -->
                <div id="panelImagenSocial" style="display: none;" class="mb-3">
                    <label class="form-label fw-bold">Subir icono (.ico)</label>
                    <div class="upload-area" id="uploadAreaSocial">
                        <i class="bi bi-cloud-upload fs-1 text-secondary"></i>
                        <p class="mt-2 mb-0">Haz clic o arrastra un archivo .ico</p>
                        <input type="file" id="uploadIconoSocial" accept=".ico" style="display: none;">
                    </div>
                    <div id="previewImagenSocial" style="display: none;">
                        <img id="imgPreviewSocial" src="" style="width: 40px; height: 40px; object-fit: contain;">
                        <span id="nombreArchivoSocial" class="flex-grow-1"></span>
                        <button type="button" id="btnQuitarArchivo" class="btn btn-sm btn-outline-danger" style="display: none;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">URL de la red social</label>
                    <input type="url" id="red-url" class="swal2-input" placeholder="https://facebook.com/miPagina" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="new-red-active" checked>
                    <label class="form-check-label">Activo</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Crear',
        cancelButtonText: 'Cancelar',
        width: '700px',
        didOpen: () => {
            const iconPicker = document.getElementById('iconPickerSocial');
            const hiddenIcon = document.getElementById('iconoSocialHidden');
            const buscador = document.getElementById('buscadorIconosSocial');
            const panelLibreria = $('#panelLibreriaSocial');
            const panelImagen = $('#panelImagenSocial');
            const radioLibreria = $('#tipoLibreriaSocial');
            const radioImagen = $('#tipoImagenSocial');
            const uploadArea = document.getElementById('uploadAreaSocial');
            const fileInput = document.getElementById('uploadIconoSocial');
            const previewDiv = $('#previewImagenSocial');
            const imgPreview = $('#imgPreviewSocial');
            const nombreArchivoSpan = $('#nombreArchivoSocial');
            const btnQuitar = $('#btnQuitarArchivo');

            // ========== GRID DE ÍCONOS ==========
            function cargarGrid(filtro = '') {
                if (!iconPicker) return;
                const iconos = window.iconosDisponibles || [];
                const termino = filtro.toLowerCase();
                let iconosFiltrados = iconos;
                if (termino) {
                    iconosFiltrados = iconos.filter(icono =>
                        icono.nombre.toLowerCase().includes(termino) ||
                        icono.icono.toLowerCase().includes(termino)
                    );
                }
                if (iconosFiltrados.length === 0) {
                    iconPicker.innerHTML = `<div class="text-center w-100 p-3">No se encontraron iconos con el término "${escapeHtml(filtro)}"</div>`;
                    return;
                }
                let html = '';
                iconosFiltrados.forEach(icono => {
                    const iconClass = `bi bi-${icono.icono}`;
                    const selected = (hiddenIcon.value === icono.icono) ? 'selected' : '';
                    html += `
                        <button type="button" class="icon-btn-modern ${selected}" data-icon="${icono.icono}">
                            <i class="${iconClass}"></i>
                            <span>${icono.nombre}</span>
                        </button>
                    `;
                });
                iconPicker.innerHTML = html;
                iconPicker.querySelectorAll('.icon-btn-modern').forEach(btn => {
                    btn.addEventListener('click', () => {
                        iconPicker.querySelectorAll('.icon-btn-modern').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        hiddenIcon.value = btn.dataset.icon;
                    });
                });
            }
            cargarGrid();

            if (buscador) {
                buscador.addEventListener('input', () => cargarGrid(buscador.value));
            }

            // ========== CAMBIO ENTRE TIPOS ==========
            radioLibreria.on('change', function() {
                if ($(this).is(':checked')) {
                    tipoIcono = 'libreria';
                    panelLibreria.show();
                    panelImagen.hide();
                }
            });
            radioImagen.on('change', function() {
                if ($(this).is(':checked')) {
                    tipoIcono = 'imagen';
                    panelLibreria.hide();
                    panelImagen.show();
                }
            });

            // ========== SUBIDA DE ARCHIVO (solo previsualización local) ==========
            function mostrarPreview(file) {
                if (file && file.name.toLowerCase().endsWith('.ico')) {
                    const objectUrl = URL.createObjectURL(file);
                    imgPreview.attr('src', objectUrl);
                    nombreArchivoSpan.text(file.name);
                    previewDiv.show();
                    btnQuitar.show();
                    archivoSeleccionado = file;
                } else {
                    toastr.error('Formato no válido. Solo se permiten archivos .ico');
                    fileInput.value = '';
                }
            }

            function limpiarPreview() {
                previewDiv.hide();
                btnQuitar.hide();
                imgPreview.attr('src', '');
                nombreArchivoSpan.text('');
                archivoSeleccionado = null;
                fileInput.value = '';
            }

            uploadArea.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    mostrarPreview(e.target.files[0]);
                }
            });

            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const file = e.dataTransfer.files[0];
                if (file) mostrarPreview(file);
            });

            btnQuitar.on('click', () => limpiarPreview());
        },
        preConfirm: () => {
            const name = $('#red-name').val();
            const url = $('#red-url').val();
            let iconValue = '';

            if (!name || !url) {
                Swal.showValidationMessage('Nombre y URL son obligatorios');
                return false;
            }

            if (tipoIcono === 'libreria') {
                const iconName = $('#iconoSocialHidden').val();
                if (!iconName) {
                    Swal.showValidationMessage('Selecciona un ícono de la lista');
                    return false;
                }
                iconValue = `bi bi-${iconName}`;
                return { name, icon: iconValue, url, active: $('#new-red-active').is(':checked'), archivo: null };
            } else {
                // Tipo imagen
                if (!archivoSeleccionado) {
                    Swal.showValidationMessage('Debes seleccionar un archivo .ico');
                    return false;
                }
                return { name, icon: null, url, active: $('#new-red-active').is(':checked'), archivo: archivoSeleccionado };
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            const formData = new FormData();
            formData.append('opcion', 'GuardarFooterSocial');
            formData.append('column_id', currentColumnIdForSocial);
            formData.append('name', data.name);
            formData.append('url', data.url);
            formData.append('active', data.active ? 1 : 0);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            if (tipoIcono === 'libreria') {
                formData.append('icon', data.icon);
            } else {
                formData.append('icono_archivo', data.archivo);
            }

            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Red social creada');
                        cargarRedesSociales(currentColumnIdForSocial);
                    } else {
                        mostrarError('Error', response.message || 'No se pudo guardar');
                    }
                },
                error: (xhr) => {
                    console.error(xhr);
                    mostrarError('Error', 'Error al guardar la red social');
                }
            });
        }
    });
});
$('#btnNuevaColumna').click(function() {
    let tipoIcono = 'libreria';
    let archivoSeleccionado = null;

    Swal.fire({
        title: 'Nueva Columna',
        html: `
            <style>
                .icon-grid-modern {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
                    gap: 12px;
                    padding: 8px;
                    max-height: 280px;
                    overflow-y: auto;
                }
                .icon-btn-modern {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 12px;
                    padding: 12px 8px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    width: 100%;
                }
                .icon-btn-modern i {
                    font-size: 28px;
                    margin-bottom: 8px;
                    color: #495057;
                }
                .icon-btn-modern span {
                    font-size: 12px;
                    color: #6c757d;
                }
                .icon-btn-modern:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                    border-color: #007bff;
                    background: #fff;
                }
                .icon-btn-modern.selected {
                    background: #007bff;
                    border-color: #007bff;
                }
                .icon-btn-modern.selected i,
                .icon-btn-modern.selected span {
                    color: white;
                }
                .upload-area {
                    border: 2px dashed #dee2e6;
                    border-radius: 12px;
                    padding: 20px;
                    text-align: center;
                    transition: all 0.2s;
                    cursor: pointer;
                }
                .upload-area:hover {
                    border-color: #007bff;
                    background: #f8f9ff;
                }
                .upload-area.dragover {
                    border-color: #007bff;
                    background: #e7f1ff;
                }
                #previewImagenCol {
                    margin-top: 15px;
                    padding: 10px;
                    background: #f1f3f5;
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
            </style>
            <div style="text-align: left">
                <div class="mb-3">
                    <label class="form-label fw-bold">Título</label>
                    <input type="text" id="col-title" class="swal2-input" placeholder="Ej: Enlaces útiles" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de columna</label>
                    <select id="col-type" class="swal2-select">
                        <option value="links">Solo enlaces</option>
                        <option value="mixed">Mixta (contacto + redes)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Ícono (opcional)</label>
                    <div class="d-flex gap-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoIconoCol" id="tipoLibreriaCol" value="libreria" checked>
                            <label class="form-check-label" for="tipoLibreriaCol">Ícono de librería</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoIconoCol" id="tipoImagenCol" value="imagen">
                            <label class="form-check-label" for="tipoImagenCol">Imagen personalizada (.ico)</label>
                        </div>
                    </div>
                </div>

                <div id="panelLibreriaCol" class="mb-3">
                    <label class="form-label fw-bold">Selecciona un ícono</label>
                    <div style="margin-bottom:10px;">
                        <input id="buscadorIconosCol" class="swal2-input" placeholder="Buscar icono..."
                            style="border:2px solid #007bff; border-radius:10px; padding:8px;">
                    </div>
                    <div id="iconPickerCol" class="icon-grid-modern">
                        <div class="text-center w-100 p-3">Cargando íconos...</div>
                    </div>
                    <input type="hidden" id="iconoColHidden" value="">
                </div>

                <div id="panelImagenCol" style="display: none;" class="mb-3">
                    <label class="form-label fw-bold">Subir icono (.ico)</label>
                    <div class="upload-area" id="uploadAreaCol">
                        <i class="bi bi-cloud-upload fs-1 text-secondary"></i>
                        <p class="mt-2 mb-0">Haz clic o arrastra un archivo .ico</p>
                        <input type="file" id="uploadIconoCol" accept=".ico" style="display: none;">
                    </div>
                    <div id="previewImagenCol" style="display: none;">
                        <img id="imgPreviewCol" src="" style="width: 40px; height: 40px; object-fit: contain;">
                        <span id="nombreArchivoCol" class="flex-grow-1"></span>
                        <button type="button" id="btnQuitarArchivoCol" class="btn btn-sm btn-outline-danger" style="display: none;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="new-col-active" checked>
                    <label class="form-check-label">Activa</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Crear',
        cancelButtonText: 'Cancelar',
        width: '700px',
        didOpen: () => {
            const iconPicker = document.getElementById('iconPickerCol');
            const hiddenIcon = document.getElementById('iconoColHidden');
            const buscador = document.getElementById('buscadorIconosCol');
            const panelLibreria = $('#panelLibreriaCol');
            const panelImagen = $('#panelImagenCol');
            const radioLibreria = $('#tipoLibreriaCol');
            const radioImagen = $('#tipoImagenCol');
            const uploadArea = document.getElementById('uploadAreaCol');
            const fileInput = document.getElementById('uploadIconoCol');
            const previewDiv = $('#previewImagenCol');
            const imgPreview = $('#imgPreviewCol');
            const nombreArchivoSpan = $('#nombreArchivoCol');
            const btnQuitar = $('#btnQuitarArchivoCol');

            function cargarGrid(filtro = '') {
                if (!iconPicker) return;
                const iconos = window.iconosDisponibles || [];
                const termino = filtro.toLowerCase();
                let iconosFiltrados = iconos;
                if (termino) {
                    iconosFiltrados = iconos.filter(icono =>
                        icono.nombre.toLowerCase().includes(termino) ||
                        icono.icono.toLowerCase().includes(termino)
                    );
                }
                if (iconosFiltrados.length === 0) {
                    iconPicker.innerHTML = `<div class="text-center w-100 p-3">No se encontraron iconos con el término "${escapeHtml(filtro)}"</div>`;
                    return;
                }
                let html = '';
                iconosFiltrados.forEach(icono => {
                    const iconClass = `bi bi-${icono.icono}`;
                    const selected = (hiddenIcon.value === icono.icono) ? 'selected' : '';
                    html += `
                        <button type="button" class="icon-btn-modern ${selected}" data-icon="${icono.icono}">
                            <i class="${iconClass}"></i>
                            <span>${icono.nombre}</span>
                        </button>
                    `;
                });
                iconPicker.innerHTML = html;
                iconPicker.querySelectorAll('.icon-btn-modern').forEach(btn => {
                    btn.addEventListener('click', () => {
                        iconPicker.querySelectorAll('.icon-btn-modern').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        hiddenIcon.value = btn.dataset.icon;
                    });
                });
            }
            cargarGrid();

            if (buscador) {
                buscador.addEventListener('input', () => cargarGrid(buscador.value));
            }

            radioLibreria.on('change', function() {
                if ($(this).is(':checked')) {
                    tipoIcono = 'libreria';
                    panelLibreria.show();
                    panelImagen.hide();
                }
            });
            radioImagen.on('change', function() {
                if ($(this).is(':checked')) {
                    tipoIcono = 'imagen';
                    panelLibreria.hide();
                    panelImagen.show();
                }
            });

            function mostrarPreview(file) {
                if (file && file.name.toLowerCase().endsWith('.ico')) {
                    const objectUrl = URL.createObjectURL(file);
                    imgPreview.attr('src', objectUrl);
                    nombreArchivoSpan.text(file.name);
                    previewDiv.show();
                    btnQuitar.show();
                    archivoSeleccionado = file;
                } else {
                    toastr.error('Formato no válido. Solo se permiten archivos .ico');
                    fileInput.value = '';
                }
            }

            function limpiarPreview() {
                previewDiv.hide();
                btnQuitar.hide();
                imgPreview.attr('src', '');
                nombreArchivoSpan.text('');
                archivoSeleccionado = null;
                fileInput.value = '';
            }

            uploadArea.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    mostrarPreview(e.target.files[0]);
                }
            });
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const file = e.dataTransfer.files[0];
                if (file) mostrarPreview(file);
            });
            btnQuitar.on('click', () => limpiarPreview());
        },
        preConfirm: () => {
            const title = $('#col-title').val();
            const columnType = $('#col-type').val();
            let iconValue = '';

            if (!title) {
                Swal.showValidationMessage('El título es obligatorio');
                return false;
            }

            if (tipoIcono === 'libreria') {
                const iconName = $('#iconoColHidden').val();
                if (iconName) iconValue = `bi bi-${iconName}`;
            } else {
                if (!archivoSeleccionado) {
                    Swal.showValidationMessage('Debes seleccionar un archivo .ico o elegir ícono de librería');
                    return false;
                }
            }

            return {
                title: title,
                column_type: columnType,
                active: $('#new-col-active').is(':checked'),
                icon: iconValue,
                archivo: archivoSeleccionado
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            const formData = new FormData();
            formData.append('opcion', 'GuardarFooterColumn');
            formData.append('title', data.title);
            formData.append('column_type', data.column_type);
            formData.append('active', data.active ? 1 : 0);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            if (tipoIcono === 'libreria' && data.icon) {
                formData.append('icon', data.icon);
            } else if (tipoIcono === 'imagen' && data.archivo) {
                formData.append('icono_archivo', data.archivo);
            }

            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Columna creada');
                        cargarFooterCompleto();
                    } else {
                        mostrarError('Error', response.message || 'No se pudo guardar');
                    }
                },
                error: (xhr) => {
                    console.error(xhr);
                    mostrarError('Error', 'Error al guardar la columna');
                }
            });
        }
    });
});
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getIconHtml(icon) {
    if (!icon) return '';
    if (icon.startsWith('/') || icon.match(/\.(ico|png|jpg|jpeg|gif|svg|webp)$/i)) {
        return `<img src="${icon}" alt="icono" style="width: 20px; height: 20px; object-fit: contain; margin-right: 8px;">`;
    } else {
        return `<i class="${icon}" style="margin-right: 8px;"></i>`;
    }
}

function limpiarIconoColumna(id) {
    Swal.fire({
        title: '¿Eliminar icono?',
        text: 'El icono o imagen de esta columna se eliminará permanentemente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: {
                    opcion: 'LimpiarIconoFooterColumn',
                    id: id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Icono eliminado');
                        cargarFooterCompleto(); // recarga la lista de columnas
                    } else {
                        mostrarError('Error', response.message || 'No se pudo eliminar');
                    }
                },
                error: (xhr) => {
                    console.error(xhr);
                    mostrarError('Error', 'Error al eliminar el icono');
                }
            });
        }
    });
}

function limpiarIconoEnlace(id) {
    Swal.fire({
        title: '¿Eliminar icono?',
        text: 'El icono o imagen de este enlace se eliminará permanentemente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'configuracionSitio',
                method: 'POST',
                data: {
                    opcion: 'LimpiarIconoFooterLink',
                    id: id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        toastr.success('Icono eliminado');
                        if (currentColumnIdForLinks) {
                            cargarEnlaces(currentColumnIdForLinks);
                        }
                    } else {
                        mostrarError('Error', response.message || 'No se pudo eliminar');
                    }
                },
                error: (xhr) => {
                    console.error(xhr);
                    mostrarError('Error', 'Error al eliminar el icono');
                }
            });
        }
    });
}