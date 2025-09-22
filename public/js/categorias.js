let tablaCategorias;

$(document).ready(function() {
    // Inicializar Datatable
    tablaCategorias = $('#tablaCategorias').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'categorias',
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
                    permisosVista = json.permisosVista || {};
                    return json.categorias || [];
                } else {
                    Swal.fire('Error', json.mensaje, 'error');
                    return [];
                }
            }
        },
        columns: [
            {
                className: 'col-expandir text-center',
                orderable: false,
                data: null,
                render: function () {
                    return `
                        <button class="btn btn-sm btn-success btn-expandir">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                    `;
                }
            },
            { data: 'id', className: 'text-center' },
            { data: 'nombre', className: 'text-center' },
            { 
                data: 'created_at', 
                className: 'text-center',
                render: data => data ? new Date(data).toLocaleDateString() : ''
            },
            {
                data: null,
                className: 'text-center',
                render: data =>`
                        ${(permisosVista?.editar) ? `
                        <button class="btn btn-sm btn-warning btn-editar" data-id="${data.id}">
                            <i class="bi bi-pencil"></i>
                        </button>` : ''}
                        ${(permisosVista?.eliminar) ? `<button class="btn btn-sm btn-danger btn-eliminar" data-id="${data.id}">
                            <i class="bi bi-trash"></i>
                        </button>` : ''}
                    `
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

    // Generar filas de subcategorías
    function formatSubcategorias(subcategorias, parentId) {
        if (!subcategorias || subcategorias.length === 0) {
            return `
                <tr class="hijo-row" data-parent="${parentId}">
                    <td></td>
                    <td colspan="4" class="text-center text-white">
                        No tiene subcategorías
                    </td>
                </tr>
            `;
        }

        let html = '';
        subcategorias.forEach(s => {
            html += `
                <tr class="hijo-row" data-parent="${parentId}">
                    <td></td>
                    <td class="text-center">${s.id}</td>
                    <td>${s.nombre}</td>
                    <td class="text-center">${s.created_at ? new Date(s.created_at).toLocaleDateString() : ''}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-warning btn-editar-sub" data-id="${s.id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-eliminar-sub" data-id="${s.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        return html;
    }

    // Expandir / contraer
    $('#tablaCategorias tbody').on('click', 'button.btn-expandir', function () {
        let tr = $(this).closest('tr');
        let row = tablaCategorias.row(tr);
        let icono = $(this).find('i');
        let boton = $(this);

        if (tr.hasClass('expanded')) {
            // Ocultar subcategorías
            tr.removeClass('expanded');
            icono.removeClass('bi-dash-circle').addClass('bi-plus-circle');
            boton.removeClass('btn-danger').addClass('btn-success');
            // Eliminar las filas insertadas
            $('#tablaCategorias tbody tr.hijo-row[data-parent="' + row.data().id + '"]').remove();
        } else {
            // Mostrar subcategorías justo debajo
            tr.addClass('expanded');
            icono.removeClass('bi-plus-circle').addClass('bi-dash-circle');
            boton.removeClass('btn-success').addClass('btn-danger');
            tr.after(formatSubcategorias(row.data().subcategorias, row.data().id));
        }
    });

    // Modal categoría con selector de iconos
    function mostrarModalCategoria({ nombre = '', icono = '' }, callback) {
        Swal.fire({
            title: 'Categoría',
            html: `
                <input id="nombreCategoria" class="swal2-input" placeholder="Nombre" value="${nombre}">
                <div style="margin-bottom:10px;">
                    <input id="buscadorIconos" class="swal2-input" placeholder="Buscar icono..."
                        style="border:2px solid #007bff; border-radius:10px; box-shadow: 0 2px 5px rgba(0,0,0,0.15); padding:8px; transition: all 0.3s;">
                </div>
                <div id="iconPicker" style="display:flex; flex-wrap:wrap; gap:5px; max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:5px;">
                    ${generarGridIconos(icono)}
                </div>
                <input type="hidden" id="iconoCategoria" value="${icono}">
                <style>
                    .icon-btn { cursor:pointer; border:1px solid #ccc; background:#fff; padding:5px; border-radius:5px; transition: all 0.2s; }
                    .icon-btn:hover { transform: scale(1.1); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
                    .icon-btn.selected { border-color:#007bff; background:#e7f1ff; }
                </style>
            `,
            showCancelButton: true,
            allowOutsideClick: false, 
            allowEscapeKey: false,
            confirmButtonText: 'Guardar',
            didOpen: () => {
                const modal = Swal.getHtmlContainer();
                const buscador = modal.querySelector('#buscadorIconos');
                const iconPicker = modal.querySelector('#iconPicker');
                const inputHidden = modal.querySelector('#iconoCategoria');

                bindBotones(iconPicker, inputHidden);

                // Filtrado dinámico con mensaje si no hay resultados
                buscador.addEventListener('input', () => {
                    iconPicker.innerHTML = generarGridIconos(inputHidden.value, buscador.value);
                    bindBotones(iconPicker, inputHidden);
                });
            },
            preConfirm: () => ({
                nombre: document.getElementById('nombreCategoria').value,
                icono: 'bi bi-' + document.getElementById('iconoCategoria').value
            })
        }).then((res) => {
            if (res.isConfirmed && callback) callback(res.value);
        });
    }
cargarIconosGlobales(() => {
    // Botón Nuevo
    $('#btnNuevo').on('click', function() {
        mostrarModalCategoria({}, (datos) => {
            $.post('categorias', { opcion: 'Crear', ...datos, _token: $('meta[name="csrf-token"]').attr('content') }, function(resp) {
                if (resp.respuesta === 'ok') {
                    Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                        tablaCategorias.ajax.reload();
                    });
                } else {
                    Swal.fire('Error', resp.mensaje, 'error');
                }
            }, 'json');
        });
    });

    // Editar categoría
    $('#tablaCategorias tbody').on('click', '.btn-editar', function() {
        let id = $(this).data('id');
        $.post('categorias', { opcion: 'Editar', id, _token: $('meta[name="csrf-token"]').attr('content') }, function(res) {
            if (res.respuesta === 'ok') {
                mostrarModalCategoria(res.categoria, (datos) => {
                    $.post('categorias', { opcion: 'Actualizar', id, ...datos, _token: $('meta[name="csrf-token"]').attr('content') }, function(resp) {
                        if (resp.respuesta === 'ok') {
                            Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                                tablaCategorias.ajax.reload();
                            });
                        } else {
                            Swal.fire('Error', resp.mensaje, 'error');
                        }
                    }, 'json');
                });
            } else {
                Swal.fire('Error', res.mensaje, 'error');
            }
        }, 'json');
    });
});
    // Eliminar categoría
    $('#tablaCategorias tbody').on('click', '.btn-eliminar', function() {
        let id = $(this).data('id');

        Swal.fire({
            title: '¿Eliminar categoría?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            allowOutsideClick: false, 
            allowEscapeKey: false,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('categorias', { opcion: 'Eliminar', id, _token: $('meta[name="csrf-token"]').attr('content') }, function(resp) {
                    if (resp.respuesta === 'ok') {
                        Swal.fire('Eliminado', resp.mensaje, 'success');
                        tablaCategorias.ajax.reload();
                    } else {
                        Swal.fire('Error', resp.mensaje, 'error');
                    }
                }, 'json');
            }
        });
    });
});

// Modal Subcategoría
    function mostrarModalSubcategoria({ nombre = '', icono = '', id_categoria = '' }, callback) {
        $.post('categorias', { opcion: 'Listar', _token: $('meta[name="csrf-token"]').attr('content') }, function(res) {
            if (res.respuesta === 'ok') {
                let opciones = res.categorias.map(c => 
                    `<option value="${c.id}" ${id_categoria == c.id ? 'selected' : ''}>${c.nombre}</option>`
                ).join('');

                Swal.fire({
                    title: 'Subcategoría',
                    html: `
                        <input id="nombreSubcategoria" class="swal2-input" placeholder="Nombre" value="${nombre}">
                        <select id="categoriaSub" class="swal2-input">${opciones}</select>
                        <div style="margin-bottom:10px;">
                            <input id="buscadorIconos" class="swal2-input" placeholder="Buscar icono..." style="border:2px solid #007bff; border-radius:10px; box-shadow: 0 2px 5px rgba(0,0,0,0.15); padding:8px; transition: all 0.3s;">
                        </div>
                        <div id="iconPicker" style="display:flex; flex-wrap:wrap; gap:5px; max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:5px;">
                            ${generarGridIconos(icono)}
                        </div>
                        <input type="hidden" id="iconoSubcategoria" value="${icono}">
                        <style>
                            .icon-btn { cursor:pointer; border:1px solid #ccc; background:#fff; padding:5px; border-radius:5px; transition: all 0.2s; }
                            .icon-btn:hover { transform: scale(1.1); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
                            .icon-btn.selected { border-color:#007bff; background:#e7f1ff; }
                        </style>
                    `,
                    showCancelButton: true,
                    allowOutsideClick: false, 
                    allowEscapeKey: false,
                    confirmButtonText: 'Guardar',
                    didOpen: () => {
                        const modal = Swal.getHtmlContainer();
                        const buscador = modal.querySelector('#buscadorIconos');
                        const iconPicker = modal.querySelector('#iconPicker');
                        const inputHidden = modal.querySelector('#iconoSubcategoria');
                        bindBotones(iconPicker, inputHidden);

                        buscador.addEventListener('input', () => {
                            iconPicker.innerHTML = generarGridIconos(inputHidden.value, buscador.value);
                            bindBotones(iconPicker, inputHidden);
                        });
                    },
                    preConfirm: () => ({
                        nombre: document.getElementById('nombreSubcategoria').value,
                        icono: 'bi bi-' + document.getElementById('iconoSubcategoria').value,
                        id_categoria: document.getElementById('categoriaSub').value
                    })
                }).then((res) => {
                    if (res.isConfirmed && callback) callback(res.value);
                });
            }
        }, 'json');
    }

    // Botón nuevo
    $('#btnNuevoSub').on('click', function() {
        mostrarModalSubcategoria({}, (datos) => {
            $.post('categorias', { opcion: 'Crear_Subcategoria', ...datos, _token: $('meta[name="csrf-token"]').attr('content') }, function(resp) {
                if (resp.respuesta === 'ok') {
                    Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                        tablaCategorias.ajax.reload();
                    });
                } else {
                    Swal.fire('Error', resp.mensaje, 'error');
                }
            }, 'json');
        });
    });

    // Editar
    $('#tablaCategoriastbody').on('click', '.btn-editar', function() {
        let id = $(this).data('id');
        $.post('categorias', { opcion: 'Editar', id, _token: $('meta[name="csrf-token"]').attr('content') }, function(res) {
            if (res.respuesta === 'ok') {
                mostrarModalSubcategoria(res.subcategoria, (datos) => {
                    $.post('subcategorias', { opcion: 'Editar_Subcategoria', id, ...datos, _token: $('meta[name="csrf-token"]').attr('content') }, function(resp) {
                        if (resp.respuesta === 'ok') {
                            Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                                tablaCategorias.ajax.reload();
                            });
                        } else {
                            Swal.fire('Error', resp.mensaje, 'error');
                        }
                    }, 'json');
                });
            } else {
                Swal.fire('Error', res.mensaje, 'error');
            }
        }, 'json');
    });

    // Eliminar
    $('#tablaCategoriastbody').on('click', '.btn-eliminar', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar subcategoría?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            allowOutsideClick: false, 
            allowEscapeKey: false,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('categorias', { opcion: 'Eliminar_Subcategoria', id, _token: $('meta[name="csrf-token"]').attr('content') }, function(resp) {
                    if (resp.respuesta === 'ok') {
                        Swal.fire('Eliminado', resp.mensaje, 'success').then(() => {
                            tablaCategorias.ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', resp.mensaje, 'error');
                    }
                }, 'json');
            }
        });
    });