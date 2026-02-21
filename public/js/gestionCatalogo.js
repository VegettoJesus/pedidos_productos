let tablaCategorias, tablaSubcategorias;
let tipoActual = 'categoria';
let iconosFiltrados = [];

$(document).ready(function() {
    inicializarTablas();
    inicializarIconos();
});

function inicializarTablas() {
    // Inicializar DataTable de Categorías
    tablaCategorias = $('#tablaCategorias').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'gestionCatalogo',
            type: 'POST',
            data: function(d) {
                d.opcion = 'Listar';
                d._token = $('meta[name="csrf-token"]').attr('content');
            },
            beforeSend: function() {
                mensajesGlobalLoader = showPreloader("Cargando categorías...", "cargar");
            },
            dataSrc: function(json) {
                hidePreloader(mensajesGlobalLoader);
                if (json.respuesta === 'ok') {
                    window.permisosVista = json.permisosVista || {};
                    
                    // Cargar categorías para el filtro de subcategorías
                    cargarFiltroCategorias(json.categorias);
                    
                    return json.categorias || [];
                } else {
                    Swal.fire('Error', json.mensaje, 'error');
                    return [];
                }
            }
        },
        columns: [
            { data: 'id', className: 'text-center' },
            { 
                data: 'icono', 
                className: 'text-center',
                render: function(data) {
                    return data ? `<i class="${data}" style="font-size: 1.5rem;"></i>` : '<i class="bi bi-question-circle" style="font-size: 1.5rem;"></i>';
                }
            },
            { data: 'nombre', className: 'text-center' },
            { 
                data: 'subcategorias', 
                className: 'text-center',
                render: function(data) {
                    return data ? data.length : 0;
                }
            },
            { 
                data: 'created_at', 
                className: 'text-center',
                render: data => data ? new Date(data).toLocaleDateString('es-PE') : ''
            },
            {
                data: null,
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    let permisos = window.permisosVista || {};
                    let botones = '';

                    if (permisos.editar) {
                        botones += `
                            <button class="btn btn-sm btn-warning btn-editar-categoria" title="Editar" data-id="${row.id}">
                                <i class="bi bi-pencil"></i>
                            </button>
                        `;
                    }
                    
                    if (permisos.eliminar) {
                        let tieneSubcategorias = row.subcategorias && row.subcategorias.length > 0;
                        botones += `
                            <button class="btn btn-sm btn-danger btn-eliminar-categoria" 
                                    title="Eliminar" 
                                    data-id="${row.id}" 
                                    data-nombre="${row.nombre}" 
                                    data-tiene-sub="${tieneSubcategorias}"
                                    data-cantidad-sub="${row.subcategorias ? row.subcategorias.length : 0}">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                    }
                    
                    return botones || '<span class="text-muted">Sin permisos</span>';
                }
            }
        ],
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "No se encontraron registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros"
        }
    });

    // Inicializar DataTable de Subcategorías
    tablaSubcategorias = $('#tablaSubcategorias').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'gestionCatalogo',
            type: 'POST',
            data: function(d) {
                d.opcion = 'ListarSubcategorias';
                d.categoria_id = $('#filtroCategoria').val();
                d._token = $('meta[name="csrf-token"]').attr('content');
            },
            beforeSend: function() {
                mensajesGlobalLoader = showPreloader("Cargando subcategorías...", "cargar");
            },
            dataSrc: function(json) {
                hidePreloader(mensajesGlobalLoader);
                if (json.respuesta === 'ok') {
                    return json.subcategorias || [];
                } else {
                    Swal.fire('Error', json.mensaje, 'error');
                    return [];
                }
            }
        },
        columns: [
            { data: 'id', className: 'text-center' },
            { 
                data: 'icono', 
                className: 'text-center',
                render: function(data) {
                    return data ? `<i class="${data}" style="font-size: 1.5rem;"></i>` : '<i class="bi bi-question-circle" style="font-size: 1.5rem;"></i>';
                }
            },
            { data: 'nombre', className: 'text-center' },
            { 
                data: 'categoria', 
                className: 'text-center',
                render: function(data) {
                    return data ? data.nombre : '';
                }
            },
            { 
                data: 'created_at', 
                className: 'text-center',
                render: data => data ? new Date(data).toLocaleDateString('es-PE') : ''
            },
            {
                data: null,
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    let permisos = window.permisosVista || {};
                    let botones = '';

                    if (permisos.subcategoria) {
                        botones += `
                            <button class="btn btn-sm btn-warning btn-editar-subcategoria" title="Editar" data-id="${row.id}">
                                <i class="bi bi-pencil"></i>
                            </button>
                        `;
                        
                        botones += `
                            <button class="btn btn-sm btn-danger btn-eliminar-subcategoria" 
                                    title="Eliminar" 
                                    data-id="${row.id}" 
                                    data-nombre="${row.nombre}">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                    }
                    
                    return botones || '<span class="text-muted">Sin permisos</span>';
                }
            }
        ],
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "No se encontraron registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros"
        }
    });
}

function cargarFiltroCategorias(categorias) {
    let options = '<option value="">Todas las Categorías</option>';
    categorias.forEach(function(cat) {
        options += `<option value="${cat.id}">${cat.nombre}</option>`;
    });
    
    $('#filtroCategoria').html(options);
    $('#categoriaSubcategoria').html(options.replace('Todas las Categorías', 'Seleccionar Categoría'));
}

function inicializarIconos() {
    iconosFiltrados = window.iconosDisponibles || [];
    
    // Renderizar iconos iniciales
    if (window.iconosDisponibles && window.iconosDisponibles.length > 0) {
        renderizarIconos('categoria', '');
        renderizarIconos('subcategoria', '');
    } else {
        console.warn('No hay iconos disponibles');
    }
    
    // Eventos de búsqueda de iconos
    $('#iconoBuscarCategoria').on('input', function() {
        filtrarIconos($(this).val(), 'categoria');
    });
    
    $('#iconoBuscarSubcategoria').on('input', function() {
        filtrarIconos($(this).val(), 'subcategoria');
    });
    
    // Botones para limpiar icono seleccionado
    $('#btnLimpiarIconoCategoria').on('click', function() {
        $('#iconoCategoria').val('');
        $('#iconoPreviewCategoria i').attr('class', 'bi bi-question-circle');
        $('.icon-btn-categoria').removeClass('selected');
    });
    
    $('#btnLimpiarIconoSubcategoria').on('click', function() {
        $('#iconoSubcategoria').val('');
        $('#iconoPreviewSubcategoria i').attr('class', 'bi bi-question-circle');
        $('.icon-btn-subcategoria').removeClass('selected');
    });
}

function filtrarIconos(busqueda, tipo) {
    if (!busqueda) {
        renderizarIconos(tipo, '');
        return;
    }
    
    // Filtrar iconos por nombre o por icono
    let termino = busqueda.toLowerCase();
    let filtrados = window.iconosDisponibles.filter(icono => 
        icono.nombre.toLowerCase().includes(termino) || 
        icono.icono.toLowerCase().includes(termino)
    );
    
    renderizarIconos(tipo, '', filtrados);
}

function renderizarIconos(tipo, seleccionado = '', iconos = null) {
    let container = tipo === 'categoria' ? '#iconPickerCategoria' : '#iconPickerSubcategoria';
    let btnClass = tipo === 'categoria' ? 'icon-btn-categoria' : 'icon-btn-subcategoria';
    let inputHidden = tipo === 'categoria' ? '#iconoCategoria' : '#iconoSubcategoria';
    let previewIcon = tipo === 'categoria' ? '#iconoPreviewCategoria i' : '#iconoPreviewSubcategoria i';
    
    let iconosList = iconos || window.iconosDisponibles || [];
    
    if (iconosList.length === 0) {
        $(container).html('<div class="text-center p-3">No hay iconos disponibles</div>');
        return;
    }
    
    let html = '';
    
    iconosList.forEach(function(icono) {
        // Extraer el nombre del icono (sin el prefijo)
        let iconName = icono.icono;
        let iconClass = icono.prefijo + ' ' + icono.prefijo + '-' + iconName;
        
        // Determinar si está seleccionado
        let isSelected = '';
        if (seleccionado) {
            if (seleccionado === iconName || seleccionado === iconClass || seleccionado.includes(iconName)) {
                isSelected = 'selected';
            }
        }
        
        html += `<button type="button" class="icon-btn ${btnClass} ${isSelected}" data-icono="${iconName}" data-icono-completo="${iconClass}" title="${icono.nombre}">
                    <i class="${iconClass}"></i>
                </button>`;
    });
    
    $(container).html(html);
    
    // Eventos de selección de iconos (usando jQuery para mantener consistencia)
    $(`.${btnClass}`).on('click', function() {
        let iconoNombre = $(this).data('icono');
        let iconoCompleto = $(this).data('icono-completo');
        
        $(`.${btnClass}`).removeClass('selected');
        $(this).addClass('selected');
        
        $(inputHidden).val(iconoNombre);
        $(previewIcon).attr('class', iconoCompleto);
    });
}

// Filtrar subcategorías al cambiar el filtro
$('#filtroCategoria').on('change', function() {
    tablaSubcategorias.ajax.reload();
});

// Cambiar de tab y recargar datos si es necesario
$('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
    if ($(e.target).attr('id') === 'subcategorias-tab') {
        tablaSubcategorias.ajax.reload();
    }
});

// Botón Nueva Categoría
$('#btnNuevaCategoria').on('click', function() {
    tipoActual = 'categoria';
    $('#tituloModal').text('Nueva Categoría');
    $('#tipoEntidad').val('categoria');
    $('#idRegistro').val('');
    
    $('#camposCategoria').show();
    $('#camposSubcategoria').hide();
    
    $('#formCategoria')[0].reset();
    $('#iconoCategoria').val('');
    $('#iconoPreviewCategoria i').attr('class', 'bi bi-question-circle');
    $('.icon-btn-categoria').removeClass('selected');
    $('#iconoBuscarCategoria').val('');
    renderizarIconos('categoria', '');
    
    $('#modalCategoria').modal('show');
});

// Botón Nueva Subcategoría
$('#btnNuevaSubcategoria').on('click', function() {
    tipoActual = 'subcategoria';
    $('#tituloModal').text('Nueva Subcategoría');
    $('#tipoEntidad').val('subcategoria');
    $('#idRegistro').val('');
    
    $('#camposCategoria').hide();
    $('#camposSubcategoria').show();
    
    $('#formCategoria')[0].reset();
    $('#iconoSubcategoria').val('');
    $('#iconoPreviewSubcategoria i').attr('class', 'bi bi-question-circle');
    $('.icon-btn-subcategoria').removeClass('selected');
    $('#iconoBuscarSubcategoria').val('');
    renderizarIconos('subcategoria', '');
    
    $('#modalCategoria').modal('show');
});

// Guardar (Crear o Actualizar)
$('#btnGuardar').on('click', function() {
    let formData = new FormData();
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    
    if (tipoActual === 'categoria') {
        let id = $('#idRegistro').val();
        formData.append('opcion', id ? 'Actualizar' : 'Crear');
        formData.append('id', id);
        formData.append('nombre', $('#nombreCategoria').val());
        formData.append('icono', 'bi bi-' + $('#iconoCategoria').val());
    } else {
        let id = $('#idRegistro').val();
        formData.append('opcion', id ? 'Actualizar_Subcategoria' : 'Crear_Subcategoria');
        formData.append('id', id);
        formData.append('nombre', $('#nombreSubcategoria').val());
        formData.append('icono', 'bi bi-' + $('#iconoSubcategoria').val());
        formData.append('id_categoria', $('#categoriaSubcategoria').val());
    }

    $.ajax({
        url: 'gestionCatalogo',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.respuesta === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.mensaje,
                    timer: 2000
                });
                
                $('#modalCategoria').modal('hide');
                
                if (tipoActual === 'categoria') {
                    tablaCategorias.ajax.reload();
                } else {
                    tablaSubcategorias.ajax.reload();
                    tablaCategorias.ajax.reload(); // Para actualizar el contador de subcategorías
                }
            } else {
                Swal.fire('Error', response.mensaje, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Ocurrió un error inesperado', 'error');
        }
    });
});

// Editar Categoría
$(document).on('click', '.btn-editar-categoria', function() {
    let id = $(this).data('id');
    tipoActual = 'categoria';
    
    $.ajax({
        url: 'gestionCatalogo',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            opcion: 'Editar',
            id: id
        },
        success: function(response) {
            if (response.respuesta === 'ok') {
                let categoria = response.categoria;
                
                $('#tituloModal').text('Editar Categoría');
                $('#tipoEntidad').val('categoria');
                $('#idRegistro').val(categoria.id);
                $('#nombreCategoria').val(categoria.nombre);
                
                // Extraer nombre del icono
                let iconoNombre = '';
                if (categoria.icono) {
                    if (categoria.icono.includes('bi-')) {
                        iconoNombre = categoria.icono.split('bi-')[1];
                    } else {
                        iconoNombre = categoria.icono;
                    }
                }
                
                $('#iconoCategoria').val(iconoNombre);
                $('#iconoPreviewCategoria i').attr('class', categoria.icono || 'bi bi-question-circle');
                
                $('#camposCategoria').show();
                $('#camposSubcategoria').hide();
                
                renderizarIconos('categoria', iconoNombre);
                $('#iconoBuscarCategoria').val('');
                
                $('#modalCategoria').modal('show');
            } else {
                Swal.fire('Error', response.mensaje, 'error');
            }
        }
    });
});

// Editar Subcategoría
$(document).on('click', '.btn-editar-subcategoria', function() {
    let id = $(this).data('id');
    tipoActual = 'subcategoria';
    
    $.ajax({
        url: 'gestionCatalogo',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            opcion: 'Editar_Subcategoria',
            id: id
        },
        success: function(response) {
            if (response.respuesta === 'ok') {
                let subcategoria = response.subcategoria;
                
                $('#tituloModal').text('Editar Subcategoría');
                $('#tipoEntidad').val('subcategoria');
                $('#idRegistro').val(subcategoria.id);
                $('#nombreSubcategoria').val(subcategoria.nombre);
                $('#categoriaSubcategoria').val(subcategoria.id_categoria);
                
                // Extraer nombre del icono
                let iconoNombre = '';
                if (subcategoria.icono) {
                    if (subcategoria.icono.includes('bi-')) {
                        iconoNombre = subcategoria.icono.split('bi-')[1];
                    } else {
                        iconoNombre = subcategoria.icono;
                    }
                }
                
                $('#iconoSubcategoria').val(iconoNombre);
                $('#iconoPreviewSubcategoria i').attr('class', subcategoria.icono || 'bi bi-question-circle');
                
                $('#camposCategoria').hide();
                $('#camposSubcategoria').show();
                
                renderizarIconos('subcategoria', iconoNombre);
                $('#iconoBuscarSubcategoria').val('');
                
                $('#modalCategoria').modal('show');
            } else {
                Swal.fire('Error', response.mensaje, 'error');
            }
        }
    });
});

// Eliminar Categoría
$(document).on('click', '.btn-eliminar-categoria', function() {
    let id = $(this).data('id');
    let nombre = $(this).data('nombre');
    let tieneSub = $(this).data('tiene-sub') === true || $(this).data('tiene-sub') === '1';
    let cantidadSub = $(this).data('cantidad-sub') || 0;
    
    let texto = '';
    if (tieneSub) {
        texto = `La categoría <strong>${nombre}</strong> tiene <strong>${cantidadSub} subcategoría(s)</strong> asociada(s).<br><br>
                Al eliminar la categoría, también se eliminarán TODAS sus subcategorías.<br><br>
                ¿Estás seguro de continuar?`;
    } else {
        texto = `¿Estás seguro de eliminar la categoría <strong>${nombre}</strong>?`;
    }
    
    Swal.fire({
        title: '¿Eliminar Categoría?',
        html: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'gestionCatalogo',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    opcion: 'Eliminar',
                    id: id
                },
                success: function(response) {
                    if (response.respuesta === 'ok') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: response.mensaje,
                            timer: 2000
                        });
                        
                        tablaCategorias.ajax.reload();
                        tablaSubcategorias.ajax.reload();
                    } else {
                        Swal.fire('Error', response.mensaje, 'error');
                    }
                }
            });
        }
    });
});

// Eliminar Subcategoría
$(document).on('click', '.btn-eliminar-subcategoria', function() {
    let id = $(this).data('id');
    let nombre = $(this).data('nombre');
    
    Swal.fire({
        title: '¿Eliminar Subcategoría?',
        html: `¿Estás seguro de eliminar la subcategoría <strong>${nombre}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'gestionCatalogo',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    opcion: 'Eliminar_Subcategoria',
                    id: id
                },
                success: function(response) {
                    if (response.respuesta === 'ok') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: response.mensaje,
                            timer: 2000
                        });
                        
                        tablaSubcategorias.ajax.reload();
                        tablaCategorias.ajax.reload(); // Actualizar contador de subcategorías
                    } else {
                        Swal.fire('Error', response.mensaje, 'error');
                    }
                }
            });
        }
    });
});

// Estilos adicionales para los iconos
$('<style>')
    .prop('type', 'text/css')
    .html(`
        .icon-btn {
            cursor: pointer;
            border: 1px solid #ccc;
            background: #fff;
            padding: 8px;
            border-radius: 5px;
            transition: all 0.2s;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 2px;
        }
        .icon-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border-color: #007bff;
        }
        .icon-btn.selected {
            border-color: #007bff;
            background: #e7f1ff;
        }
        .icon-picker-container {
            background: #f8f9fa;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .icon-picker-container::-webkit-scrollbar {
            width: 8px;
        }
        .icon-picker-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .icon-picker-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .icon-picker-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    `)
    .appendTo('head');