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
                    <button class="btn btn-sm btn-primary btn-permisos" data-id="${data.id}" title="Permisos">
                        <i class="bi bi-gear"></i>
                    </button>
                    <button class="btn btn-sm btn-warning btn-editar" data-id="${data.id}" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-eliminar" data-id="${data.id}" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
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

        $.post('administrarMenu', {
            opcion: 'Listar',
            id_padre: idPadre,
            id_rol: idRol,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function (res) {
            if (res.respuesta === 'ok') {
                tabla.rows.add(res.menus).draw();
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
                                <button class="btn btn-sm btn-primary btn-permisos" data-id="${hijo.id}" title="Permisos">
                                    <i class="bi bi-gear"></i>
                                </button>
                                <button class="btn btn-sm btn-warning btn-editar" data-id="${hijo.id}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="${hijo.id}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
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
            let filas = res.roles.map(rol => {
                let permiso = res.permisos[rol.id] || {};
                return `
                    <tr>
                        <td>${rol.name.toUpperCase()}</td>
                        <td class="text-center"><input type="checkbox" class="chkPermiso" data-campo="ver" data-rol="${rol.id}" ${permiso.ver ? 'checked' : ''}></td>
                        <td class="text-center"><input type="checkbox" class="chkPermiso" data-campo="crear" data-rol="${rol.id}" ${permiso.crear ? 'checked' : ''}></td>
                        <td class="text-center"><input type="checkbox" class="chkPermiso" data-campo="editar" data-rol="${rol.id}" ${permiso.editar ? 'checked' : ''}></td>
                        <td class="text-center"><input type="checkbox" class="chkPermiso" data-campo="eliminar" data-rol="${rol.id}" ${permiso.eliminar ? 'checked' : ''}></td>
                    </tr>
                `;
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
            // Crear Padre
            const icons = [
                "alarm", "app", "arrow-down", "arrow-left", "arrow-right", "arrow-up",
                "bag", "bell", "bookmark", "box", "calendar", "camera", "chat",
                "check", "clipboard", "cloud", "compass", "cpu", "download",
                "envelope", "eye", "file", "flag", "folder", "gear", "globe",
                "grid", "heart", "house", "image", "inbox", "key", "link",
                "list", "lock", "map", "mic", "music", "pencil", "person",
                "phone", "play", "plus", "printer", "search", "shield", "star",
                "trash", "unlock", "upload", "wallet", "wifi", "x-circle"
            ];

            let iconGrid = icons.map(icon => 
                `<button type="button" class="icon-btn" data-icon="bi bi-${icon}" style="font-size: 24px; margin: 4px;"> <i class="bi bi-${icon}"></i> </button>`
            ).join("");

            Swal.fire({
                title: 'Nuevo Padre',
                html: `
                    <input id="nombrePadre" class="swal2-input" placeholder="Nombre">
                    <input id="urlPadre" class="swal2-input" placeholder="URL">
                    <div id="iconPicker" style="display: flex; flex-wrap: wrap; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 5px;">
                        ${iconGrid}
                    </div>
                    <input type="hidden" id="iconoPadre">
                    <style>
                        .icon-btn { cursor: pointer; border: 1px solid #ccc; background: #fff; }
                        .icon-btn.selected { border-color: #007bff; background: #e7f1ff; }
                    </style>
                `,
                showCancelButton: true,          
                cancelButtonText: 'Cerrar',      
                didOpen: () => {
                    const buttons = document.querySelectorAll('#iconPicker .icon-btn');
                    buttons.forEach(btn => {
                        btn.addEventListener('click', () => {
                            buttons.forEach(b => b.classList.remove('selected'));
                            btn.classList.add('selected');
                            document.getElementById('iconoPadre').value = btn.dataset.icon;
                        });
                    });
                },
                preConfirm: () => {
                    return {
                        tipo: 'padre',
                        nombre: document.getElementById('nombrePadre').value,
                        url: document.getElementById('urlPadre').value,
                        icono: document.getElementById('iconoPadre').value
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
                // padre
                const icons = [
                    "alarm", "app", "arrow-down", "arrow-left", "arrow-right", "arrow-up",
                    "bag", "bell", "bookmark", "box", "calendar", "camera", "chat",
                    "check", "clipboard", "cloud", "compass", "cpu", "download",
                    "envelope", "eye", "file", "flag", "folder", "gear", "globe",
                    "grid", "heart", "house", "image", "inbox", "key", "link",
                    "list", "lock", "map", "mic", "music", "pencil", "person",
                    "phone", "play", "plus", "printer", "search", "shield", "star",
                    "trash", "unlock", "upload", "wallet", "wifi", "x-circle"
                ];

                let iconGrid = icons.map(icon => 
                    `<button type="button" class="icon-btn ${menu.icono === 'bi bi-'+icon ? 'selected' : ''}" 
                        data-icon="bi bi-${icon}" style="font-size: 24px; margin: 4px;">
                        <i class="bi bi-${icon}"></i>
                    </button>`
                ).join("");

                Swal.fire({
                    title: 'Editar Padre',
                    html: `
                        <input id="nombrePadre" class="swal2-input" value="${menu.nombre}" placeholder="Nombre">
                        <input id="urlPadre" class="swal2-input" value="${menu.url}" placeholder="URL">
                        <div id="iconPicker" style="display: flex; flex-wrap: wrap; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 5px;">
                            ${iconGrid}
                        </div>
                        <input type="hidden" id="iconoPadre" value="${menu.icono}">
                        <style>
                            .icon-btn { cursor: pointer; border: 1px solid #ccc; background: #fff; }
                            .icon-btn.selected { border-color: #007bff; background: #e7f1ff; }
                        </style>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    cancelButtonText: 'Cancelar',
                    didOpen: () => {
                        const buttons = document.querySelectorAll('#iconPicker .icon-btn');
                        buttons.forEach(btn => {
                            btn.addEventListener('click', () => {
                                buttons.forEach(b => b.classList.remove('selected'));
                                btn.classList.add('selected');
                                document.getElementById('iconoPadre').value = btn.dataset.icon;
                            });
                        });
                    },
                    preConfirm: () => {
                        return {
                            id: menu.id,
                            tipo: 'padre',
                            nombre: document.getElementById('nombrePadre').value,
                            url: document.getElementById('urlPadre').value,
                            icono: document.getElementById('iconoPadre').value
                        }
                    }
                }).then((res) => {
                    if (res.value) {
                        $.post('administrarMenu', {
                            opcion: 'Actualizar',
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

            } else {
                // hijo
                let opcionesPadres = '';
                window.padres.forEach(p => {
                    opcionesPadres += `<option value="${p.id}" ${menu.padre_id == p.id ? 'selected' : ''}>${p.nombre}</option>`;
                });

                Swal.fire({
                    title: 'Editar Hijo',
                    html: `
                        <select id="padreHijo" class="swal2-input">${opcionesPadres}</select>
                        <input id="nombreHijo" class="swal2-input" value="${menu.nombre}" placeholder="Nombre del hijo">
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        return {
                            id: menu.id,
                            tipo: 'hijo',
                            padre_id: $('#padreHijo').val(),
                            nombre: $('#nombreHijo').val()
                        }
                    }
                }).then((res) => {
                    if (res.value) {
                        $.post('administrarMenu', {
                            opcion: 'Actualizar',
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
        }
    }, 'json');
});
