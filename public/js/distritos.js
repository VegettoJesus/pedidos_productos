$(document).ready(function () {
    let tabla = $('#tablaDistritos').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'distritos',
            type: 'POST',
            data: { opcion: 'Listar', _token: $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () {
                mensajesGlobalLoader = showPreloader("Cargando Distritos...", "cargar");
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
            { data: 'costo_envio', className: 'text-center' },
            { data: 'provincia.nombre', className: 'text-center' },
            { data: 'provincia.departamento.nombre', className: 'text-center' },
            { data: 'created_at', className: 'text-center' },
            { data: 'updated_at', className: 'text-center' },
            {
                data: null,
                className: 'text-center',
                render: function (data, type, row) {
                    let botones = ``;
                    if (permisosVista.editar) {
                        botones += `<button class="btn btn-warning btn-sm btnEditar" 
                                        data-id="${row.id}" 
                                        data-nombre="${row.nombre}" 
                                        data-costo="${row.costo_envio}" 
                                        data-provincia="${row.provincia_id}">
                                        <i class="bi bi-pencil"></i>
                                    </button>`;
                    }
                    if (permisosVista.eliminar) {
                        botones += `<button class="btn btn-danger btn-sm btnEliminar" data-id="${row.id}">
                                        <i class="bi bi-trash"></i>
                                    </button>`;
                    }
                    return botones ? `<div class="d-flex justify-content-center gap-2">${botones}</div>` : `<span class="text-muted">Sin permisos</span>`;
                }
            }
        ]
    });

    // Nuevo
    $('#btnNuevo').click(function () {
        $('#formDistrito')[0].reset();
        $('#idDistrito').val('');
        $('#tituloModal').text('Nuevo Distrito');
        $('#modalDistrito').modal('show');
    });

    // Guardar
    $('#btnGuardar').click(function () {
        let opcion = $('#idDistrito').val() ? 'Editar' : 'Crear';
        let datos = $('#formDistrito').serialize() + `&opcion=${opcion}&_token=${$('meta[name="csrf-token"]').attr('content')}`;

        $.post('distritos', datos, function (res) {
            if (res.respuesta === 'success') {
                $('#modalDistrito').modal('hide');
                tabla.ajax.reload(null, false);
                Swal.fire('Éxito', res.mensaje, 'success');
            } else {
                Swal.fire('Error', res.mensaje, 'error');
            }
        }, 'json').fail(() => {
            Swal.fire('Error', 'Hubo un problema en el servidor.', 'error');
        });
    });

    // Editar
    $('#tablaDistritos').on('click', '.btnEditar', function () {
        $('#idDistrito').val($(this).data('id'));
        $('#nombre').val($(this).data('nombre'));
        $('#costo_envio').val($(this).data('costo'));
        $('#provincia_id').val($(this).data('provincia'));
        $('#tituloModal').text('Editar Distrito');
        $('#modalDistrito').modal('show');
    });

    // Eliminar
    $('#tablaDistritos').on('click', '.btnEliminar', function () {
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
                $.post('distritos', { opcion: 'Eliminar', id: id, _token: $('meta[name="csrf-token"]').attr('content') }, function (res) {
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