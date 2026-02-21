$(document).ready(function() {
    let tablaDepartamentos, tablaProvincias, tablaDistritos;
    let tipoActual = 'departamento';
    let soloActivos = true; 

    function inicializarTablas() {
        tablaDepartamentos = $('#tablaDepartamentos').DataTable({
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
                { data: 'id', className: 'text-center' },
                { data: 'nombre', className: 'text-center' },
                { 
                    data: 'activo',
                    className: 'text-center',
                    render: function(data) {
                        return data 
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>';
                    }
                },
                { 
                    data: 'created_at',
                    className: 'text-center',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('es-PE') : '';
                    }
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
                                <button class="btn btn-sm btn-warning btn-editar-departamento" title="Editar" data-id="${row.id}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            `;
                        }
                        
                        if (permisos.configurar) {
                            botones += `
                                <button class="btn btn-sm btn-${row.activo ? 'danger' : 'success'} btn-cambiar-estado" 
                                        title="${row.activo ? 'Desactivar' : 'Activar'}" 
                                        data-id="${row.id}" 
                                        data-activo="${row.activo}">
                                    <i class="bi bi-${row.activo ? 'x-circle' : 'check-circle'}"></i>
                                </button>
                            `;
                        }
                        
                        if (permisos.eliminar) {
                            let provinciasCount = row.provincias_count || 0;
                            botones += `
                                <button class="btn btn-sm btn-danger btn-eliminar-departamento" title="Eliminar" data-id="${row.id}" data-nombre="${row.nombre}" data-tipo="departamento" data-asociados="${provinciasCount}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                        
                        return botones || '<span class="text-muted">Sin permisos</span>';
                    }
                }
            ]
        });

        tablaProvincias = $('#tablaProvincias').DataTable({
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
                { data: 'id', className: 'text-center' },
                { data: 'nombre', className: 'text-center' },
                { 
                    data: 'departamento',
                    className: 'text-center',
                    render: function(data) {
                        return data ? data.nombre : '';
                    }
                },
                { 
                    data: 'activo',
                    className: 'text-center',
                    render: function(data) {
                        return data 
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>';
                    }
                },
                { 
                    data: 'created_at',
                    className: 'text-center',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('es-PE') : '';
                    }
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
                                <button class="btn btn-sm btn-warning btn-editar-provincia" title="Editar" data-id="${row.id}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            `;
                        }
                        
                        if (permisos.configurar) {
                            botones += `
                                <button class="btn btn-sm btn-${row.activo ? 'danger' : 'success'} btn-cambiar-estado" 
                                        title="${row.activo ? 'Desactivar' : 'Activar'}" 
                                        data-id="${row.id}" 
                                        data-activo="${row.activo}">
                                    <i class="bi bi-${row.activo ? 'x-circle' : 'check-circle'}"></i>
                                </button>
                            `;
                        }
                        
                        if (permisos.eliminar) {
                            let distritosCount = row.distritos_count || 0;
                            botones += `
                                <button class="btn btn-sm btn-danger btn-eliminar-provincia" title="Eliminar" data-id="${row.id}" data-nombre="${row.nombre}" data-tipo="provincia" data-asociados="${distritosCount}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                        
                        return botones || '<span class="text-muted">Sin permisos</span>';
                    }
                }
            ]
        });

        tablaDistritos = $('#tablaDistritos').DataTable({
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
                { data: 'id', className: 'text-center' },
                { data: 'nombre', className: 'text-center' },
                { 
                    data: 'provincia',
                    className: 'text-center',
                    render: function(data) {
                        return data && data.departamento ? data.departamento.nombre : '';
                    }
                },
                { 
                    data: 'provincia',
                    className: 'text-center',
                    render: function(data) {
                        return data ? data.nombre : '';
                    }
                },
                { 
                    data: 'costo_envio',
                    className: 'text-center',
                    render: function(data) {
                        return data ? 'S/ ' + parseFloat(data).toFixed(2) : 'S/ 0.00';
                    }
                },
                { 
                    data: 'activo',
                    className: 'text-center',
                    render: function(data) {
                        return data 
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>';
                    }
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
                                <button class="btn btn-sm btn-warning btn-editar-distrito" title="Editar" data-id="${row.id}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            `;
                        }
                        
                        if (permisos.configurar) {
                            botones += `
                                <button class="btn btn-sm btn-${row.activo ? 'danger' : 'success'} btn-cambiar-estado" 
                                        title="${row.activo ? 'Desactivar' : 'Activar'}" 
                                        data-id="${row.id}" 
                                        data-activo="${row.activo}">
                                    <i class="bi bi-${row.activo ? 'x-circle' : 'check-circle'}"></i>
                                </button>
                            `;
                        }
                        
                        if (permisos.eliminar) {
                            botones += `
                                <button class="btn btn-sm btn-danger btn-eliminar-distrito" title="Eliminar" data-id="${row.id}" data-nombre="${row.nombre}" data-tipo="distrito">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                        
                        return botones || '<span class="text-muted">Sin permisos</span>';
                    }
                }
            ]
        });

        cargarDepartamentos();
        cargarFiltros();
    }

    function cargarFiltros() {
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'ListarFiltros',
                filtro: 'departamentos'
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    let options = '<option value="">Todos los Departamentos</option>';
                    response.departamentos.forEach(function(depto) {
                        options += `<option value="${depto.id}">${depto.nombre}</option>`;
                    });
                    
                    $('#filtroDepartamentoProvincia').html(options);
                    $('#departamentoProvincia').html(options.replace('Todos los Departamentos', 'Seleccionar Departamento'));
                    $('#departamentoDistrito').html(options.replace('Todos los Departamentos', 'Seleccionar Departamento'));
                    $('#filtroDepartamentoDistrito').html(options.replace('Todos los Departamentos', 'Seleccionar Departamento'));
                    
                    cargarProvincias('');
                }
            }
        });
    }

    function cargarDepartamentos() {
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'Listar',
                tipo: 'departamento',
                solo_activos: soloActivos ? '1' : '0'
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    window.permisosVista = response.permisosVista;
                    tablaDepartamentos.clear().rows.add(response.data).draw();
                }
            }
        });
    }

    function cargarProvincias(departamentoId = '') {
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'Listar',
                tipo: 'provincia',
                departamento_id: departamentoId,
                solo_activos: soloActivos ? '1' : '0'
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    window.permisosVista = response.permisosVista;
                    tablaProvincias.clear().rows.add(response.data).draw();
                }
            }
        });
    }

    function cargarDistritos(departamentoId = '', provinciaId = '') {
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'Listar',
                tipo: 'distrito',
                departamento_id: departamentoId,
                provincia_id: provinciaId,
                solo_activos: soloActivos ? '1' : '0'
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    window.permisosVista = response.permisosVista;
                    tablaDistritos.clear().rows.add(response.data).draw();
                }
            }
        });
    }

    $('#filtroDepartamentoProvincia').on('change', function() {
        cargarProvincias($(this).val());
    });

    $('#filtroDepartamentoDistrito').on('change', function() {
        let deptoId = $(this).val();
        $('#filtroProvinciaDistrito').prop('disabled', !deptoId);
        
        if (deptoId) {
            cargarProvinciasParaFiltro(deptoId);
        } else {
            $('#filtroProvinciaDistrito').html('<option value="">Seleccionar Provincia</option>');
            cargarDistritos();
        }
    });

    $('#filtroProvinciaDistrito').on('change', function() {
        let deptoId = $('#filtroDepartamentoDistrito').val();
        let provinciaId = $(this).val();
        cargarDistritos(deptoId, provinciaId);
    });

    function cargarProvinciasParaFiltro(departamentoId) {
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'ListarFiltros',
                filtro: 'provincias_por_departamento',
                departamento_id: departamentoId
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    let options = '<option value="">Todas las Provincias</option>';
                    response.provincias.forEach(function(prov) {
                        options += `<option value="${prov.id}">${prov.nombre}</option>`;
                    });
                    $('#filtroProvinciaDistrito').html(options);
                    cargarDistritos(departamentoId);
                }
            }
        });
    }

    $('#btnNuevoDepartamento').on('click', function() {
        tipoActual = 'departamento';
        $('#tituloModal').text('Nuevo Departamento');
        $('#tipoEntidad').val('departamento');
        $('#idRegistro').val('');
        
        $('#camposDepartamento').show();
        $('#camposProvincia').hide();
        $('#camposDistrito').hide();
        
        $('#formUbicacion')[0].reset();
        $('#activo').prop('checked', true);
        
        $('#modalUbicacion').modal('show');
    });

    $('#btnNuevaProvincia').on('click', function() {
        tipoActual = 'provincia';
        $('#tituloModal').text('Nueva Provincia');
        $('#tipoEntidad').val('provincia');
        $('#idRegistro').val('');
        
        $('#camposDepartamento').hide();
        $('#camposProvincia').show();
        $('#camposDistrito').hide();
        
        $('#formUbicacion')[0].reset();
        $('#activo').prop('checked', true);
        
        $('#modalUbicacion').modal('show');
    });

    $('#btnNuevoDistrito').on('click', function() {
        tipoActual = 'distrito';
        $('#tituloModal').text('Nuevo Distrito');
        $('#tipoEntidad').val('distrito');
        $('#idRegistro').val('');
        
        $('#camposDepartamento').hide();
        $('#camposProvincia').hide();
        $('#camposDistrito').show();
        
        $('#formUbicacion')[0].reset();
        $('#activo').prop('checked', true);
        $('#provinciaDistrito').prop('disabled', true);
        
        $('#modalUbicacion').modal('show');
    });

    $('#departamentoDistrito').on('change', function() {
        let deptoId = $(this).val();
        $('#provinciaDistrito').prop('disabled', !deptoId);
        
        if (deptoId) {
            $.ajax({
                url: window.location.pathname,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    opcion: 'ListarFiltros',
                    filtro: 'provincias_por_departamento',
                    departamento_id: deptoId
                },
                success: function(response) {
                    if (response.respuesta === 'ok') {
                        let options = '<option value="">Seleccionar Provincia</option>';
                        response.provincias.forEach(function(prov) {
                            options += `<option value="${prov.id}">${prov.nombre}</option>`;
                        });
                        $('#provinciaDistrito').html(options);
                    }
                }
            });
        } else {
            $('#provinciaDistrito').html('<option value="">Seleccionar Provincia</option>');
        }
    });

    $('#btnGuardarUbicacion').on('click', function() {
        let formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('opcion', $('#idRegistro').val() ? 'Editar' : 'Crear');
        formData.append('tipo', tipoActual);
        formData.append('id', $('#idRegistro').val());
        formData.append('activo', $('#activo').is(':checked') ? 1 : 0);
        
        switch (tipoActual) {
            case 'departamento':
                formData.append('nombre', $('#nombreDepartamento').val());
                break;
                
            case 'provincia':
                formData.append('nombre', $('#nombreProvincia').val());
                formData.append('departamento_id', $('#departamentoProvincia').val());
                break;
                
            case 'distrito':
                formData.append('nombre', $('#nombreDistrito').val());
                formData.append('costo_envio', $('#costoEnvio').val());
                formData.append('provincia_id', $('#provinciaDistrito').val());
                break;
        }

        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.respuesta === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.mensaje,
                        timer: 2000
                    });
                    
                    $('#modalUbicacion').modal('hide');
                    
                    switch (tipoActual) {
                        case 'departamento':
                            cargarDepartamentos();
                            cargarFiltros(); 
                            break;
                        case 'provincia':
                            cargarProvincias($('#filtroDepartamentoProvincia').val());
                            break;
                        case 'distrito':
                            cargarDistritos(
                                $('#filtroDepartamentoDistrito').val(),
                                $('#filtroProvinciaDistrito').val()
                            );
                            break;
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.mensaje
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error inesperado'
                });
            }
        });
    });

    $(document).on('click', '.btn-editar-departamento', function() {
        let id = $(this).data('id');
        tipoActual = 'departamento';
        
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'Listar',
                tipo: 'departamento'
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    let departamento = response.data.find(d => d.id == id);
                    if (departamento) {
                        $('#tituloModal').text('Editar Departamento');
                        $('#tipoEntidad').val('departamento');
                        $('#idRegistro').val(departamento.id);
                        $('#nombreDepartamento').val(departamento.nombre);
                        $('#activo').prop('checked', departamento.activo);
                        
                        $('#camposDepartamento').show();
                        $('#camposProvincia').hide();
                        $('#camposDistrito').hide();
                        
                        $('#modalUbicacion').modal('show');
                    }
                }
            }
        });
    });
    
    $(document).on('click', '.btn-editar-provincia', function() {
        let id = $(this).data('id');
        tipoActual = 'provincia';
        
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'Listar',
                tipo: 'provincia'
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    let provincia = response.data.find(p => p.id == id);
                    if (provincia) {
                        $('#tituloModal').text('Editar Provincia');
                        $('#tipoEntidad').val('provincia');
                        $('#idRegistro').val(provincia.id);
                        $('#nombreProvincia').val(provincia.nombre);
                        $('#departamentoProvincia').val(provincia.departamento_id);
                        $('#activo').prop('checked', provincia.activo);
                        
                        $('#camposDepartamento').hide();
                        $('#camposProvincia').show();
                        $('#camposDistrito').hide();
                        
                        $('#modalUbicacion').modal('show');
                    }
                }
            }
        });
    });
    
    $(document).on('click', '.btn-editar-distrito', function() {
        let id = $(this).data('id');
        tipoActual = 'distrito';
        
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'Listar',
                tipo: 'distrito'
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    let distrito = response.data.find(d => d.id == id);
                    if (distrito) {
                        $('#tituloModal').text('Editar Distrito');
                        $('#tipoEntidad').val('distrito');
                        $('#idRegistro').val(distrito.id);
                        $('#nombreDistrito').val(distrito.nombre);
                        $('#costoEnvio').val(distrito.costo_envio);
                        
                        cargarDepartamentoYProvinciaParaEditar(distrito);
                        
                        $('#activo').prop('checked', distrito.activo);
                        
                        $('#camposDepartamento').hide();
                        $('#camposProvincia').hide();
                        $('#camposDistrito').show();
                        
                        $('#modalUbicacion').modal('show');
                    }
                }
            }
        });
    });

    function cargarDepartamentoYProvinciaParaEditar(distrito) {
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'ListarFiltros',
                filtro: 'departamentos'
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    let options = '<option value="">Seleccionar Departamento</option>';
                    response.departamentos.forEach(function(depto) {
                        let selected = (distrito.provincia && distrito.provincia.departamento_id == depto.id) ? 'selected' : '';
                        options += `<option value="${depto.id}" ${selected}>${depto.nombre}</option>`;
                    });
                    $('#departamentoDistrito').html(options);
                    
                    if (distrito.provincia && distrito.provincia.departamento_id) {
                        $('#departamentoDistrito').val(distrito.provincia.departamento_id);
                        cargarProvinciasParaEditar(distrito);
                    }
                }
            }
        });
    }

    function cargarProvinciasParaEditar(distrito) {
        let deptoId = $('#departamentoDistrito').val();
        
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                opcion: 'ListarFiltros',
                filtro: 'provincias_por_departamento',
                departamento_id: deptoId
            },
            success: function(response) {
                if (response.respuesta === 'ok') {
                    let options = '<option value="">Seleccionar Provincia</option>';
                    response.provincias.forEach(function(prov) {
                        let selected = (distrito.provincia_id == prov.id) ? 'selected' : '';
                        options += `<option value="${prov.id}" ${selected}>${prov.nombre}</option>`;
                    });
                    $('#provinciaDistrito').html(options);
                    $('#provinciaDistrito').prop('disabled', false);
                    $('#provinciaDistrito').val(distrito.provincia_id);
                }
            }
        });
    }

    $(document).on('click', '.btn-eliminar-departamento, .btn-eliminar-provincia, .btn-eliminar-distrito', function() {
        let id = $(this).data('id');
        let nombre = $(this).data('nombre');
        let tipo = $(this).data('tipo');
        let asociados = $(this).data('asociados') || 0;
        
        let titulo = `¿Eliminar ${tipo}?`;
        let texto = '';
        
        if (tipo === 'departamento') {
            if (asociados > 0) {
                texto = `El departamento <strong>${nombre}</strong> tiene <strong>${asociados} provincia(s)</strong> asociada(s).<br><br>
                        Al eliminar el departamento, también se eliminarán TODAS sus provincias y distritos.<br><br>
                        ¿Estás seguro de continuar?`;
            } else {
                texto = `¿Estás seguro de eliminar el departamento <strong>${nombre}</strong>?`;
            }
        } else if (tipo === 'provincia') {
            if (asociados > 0) {
                texto = `La provincia <strong>${nombre}</strong> tiene <strong>${asociados} distrito(s)</strong> asociado(s).<br><br>
                        Al eliminar la provincia, también se eliminarán TODOS sus distritos.<br><br>
                        ¿Estás seguro de continuar?`;
            } else {
                texto = `¿Estás seguro de eliminar la provincia <strong>${nombre}</strong>?`;
            }
        } else {
            texto = `¿Estás seguro de eliminar el distrito <strong>${nombre}</strong>?`;
        }
        
        Swal.fire({
            title: titulo,
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
                    url: window.location.pathname,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        opcion: 'Eliminar',
                        tipo: tipo,
                        id: id
                    },
                    success: function(response) {
                        if (response.respuesta === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: response.mensaje,
                                timer: 2000
                            });
                            
                            switch (tipo) {
                                case 'departamento':
                                    cargarDepartamentos();
                                    cargarFiltros();
                                    break;
                                case 'provincia':
                                    cargarProvincias($('#filtroDepartamentoProvincia').val());
                                    break;
                                case 'distrito':
                                    cargarDistritos(
                                        $('#filtroDepartamentoDistrito').val(),
                                        $('#filtroProvinciaDistrito').val()
                                    );
                                    break;
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.mensaje
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error inesperado'
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-cambiar-estado', function() {
        let id = $(this).data('id');
        let tipo = $(this).closest('table').attr('id').replace('tabla', '').toLowerCase().slice(0, -1);
        let activo = $(this).data('activo');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas ${activo ? 'desactivar' : 'activar'} este registro?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.location.pathname,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        opcion: 'CambiarEstado',
                        tipo: tipo,
                        id: id
                    },
                    success: function(response) {
                        if (response.respuesta === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.mensaje
                            });
                            
                            switch (tipo) {
                                case 'departamento':
                                    cargarDepartamentos();
                                    break;
                                case 'provincia':
                                    cargarProvincias($('#filtroDepartamentoProvincia').val());
                                    break;
                                case 'distrito':
                                    cargarDistritos(
                                        $('#filtroDepartamentoDistrito').val(),
                                        $('#filtroProvinciaDistrito').val()
                                    );
                                    break;
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.mensaje
                            });
                        }
                    }
                });
            }
        });
    });

    inicializarTablas();
});