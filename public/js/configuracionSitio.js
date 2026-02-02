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
        
        console.log('Sortable inicializado correctamente');
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

    function cargarConfiguracionGeneral() {
        cargarConfiguracion(); 
    }
    
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