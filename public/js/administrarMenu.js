$(document).ready(function () {
    let tabla = $('#tablaMenus').DataTable({
        columns: [
            {
                data: null,
                className: "text-center",
                orderable: false,
                render: data => `<button class="btn btn-sm btn-info btn-expandir" data-id="${data.id}">+</button>`
            },
            { data: 'id', className: 'text-center' },
            { data: 'nombre' },
            { data: 'url' },
            { data: 'orden', className: 'text-center' },
            { data: 'created_at', className: 'text-center' },
            { data: 'updated_at', className: 'text-center' },
            {
                data: null,
                className: 'text-center',
                render: data => `
                    ${(data.permisosVista?.configurar) ? `
                    <button class="btn btn-sm btn-primary btn-permisos" data-id="${data.id}" title="Permisos">
                        <i class="bi bi-gear"></i>
                    </button>` : ''}
                    ${(data.permisosVista?.editar) ? `
                    <button class="btn btn-sm btn-warning btn-editar" data-id="${data.id}" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>` : ''}
                    ${(data.permisosVista?.configurar) ? `
                    <button class="btn btn-sm btn-info btn-url" data-id="${data.id}" title="Actualizar URL">
                        <i class="bi bi-link"></i>
                    </button>` : ''}
                    ${(data.permisosVista?.eliminar) ? `
                    <button class="btn btn-sm btn-danger btn-eliminar" data-id="${data.id}" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>` : ''}
                `
            }
        ]
    });

    cargarTabla();

    $('#btnBuscar').on('click', function () {
        cargarTabla();
    });

    function cargarTabla() {
        tabla.clear().draw();
        let idPadre = $('#filtro_padre').val();
        let idRol   = $('#filtro_permiso').val();
        mensajesGlobalLoader = showPreloader("Cargando menús...", "cargar");
        $.post('administrarMenu', {
            opcion: 'Listar',
            id_padre: idPadre,
            id_rol: idRol,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function (res) {
            if (res.respuesta === 'ok') {
                let menusConPermisos = res.menus.map(menu => ({
                    ...menu,
                    permisosVista: res.permisosVista
                }));
                tabla.rows.add(menusConPermisos).draw();
                hidePreloader(mensajesGlobalLoader);
            }
        }, 'json');
    }


    $('#tablaMenus tbody').on('click', '.btn-expandir', function () {
        let btn = $(this);
        let idPadre = btn.data('id');
        let filaPadre = tabla.row(btn.parents('tr'));

        if (btn.hasClass('btn-info')) {
            $.post('administrarMenu', {
                opcion: 'Listar',
                id_padre: idPadre,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function (res) {
                if (res.respuesta === 'ok' && res.menus.length > 0) {
                     console.log(res)
                    let filasHijos = res.menus.map(hijo => `
                        <tr class="table-secondary hijo-row hijo-row-${idPadre}">
                            <td></td>
                            <td class="text-center">${hijo.id}</td>
                            <td>${hijo.nombre}</td>
                            <td>${hijo.url ?? ''}</td>
                            <td class="text-center">${hijo.orden}</td>
                            <td class="text-center">${hijo.created_at}</td>
                            <td class="text-center">${hijo.updated_at}</td>
                            <td class="text-center">
                                ${(res.permisosVista?.configurar) ? `
                                    <button class="btn btn-sm btn-primary btn-permisos" data-id="${hijo.id}" title="Permisos">
                                        <i class="bi bi-gear"></i>
                                    </button>` : ''}
                                ${(res.permisosVista?.editar) ? `
                                    <button class="btn btn-sm btn-warning btn-editar" data-id="${hijo.id}" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>` : ''}
                                ${(res.permisosVista?.configurar) ? `
                                    <button class="btn btn-sm btn-info btn-url" data-id="${hijo.id}" title="Actualizar URL">
                                        <i class="bi bi-link"></i>
                                    </button>` : ''}
                                ${(res.permisosVista?.eliminar) ? `
                                    <button class="btn btn-sm btn-danger btn-eliminar" data-id="${hijo.id}" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>` : ''}
                            </td>
                    `).join('');

                    $(filaPadre.node()).after(filasHijos);
                    btn.removeClass('btn-info').addClass('btn-danger').text('-');
                }
            }, 'json');
        } else {
            $(`.hijo-row-${idPadre}`).remove();
            btn.removeClass('btn-danger').addClass('btn-info').text('+');
        }
    });
});

$('#tablaMenus').on('click', '.btn-permisos', function () {
    let idMenu = $(this).data('id');

    $.post('administrarMenu', {
        opcion: 'Permisos',
        id_menu: idMenu,
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function (res) {
        if (res.respuesta === 'ok') {

            // Lista de todos los permisos posibles
            let permisosDisponibles = ['ver','crear','editar','eliminar','configurar', 'roles'];

            // Generar cabeceras
            let ths = '<th>Rol</th>';
            permisosDisponibles.forEach(p => {
                ths += `<th class="text-center">${p.charAt(0).toUpperCase() + p.slice(1)}</th>`;
            });
            $('#tablaPermisos thead tr').html(ths);

            // Generar filas
            let filas = res.roles.map(rol => {
                let permiso = res.permisos[rol.id] || {}; // puede venir vacío
                let celdas = permisosDisponibles.map(p => `
                    <td class="text-center">
                        <input type="checkbox" class="chkPermiso" data-campo="${p}" data-rol="${rol.id}" ${permiso[p] ? 'checked' : ''}>
                    </td>
                `).join('');

                return `<tr>
                    <td>${rol.name.toUpperCase()}</td>
                    ${celdas}
                </tr>`;
            }).join('');

            $('#tablaPermisos tbody').html(filas);
            $('#modalPermisos').data('idMenu', idMenu).modal('show');
        }
    }, 'json');
});

// Guardar cambios al marcar/desmarcar
$('#tablaPermisos').on('change', '.chkPermiso', function () {
    let idMenu = $('#modalPermisos').data('idMenu');
    let idRol = $(this).data('rol');
    let campo = $(this).data('campo');
    let valor = $(this).is(':checked') ? 1 : 0;

    $.post('administrarMenu', {
        opcion: 'ActualizarPermiso',
        id_menu: idMenu,
        id_rol: idRol,
        campo: campo,
        valor: valor,
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function (res) {
        if (res.respuesta === 'ok') {
            Swal.fire({
                icon: 'success',        
                title: 'Operación exitosa',
                text: res.mensaje,      
                confirmButtonText: 'OK'
            });
        }
    }, 'json');
});
$('#modalPermisos').on('hidden.bs.modal', function () {
    location.reload();
});

function mostrarModalPadre({ titulo = "Nuevo Padre", nombre = "", url = "", icono = "" }, callback) {
    const iconGrid = generarGridIconos(icono);

    Swal.fire({
        title: titulo,
        html: `
            <input id="nombrePadre" class="swal2-input" value="${nombre}" placeholder="Nombre">
            <input id="urlPadre" class="swal2-input" value="${url}" placeholder="URL">
            <div style="margin-bottom:10px;">
                <input id="buscadorIconos" class="swal2-input" placeholder="Buscar icono..."
                       style="border:2px solid #007bff; border-radius:10px; box-shadow: 0 2px 5px rgba(0,0,0,0.15); padding:8px; transition: all 0.3s;">
            </div>
            <div id="iconPicker" style="display:flex; flex-wrap:wrap; gap:5px; max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:5px;">
                ${iconGrid}
            </div>
            <input type="hidden" id="iconoPadre" value="${icono}">
            <style>
                .icon-btn { cursor:pointer; border:1px solid #ccc; background:#fff; padding:5px; border-radius:5px; transition: all 0.2s; }
                .icon-btn:hover { transform: scale(1.1); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
                .icon-btn.selected { border-color:#007bff; background:#e7f1ff; }
            </style>
        `,
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        preConfirm: () => ({
            nombre: document.getElementById('nombrePadre').value,
            url: document.getElementById('urlPadre').value,
            icono: 'bi bi-' + document.getElementById('iconoPadre').value
        }),
        didOpen: () => {
            const modal = Swal.getHtmlContainer();
            const buscador = modal.querySelector('#buscadorIconos');
            const iconPicker = modal.querySelector('#iconPicker');
            const inputHidden = modal.querySelector('#iconoPadre');

            bindBotones(iconPicker, inputHidden);

            // Filtrado dinámico con mensaje si no hay resultados
            buscador.addEventListener('input', () => {
                iconPicker.innerHTML = generarGridIconos(inputHidden.value, buscador.value);
                bindBotones(iconPicker, inputHidden);
            });
        }
    }).then(res => {
        if (res.isConfirmed && callback) callback(res.value);
    });
}
cargarIconosGlobales(() => {
    $('#btnNuevo').on('click', function () {
        Swal.fire({
            title: 'Crear Menú',
            text: '¿Qué deseas crear?',
            showDenyButton: true,
            showCancelButton: true,          
            confirmButtonText: 'Padre',
            denyButtonText: 'Hijo',
            cancelButtonText: 'Cerrar'       
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarModalPadre({}, (data) => {
                    $.post('administrarMenu', { opcion: 'Crear', tipo: 'padre', ...data, _token: $('meta[name="csrf-token"]').attr('content') }, function (resp) {
                        if (resp.respuesta === 'ok') {
                            Swal.fire('Éxito', resp.mensaje, 'success').then(() => window.location.reload());
                        } else Swal.fire('Error', resp.mensaje, 'error');
                    }, 'json');
                });

            } else if (result.isDenied) {
                // Crear Hijo
                let opcionesPadres = '';
                window.padres.forEach(p => {
                    opcionesPadres += `<option value="${p.id}">${p.nombre}</option>`;
                });

                Swal.fire({
                    title: 'Nuevo Hijo',
                    html: `
                        <select id="padreHijo" class="swal2-input">${opcionesPadres}</select>
                        <input id="nombreHijo" class="swal2-input" placeholder="Nombre del hijo">
                    `,
                    showCancelButton: true,          
                    cancelButtonText: 'Cerrar',      
                    preConfirm: () => {
                        return {
                            tipo: 'hijo',
                            padre_id: $('#padreHijo').val(),
                            nombre: $('#nombreHijo').val()
                        }
                    }
                }).then((res) => {
                    if (res.value) {
                        $.post('administrarMenu', {
                            opcion: 'Crear',
                            ...res.value,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        }, function (data) {
                            if (data.respuesta === 'ok') {
                                Swal.fire({
                                    title: 'Éxito',
                                    text: data.mensaje,
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.mensaje, 'error');
                            }
                        }, 'json');
                    }
                });
            }
        });
    });
    // Evento botón Editar
    $('#tablaMenus tbody').on('click', '.btn-editar', function () {
        let idMenu = $(this).data('id');

        $.post('administrarMenu', {
            opcion: 'Editar',
            id_menu: idMenu,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function (res) {
            if (res.respuesta === 'ok') {
                let menu = res.menu;

                if (menu.tipo === 'padre') {
                    mostrarModalPadre({
                        titulo: 'Editar Padre',
                        nombre: menu.nombre,
                        url: menu.url,
                        icono: menu.icono
                    }, (data) => {
                        // Actualizamos el padre
                        $.post('administrarMenu', {
                            opcion: 'Actualizar',
                            id: menu.id,
                            tipo: 'padre',
                            ...data,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        }, function (resp) {
                            if (resp.respuesta === 'ok') {
                                Swal.fire('Éxito', resp.mensaje, 'success').then(() => window.location.reload());
                            } else Swal.fire('Error', resp.mensaje, 'error');
                        }, 'json');
                    });
                } else {
                    // Hijo
                    let opcionesPadres = window.padres.map(p => 
                        `<option value="${p.id}" ${menu.padre_id == p.id ? 'selected' : ''}>${p.nombre}</option>`
                    ).join('');

                    Swal.fire({
                        title: 'Editar Hijo',
                        html: `
                            <select id="padreHijo" class="swal2-input">${opcionesPadres}</select>
                            <input id="nombreHijo" class="swal2-input" value="${menu.nombre}" placeholder="Nombre del hijo">
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Actualizar',
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => ({
                            id: menu.id,
                            tipo: 'hijo',
                            padre_id: $('#padreHijo').val(),
                            nombre: $('#nombreHijo').val()
                        })
                    }).then((res) => {
                        if (res.value) {
                            $.post('administrarMenu', {
                                opcion: 'Actualizar',
                                ...res.value,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            }, function (resp) {
                                if (resp.respuesta === 'ok') {
                                    Swal.fire('Éxito', resp.mensaje, 'success').then(() => window.location.reload());
                                } else Swal.fire('Error', resp.mensaje, 'error');
                            }, 'json');
                        }
                    });
                }
            }
        }, 'json');
    });
});

$('#tablaMenus tbody').on('click', '.btn-eliminar', function () {
    let idMenu = $(this).data('id');

    Swal.fire({
        title: '¿Estás seguro?',
        text: "Este menú será eliminado.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('administrarMenu', {
                opcion: 'Eliminar',
                id_menu: idMenu,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function (res) {
                if (res.respuesta === 'ok') {
                    Swal.fire({
                        title: 'Éxito',
                        text: res.mensaje,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', res.mensaje, 'error');
                }
            }, 'json');
        }
    });
});
$('#tablaMenus tbody').on('click', '.btn-url', function () {
    let idMenu = $(this).data('id');

    Swal.fire({
        title: 'Actualizar URL',
        input: 'text',
        inputLabel: 'Nueva URL',
        inputPlaceholder: 'Ej: dashboard/reportes',
        showCancelButton: true,
        confirmButtonText: 'Actualizar',
        cancelButtonText: 'Cancelar',
        preConfirm: (url) => {
            if (!url) {
                Swal.showValidationMessage('La URL no puede estar vacía');
            }
            return url;
        }
    }).then((res) => {
        if (res.value) {
            $.post('administrarMenu', {
                opcion: 'ActualizarURL',
                id_menu: idMenu,
                url: res.value,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function (data) {
                if (data.respuesta === 'ok') {
                    Swal.fire('Éxito', data.mensaje, 'success')
                        .then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.mensaje, 'error');
                }
            }, 'json');
        }
    });
});
