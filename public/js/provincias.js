$(document).ready(function () {
    let tabla = $('#tablaProvincias').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'provincias',
            type: 'POST',
            data: { opcion: 'Listar', _token: $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () {
                mensajesGlobalLoader = showPreloader("Cargando Provincias...", "cargar");
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
            { data: 'departamento.nombre', className: 'text-center' },
            { data: 'created_at', className: 'text-center' },
            { data: 'updated_at', className: 'text-center' },
            {
                data: null,
                className: 'text-center',
                render: function (data, type, row) {
                    let botones = ``;

                    if (permisosVista.editar) {
                        botones += `
                            <button class="btn btn-warning btn-sm btnEditar" 
                                    data-id="${row.id}" 
                                    data-nombre="${row.nombre}" 
                                    data-departamento="${row.departamento_id}">
                                <i class="bi bi-pencil"></i>
                            </button>
                        `;
                    }

                    if (permisosVista.eliminar) {
                        botones += `
                            <button class="btn btn-danger btn-sm btnEliminar" 
                                    data-id="${row.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                    }

                    return botones 
                        ? `<div class="d-flex justify-content-center gap-2">${botones}</div>` 
                        : `<span class="text-muted">Sin permisos</span>`;
                }
            }
        ]
    });

    // Nuevo
    $('#btnNuevo').click(function () {
        $('#formProvincia')[0].reset();
        $('#idProvincia').val('');
        $('#tituloModal').text('Nueva Provincia');
        $('#modalProvincia').modal('show');
    });

    // Guardar
    $('#btnGuardar').click(function () {
        let opcion = $('#idProvincia').val() ? 'Editar' : 'Crear';
        let datos = $('#formProvincia').serialize() + `&opcion=${opcion}&_token=${$('meta[name="csrf-token"]').attr('content')}`;

        $.post('provincias', datos, function (res) {
            if (res.respuesta === 'success') {
                $('#modalProvincia').modal('hide');
                tabla.ajax.reload(null, false);
                Swal.fire('Éxito', res.mensaje, 'success');
            } else {
                Swal.fire('Error', res.mensaje, 'error');
            }
        }, 'json');
    });

    // Editar
    $('#tablaProvincias').on('click', '.btnEditar', function () {
        $('#idProvincia').val($(this).data('id'));
        $('#nombre').val($(this).data('nombre'));
        $('#departamento_id').val($(this).data('departamento'));
        $('#tituloModal').text('Editar Provincia');
        $('#modalProvincia').modal('show');
    });

    // Eliminar
    $('#tablaProvincias').on('click', '.btnEliminar', function () {
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
                $.post('provincias', { opcion: 'Eliminar', id: id, _token: $('meta[name="csrf-token"]').attr('content') }, function (res) {
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
