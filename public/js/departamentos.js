$(document).ready(function () {
    let tabla = $('#tablaDepartamentos').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'departamentos',
            type: 'POST',
            data: { opcion: 'Listar', _token: $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () {
                mensajesGlobalLoader = showPreloader("Cargando Departamentos...", "cargar");
            },
            dataSrc: function (json) {
                permisosVista = json.permisosVista; 
                return json.data;
            },
            complete: function () {
                hidePreloader(mensajesGlobalLoader);
            }
        },
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
        },
        columns: [
            { data: null, defaultContent: '', orderable: false },
            { data: 'id', className: 'text-center' },
            { data: 'nombre', className: 'text-center' },
            { data: 'created_at', className: 'text-center' },
            { data: 'updated_at', className: 'text-center' },
            {
                data: null,
                className: 'text-center',
                render: function (data, type, row) {
                    let botones = ``;
                    if (permisosVista.editar) {
                        botones += `
                            <button class="btn btn-warning btn-sm btnEditar" data-id="${row.id}" data-nombre="${row.nombre}">
                                <i class="bi bi-pencil"></i>
                            </button>
                        `;
                    }
                    if (permisosVista.eliminar) {
                        botones += `
                            <button class="btn btn-danger btn-sm btnEliminar" data-id="${row.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                    }
                    return botones || `<span class="text-muted">Sin permisos</span>`;
                }
            }
        ]
    });

    // Abrir modal nuevo
    $('#btnNuevo').click(function () {
        $('#formDepartamento')[0].reset();
        $('#idDepartamento').val('');
        $('#tituloModal').text('Nuevo Departamento');
        $('#modalDepartamento').modal('show');
    });

    // Guardar
    $('#btnGuardar').click(function () {
        let opcion = $('#idDepartamento').val() ? 'Editar' : 'Crear';
        let datos = $('#formDepartamento').serialize() + `&opcion=${opcion}&_token=${$('meta[name="csrf-token"]').attr('content')}`;

        $.post('departamentos', datos, function (res) {

            if (res.respuesta === 'success') {
                $('#modalDepartamento').modal('hide');
                tabla.ajax.reload(null, false);
                Swal.fire('Éxito', res.mensaje, 'success');
            } else {
                Swal.fire('Error', res.mensaje, 'error');
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Hubo un problema en el servidor.', 'error');
        });
    });

    // Editar
    $('#tablaDepartamentos').on('click', '.btnEditar', function () {
        $('#idDepartamento').val($(this).data('id'));
        $('#nombre').val($(this).data('nombre'));
        $('#tituloModal').text('Editar Departamento');
        $('#modalDepartamento').modal('show');
    });

    // Eliminar
    $('#tablaDepartamentos').on('click', '.btnEliminar', function () {
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('departamentos', { opcion: 'Eliminar', id: id, _token: $('meta[name="csrf-token"]').attr('content') }, function (res) {
                    if (res.respuesta === 'success') {
                        tabla.ajax.reload(null, false);
                        Swal.fire('Eliminado', res.mensaje, 'success');
                    } else {
                        Swal.fire('Error', res.mensaje, 'error');
                    }
                }, 'json');
            }
        });
    });
});
