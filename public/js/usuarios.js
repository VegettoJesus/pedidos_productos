let tablaUsuarios;
let marcadorSeleccionado = null;
let mapa = null;
let direccionSeleccionada = '';
let codigoPostalSeleccionado = '';
let searchTimeout = null;

const togglePassword = document.querySelector('#togglePassword');
  const password = document.querySelector('#password');

  togglePassword.addEventListener('click', function () {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.querySelector('i').classList.toggle('bi-eye');
    this.querySelector('i').classList.toggle('bi-eye-slash');
  });

$(document).ready(function () {
    $('#departamento_id').on('change', function () {
        let departamento_id = $(this).val();
        let provinciaSeleccionada = $('#provincia_id').attr('data-selected');
        let distritoSeleccionado = $('#distrito_id').attr('data-selected');
        
        if (departamento_id) {
            $.get('/get-provincias/' + departamento_id, function (data) {
                $('#provincia_id').prop('disabled', false).empty().append('<option value="">Seleccione</option>');
                $('#distrito_id').prop('disabled', true).empty().append('<option value="">Seleccione</option>');

                $.each(data, function (key, provincia) {
                    $('#provincia_id').append('<option value="' + provincia.id + '">' + provincia.nombre + '</option>');
                });

                // Si hay una provincia seleccionada, seleccionarla
                if (provinciaSeleccionada) {
                    $('#provincia_id').val(provinciaSeleccionada).trigger('change');
                    // Limpiar después de usar
                    $('#provincia_id').removeAttr('data-selected');
                }
            });
        } else {
            $('#provincia_id, #distrito_id').prop('disabled', true).empty().append('<option value="">Seleccione</option>');
            $('#provincia_id, #distrito_id').removeAttr('data-selected');
        }
    });

    $('#provincia_id').on('change', function () {
        let provincia_id = $(this).val();
        let distritoSeleccionado = $('#distrito_id').attr('data-selected');
        
        if (provincia_id) {
            $.get('/get-distritos/' + provincia_id, function (data) {
                $('#distrito_id').prop('disabled', false).empty().append('<option value="">Seleccione</option>');
                $.each(data, function (key, distrito) {
                    $('#distrito_id').append('<option value="' + distrito.id + '">' + distrito.nombre + '</option>');
                });

                // Si hay un distrito seleccionado, seleccionarlo
                if (distritoSeleccionado) {
                    $('#distrito_id').val(distritoSeleccionado);
                    // Limpiar después de usar
                    $('#distrito_id').removeAttr('data-selected');
                }
            });
        } else {
            $('#distrito_id').prop('disabled', true).empty().append('<option value="">Seleccione</option>');
        }
    });

    tablaUsuarios = $('#tablaUsuarios').DataTable({
        processing: true,
        serverSide: false,
        dom: '<"row mb-2"<"col-lg-10 col-md-12 col-sm-12 col-xs-12 d-flex flex-wrap gap-2 align-items-center"lB><"col-lg-2 col-md-12 col-sm-12 col-xs-12"f>>tip',
        ajax: {
            url: 'usuarios',
            type: 'POST',
            data: function (d) {
                d.opcion = 'Listar';
                d.filtro_estado = $('#filtro_estado').val();
                d.filtro_roles = $('#filtro_roles').val();
                d._token = $('meta[name="csrf-token"]').attr('content');
            },
            beforeSend: function () {
                mensajesGlobalLoader = showPreloader("Cargando usuarios...", "cargar");
            },
            dataSrc: function (json) {
                if (json.respuesta === 'ok') {
                    permisosVista = json.permisosVista || {};
                    return json.usuarios || [];
                } else {
                    Swal.fire('Error', json.mensaje, 'error');
                    return [];
                }
            },
            complete: function () {
                hidePreloader(mensajesGlobalLoader);
            }
        },
        columns: [
            { 
                data: null, 
                className: 'text-center',
                render: data => `<input type="checkbox" class="check-usuario" value="${data.id}">`
            },
            { data: 'id', className: 'text-center' },
            { data: 'nombres', className: 'text-center' },
            { data: 'apellidos', className: 'text-center' },
            { data: 'email', className: 'text-center' },
            { data: 'rol.name', className: 'text-center' }, 
            { 
                data: 'estado', 
                className: 'text-center',
                render: data => {
                    if(data == 1){
                        return '<span class="badge bg-success">ACTIVO</span>';
                    } else {
                        return '<span class="badge bg-danger">INACTIVO</span>';
                    }
                }
            },
            { 
                data: 'conectado', 
                className: 'text-center',
                render: data => {
                    if(data == 1){
                        return '<span class="badge bg-success">En línea</span>';
                    } else {
                        return '<span class="badge bg-danger">Desconectado</span>';
                    }
                }
            },
            { data: 'created_at', className: 'text-center' },
            { data: 'updated_at', className: 'text-center' },
            { data: 'deleted_at', className: 'text-center' },
            { 
                data: null, 
                className: 'text-center',
                render: data => {
                    let botones = `
                        <button class="btn btn-sm btn-info btn-detalle" data-id="${data.id}" title="Ver Detalle">
                            <i class="bi bi-eye"></i>
                        </button>
                    `;

                    if (data.id != 1) { 
                        if (permisosVista.editar) {
                            botones += `
                                <button class="btn btn-sm btn-warning btn-editar" data-id="${data.id}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            `;
                        }
                        if (permisosVista.eliminar) {
                            botones += `
                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="${data.id}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                    }

                    return botones;
                }
            }
        ],
        initComplete: function () {
        if (permisosVista.eliminar) {
            $('.dataTables_length').append(`
                <button id="btnEliminarSeleccionados" class="btn btn-danger w-lg-100 w-md-100 w-sm-100 w-xs-100 mb-2 mb-md-0 d-none">
                    <i class="bi bi-trash"></i> Eliminar seleccionados
                </button>
            `);

            $('#btnEliminarSeleccionados').on('click', function () {
                eliminarSeleccionados();
            });
        }

        if (permisosVista.roles) { 
            $('.dataTables_length').append(`
                <button id="btnRoles" class="btn btn-primary w-lg-100 w-md-100 w-sm-100 w-xs-100 mb-2 mb-md-0">
                    <i class="bi bi-person-badge"></i> Roles
                </button>
            `);

            $('#btnRoles').on('click', function () {
                $('#modalRoles').modal('show'); 
            });
        }
    }
    });
    $('#tablaUsuarios').on('change', '.check-usuario', function () {
        let seleccionados = $('.check-usuario:checked').length;
        if (seleccionados > 0) {
            $('#btnEliminarSeleccionados').removeClass('d-none');
        } else {
            $('#btnEliminarSeleccionados').addClass('d-none');
        }
    });

    // Buscar
    $('#btnBuscar').on('click', function () {
        tablaUsuarios.ajax.reload();
    });

    // Eliminar individual
    $('#tablaUsuarios').on('click', '.btn-eliminar', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: "¿Seguro?",
            text: "El usuario será desactivado",
            icon: "warning",
            showCancelButton: true,
            allowOutsideClick: false, 
            allowEscapeKey: false,
            confirmButtonText: "Sí, desactivar",
            cancelButtonText: "Cancelar"
        }).then(result => {
            if (result.isConfirmed) {

                $.post('usuarios', {
                    opcion: 'Eliminar',
                    id: id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                })
                .done(function (res) {

                    if (res.respuesta === 'ok') {
                        Swal.fire('Éxito', res.mensaje, 'success').then(() => {
                            tablaUsuarios.ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', res.mensaje, 'error');
                    }
                })
                .fail(function () {
                    Swal.close();
                    Swal.fire('Error', 'Hubo un problema en la conexión.', 'error');
                });
            }
        });
    });


    // Eliminar múltiples
    function eliminarSeleccionados() {
        let ids = [];
        $('.check-usuario:checked').each(function () {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            Swal.fire('Atención', 'No seleccionaste usuarios', 'info');
            return;
        }

        Swal.fire({
            title: "¿Seguro?",
            text: "Los usuarios seleccionados serán desactivados",
            icon: "warning",
            showCancelButton: true,
            allowOutsideClick: false, 
            allowEscapeKey: false,
            confirmButtonText: "Sí, desactivar",
            cancelButtonText: "Cancelar"
        }).then(result => {
            if (result.isConfirmed) {
                $.post('usuarios', {
                    opcion: 'EliminarMultiple',
                    ids: ids,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, function (res) {
                    if (res.respuesta === 'ok') {
                        Swal.fire('Éxito', res.mensaje, 'success').then(() => {
                            tablaUsuarios.ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', res.mensaje, 'error');
                    }
                });
            }
        });
    }
    
    $("#btnNuevo").on("click", function(){
        $("#formNuevoUsuario")[0].reset();
        $("#formNuevoUsuario").find(".is-invalid").removeClass("is-invalid");
        $("id_user").val("0");
        $('#img_preview').remove();
        $("#modalNuevoUsuario").modal("show");
    });

    $("#formNuevoUsuario").on("submit", function(e){
        e.preventDefault();

        let form = $(this);
        let isValid = true;
        let idUser = $("#id_user").val(); 

        form.find(".invalid-feedback").remove();
        form.find(".is-invalid").removeClass("is-invalid");

        form.find("input, select").not("#imagen").each(function(){
            let field = $(this);

            if(field.attr("id") === "password" && idUser != "0") return;

            if(field.val().trim() === ""){
                isValid = false;
                field.addClass("is-invalid");
                field.after(`<div class="invalid-feedback">Este campo es obligatorio, por favor ingrese un valor.</div>`);
            }
        });

        if(!isValid){
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Debe llenar todos los campos antes de registrar el usuario.'
            });
            return false;
        }

        let password = $("#password").val().trim();
        if (password){ 
            let regexPassword = /^(?=.*[A-Z])(?=.*[a-zA-Z])(?=.*\d).{6,}$/;
            if(!regexPassword.test(password)){
                $("#password").addClass("is-invalid");
                $("#password").after(`<div class="invalid-feedback">La contraseña debe tener mínimo 6 caracteres, al menos una mayúscula y contener letras y números.</div>`);
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña inválida',
                    html: 'La contraseña debe tener:<br>- Mínimo 6 caracteres<br>- Al menos una letra mayúscula<br>- Letras y números'
                });
                return false;
            }
        }

        // Preparar FormData
        let formData = new FormData(this);
        formData.append("opcion", "Registrar");
        formData.append("_token", $("input[name=_token]").val());

        // Enviar AJAX
        $.ajax({
            url: "usuarios",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(resp){
                if(resp.respuesta === 'ok'){
                    Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                        $("#tablaUsuarios").DataTable().ajax.reload();
                        $("#modalNuevoUsuario").modal("hide");
                    });
                } else {
                    hidePreloader(mensajesGlobalLoader);
                    Swal.fire("Error", resp.mensaje, "error");
                }
            },
            error: function(err){
                Swal.fire("Error", "No se pudo registrar el usuario", "error");
            }
        });
    });
});

$('#tablaUsuarios').on('click', '.btn-editar', function () {
    $("#formNuevoUsuario").find(".is-invalid").removeClass("is-invalid");
    $('#img_preview').remove(); 
    $("#formNuevoUsuario")[0].reset();
    let idUser = $(this).data('id');
    mensajesGlobalLoader = showPreloader("Cargando información...", "cargar");
    
    $.post('usuarios', {
        opcion: 'Obtener',
        id_user: idUser,
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function(res) {
        if (res.respuesta === 'ok') {
            let user = res.usuario;
            let datos = res.usuario_datos;

            $('#id_user').val(user.id);
            $('#nombres').val(user.nombres);
            $('#apellidos').val(user.apellidos);
            $('#email').val(user.email);
            $('#id_rol').val(user.id_rol);
            $('#estado').val(user.estado ? 1 : 0);
            $('#password').val('');
            $('#tipoDoc').val(datos.tipoDoc);
            $('#numeroDoc').val(datos.numeroDoc);
            $('#celular').val(datos.celular);
            $('#fecha_nacimiento').val(datos.fecha_nacimiento);
            $('#nacionalidad').val(datos.nacionalidad);
            $('#calle').val(datos.calle || '');
            $('#numero').val(datos.numero || '');
            $('#dir_otros').val(datos.dir_otros || '');
            $('#cod_postal').val(datos.cod_postal || '');

            // Guardar los valores a seleccionar en atributos del DOM
            $('#departamento_id').attr('data-selected', datos.departamento);
            $('#provincia_id').attr('data-selected', datos.provincia);
            $('#distrito_id').attr('data-selected', datos.distrito);

            // Establecer departamento
            $('#departamento_id').val(datos.departamento).trigger('change');

            if(datos.imagen){
                $('#imagen').after(`<img src="/perfil_usuario/${datos.imagen}" class="img-thumbnail mt-2" width="100" id="img_preview">`);
            } else {
                $('#img_preview').remove(); 
            }

            $('#modalNuevoUsuarioLabel').text('Editar Usuario');
            $('#modalNuevoUsuario').modal('show');
            hidePreloader(mensajesGlobalLoader);
        } else {
            hidePreloader(mensajesGlobalLoader);
            Swal.fire('Error', res.mensaje, 'error');
        }
    }, 'json');
});

let tablaRoles;

$('#modalRoles').on('shown.bs.modal', function () {
    if (!$.fn.DataTable.isDataTable('#tablaRoles')) {
        tablaRoles = $('#tablaRoles').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'usuarios', 
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    opcion: 'ListarRol'
                },
                beforeSend: function () {
                    mensajesGlobalLoader = showPreloader("Cargando roles...", "cargar");
                },      
                dataSrc: function(json){
                    if (json.respuesta === 'ok') {
                        permisosVista = json.permisosVista || {};
                        return json.roles || [];
                    } 
                    Swal.fire('Error', json.mensaje, 'error');
                    return [];
                },
                complete: function () {
                    hidePreloader(mensajesGlobalLoader);
                }
            },
            columns: [
                { data: 'id', className: 'text-center' },
                { data: 'name', className: 'text-center' },
                { 
                    data: null, 
                    className: 'text-center',
                    render: data => {
                        let botones = "";

                        if (permisosVista.editar) {
                            botones += `
                                <button class="btn btn-sm btn-warning btn-editar-rol" 
                                        data-id="${data.id}" 
                                        title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            `;
                        }

                        if (permisosVista.eliminar) {
                            botones += `
                                <button class="btn btn-sm btn-danger btn-eliminar-rol" 
                                        data-id="${data.id}" 
                                        title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }

                        return botones;
                    }
                }
            ]
        });
    }
});

// Crear rol
$('#btnNuevoRol').on('click', function(){
    $('#modalRoles').modal('hide');

    Swal.fire({
        title: 'Crear nuevo rol',
        input: 'text',
        inputLabel: 'Nombre del rol',
        showCancelButton: true,
        confirmButtonText: 'Crear',
        preConfirm: (nombre) => {
            if(!nombre || nombre.trim() === '') {
                Swal.showValidationMessage('Debe ingresar un nombre');
            }
            return nombre;
        },
        didOpen: () => {
            const input = Swal.getInput();
            if(input) input.focus();
        }
    }).then((result) => {
        if(result.isConfirmed){
            mensajesGlobalLoader = showPreloader("Guardando rol...", "registrar");
            $.post('usuarios', {
                _token: $('meta[name="csrf-token"]').attr('content'), 
                opcion: 'CrearRol', 
                name: result.value.trim()
            }, function(resp){
                hidePreloader(mensajesGlobalLoader);
                if(resp.respuesta === 'ok'){
                    Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                        tablaRoles.ajax.reload();
                    });
                } else {
                    Swal.fire('Error', resp.mensaje, 'error');
                }
            }, 'json');
        }
    }).finally(() => {
        $('#modalRoles').modal('show');
    });
});

// Editar rol
$('#tablaRoles').on('click', '.btn-editar-rol', function(){
    let id = $(this).data('id');

    $('#modalRoles').modal('hide');
    $.post('usuarios', {
        _token: $('meta[name="csrf-token"]').attr('content'), 
        opcion: 'ObtenerRol', 
        id: id
    }, function(resp){
        if(resp.respuesta === 'ok'){
            Swal.fire({
                title: 'Editar rol',
                input: 'text',
                inputLabel: 'Nombre del rol',
                inputValue: resp.rol.name,
                showCancelButton: true,
                allowOutsideClick: false, 
                allowEscapeKey: false,
                confirmButtonText: 'Actualizar',
                preConfirm: (nombre) => {
                    if(!nombre || nombre.trim() === '') Swal.showValidationMessage('Debe ingresar un nombre');
                    return nombre;
                },
                didOpen: () => {
                    const input = Swal.getInput();
                    if(input) input.focus();
                }
            }).then((result) => {
                if(result.isConfirmed){
                    $.post('usuarios', {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        opcion: 'ActualizarRol',
                        id: id,
                        name: result.value.trim()
                    }, function(resp){
                        if(resp.respuesta === 'ok'){
                            Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                                tablaRoles.ajax.reload();
                            });
                        } else {
                            Swal.fire('Error', resp.mensaje, 'error');
                        }
                    }, 'json');
                }
            }).finally(() => {
                $('#modalRoles').modal('show');
            });
        } else {
            Swal.fire('Error', resp.mensaje, 'error').finally(() => {
                $('#modalRoles').modal('show');
            });
        }
    }, 'json');
});

// Eliminar rol
$('#tablaRoles').on('click', '.btn-eliminar-rol', function(){
    let id = $(this).data('id');
    $('#modalRoles').modal('hide');

    Swal.fire({
        title: '¿Seguro de eliminar este rol?',
        icon: 'warning',
        showCancelButton: true,
        allowOutsideClick: false, 
        allowEscapeKey: false,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result)=>{
        if(result.isConfirmed){
            $.post('usuarios', {
                _token: $('meta[name="csrf-token"]').attr('content'), 
                opcion: 'EliminarRol', 
                id: id
            }, function(resp){
                if(resp.respuesta === 'ok'){
                    Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                        tablaRoles.ajax.reload();
                    });
                    
                } else {
                    Swal.fire('Error', resp.mensaje, 'error');
                }
            }, 'json');
        }
    }).finally(() => {
        $('#modalRoles').modal('show');
    });
});

$(document).on('click', '.btn-detalle', function () {
    let id_user = $(this).data('id');
    mensajesGlobalLoader = showPreloader("Cargando usuarios...", "cargar");
    $.post('usuarios', { 
        opcion: 'obtenerInfo', 
        id_user: id_user, 
        _token: $('meta[name="csrf-token"]').attr('content') 
    }, function (res) {
        if (res.respuesta === 'ok') {
            console.log(res)
            let usuario = res.usuario;
            let datos = res.usuario.datos;

            $('#detalleImagen').attr('src', usuario.datos.imagen_url);
            $('#detalleNombre').text(`${usuario.nombres} ${usuario.apellidos}`);
            $('#detalleRol').text(usuario.rol?.name ?? 'Sin rol');
            $('#detalleEmail').text(usuario.email);
            $('#detalleDocumento').text(`${datos?.tipoDoc ?? ''} ${datos?.numeroDoc ?? ''}`);
            let direccionCompleta = [
                datos?.calle + " " + datos?.numero,
                datos?.dir_otros,
                datos?.cod_postal,
                datos?.provincia?.nombre,
                datos?.departamento?.nombre
            ].filter(Boolean).join(', ');

            $('#detalleDireccion').text(direccionCompleta || '-');
            $('#detalleCelular').text(datos?.celular ?? '-');
            $('#detalleFechaNac').text(datos?.fecha_nacimiento ?? '-');
            $('#detalleNacionalidad').text(datos?.nacionalidad ?? '-');

            // Auditorías
            if ($.fn.DataTable.isDataTable('#tablaAuditorias')) {
                $('#tablaAuditorias').DataTable().clear().destroy();
            }

            let dataAuditorias = usuario.auditorias?.map(a => [
                a.accion,
                a.tabla_afectada,
                a.descripcion,
                a.created_at
            ]) ?? [];

            $('#tablaAuditorias').DataTable({
                data: dataAuditorias,
                columns: [
                    { title: "Acción" },
                    { title: "Tabla" },
                    { title: "Descripción" },
                    { title: "Fecha" }
                ],
                pageLength: 10,
                language: {
                    "processing": "Procesando...",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "zeroRecords": "No se encontraron resultados",
                    "emptyTable": "No se encontraron registros",
                    "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "infoFiltered": "(filtrado de un total de _MAX_ registros)",
                    "search": "Buscar:",
                    "infoThousands": ",",
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

            $('#modalDetalleUsuario').modal('show');
            hidePreloader(mensajesGlobalLoader);
        } else {
            hidePreloader(mensajesGlobalLoader);
            Swal.fire('Error', res.mensaje, 'error');
        }
    });
});
