$(document).ready(function() {
    let empresaActual = null;
    let modalAyudaUbigeo = null;
    
    setTimeout(() => {
        const modalUbigeo = document.getElementById('modalAyudaUbigeo');
        if (modalUbigeo) modalAyudaUbigeo = new bootstrap.Modal(modalUbigeo);
    }, 100);

    cargarDatosEmpresa();
    inicializarEventos();
    actualizarVistaPrevia();

    function cargarDatosEmpresa() {
        $.ajax({
            url: 'datosEmpresa',
            method: 'POST',
            data: {
                opcion: 'Listar', 
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            beforeSend: function() {
                mostrarLoading('Cargando datos de la empresa...');
            },
            success: function(response) {
                Swal.close();
                if (response && response.success) {
                    empresaActual = response.data || {};
                    llenarFormulario(empresaActual);
                    actualizarVistaPrevia();
                } else {
                    mostrarError('Error', response?.message || 'No se pudieron cargar los datos');
                }
            },
            error: function(xhr) {
                mostrarError('Error de conexión', 'No se pudo conectar con el servidor');
            }
        });
    }

    function llenarFormulario(data) {
        if (!data) return;
        
        $('#ruc').val(data.ruc || '');
        $('#razon_social').val(data.razon_social || '');
        $('#nombre_comercial').val(data.nombre_comercial || '');
        $('#propietario_nombre').val(data.propietario_nombre || '');
        $('#propietario_apellido').val(data.propietario_apellido || '');
        $('#direccion').val(data.direccion || '');
        $('#ubigeo').val(data.ubigeo || '');
        $('#maps_url').val(data.maps_url || '');
        $('#telefono').val(data.telefono || '');
        $('#celular').val(data.celular || '');

        if (data.departamento_id) {
            $('#departamento_id').val(data.departamento_id);
            cargarProvincias(data.departamento_id, function() {
                if (data.provincia_id) {
                    $('#provincia_id').val(data.provincia_id);
                    cargarDistritos(data.provincia_id, function() {
                        if (data.distrito_id) {
                            $('#distrito_id').val(data.distrito_id);
                            setTimeout(() => {
                                actualizarVistaPreviaEnTiempoReal();
                            }, 200);
                        }
                    });
                }
            });
        }
    }

    function cargarProvincias(departamentoId, callback) {
        $('#provincia_id').prop('disabled', true).html('<option>Cargando...</option>');
        $('#distrito_id').prop('disabled', true).html('<option>Seleccione distrito</option>');

        if (!departamentoId) {
            if (callback) callback();
            return;
        }

        $.get(`/get-provincias/${departamentoId}`, function (data) {
            let options = '<option value="">Seleccione provincia</option>';
            data.forEach(p => {
                options += `<option value="${p.id}">${p.nombre}</option>`;
            });
            $('#provincia_id').html(options).prop('disabled', false);
            
            if (callback) callback();
        }).fail(function() {
            $('#provincia_id').html('<option value="">Error al cargar</option>');
            if (callback) callback();
        });
    }

    function cargarDistritos(provinciaId, callback) {
        $('#distrito_id').prop('disabled', true).html('<option>Cargando...</option>');

        if (!provinciaId) {
            if (callback) callback();
            return;
        }

        $.get(`/get-distritos/${provinciaId}`, function (data) {
            let options = '<option value="">Seleccione distrito</option>';
            data.forEach(d => {
                options += `<option value="${d.id}">${d.nombre}</option>`;
            });
            $('#distrito_id').html(options).prop('disabled', false);
            
            if (callback) callback();
        }).fail(function() {
            $('#distrito_id').html('<option value="">Error al cargar</option>');
            if (callback) callback();
        });
    }

    function guardarDatosEmpresa() {
        if (!validarFormulario()) {
            return;
        }

        const formData = {
            ruc: $('#ruc').val().trim(),
            razon_social: $('#razon_social').val().trim(),
            nombre_comercial: $('#nombre_comercial').val().trim() || null,
            propietario_nombre: $('#propietario_nombre').val().trim(),
            propietario_apellido: $('#propietario_apellido').val().trim(),
            direccion: $('#direccion').val().trim(),
            ubigeo: $('#ubigeo').val().trim(),
            departamento_id: $('#departamento_id').val(),
            provincia_id: $('#provincia_id').val(),
            distrito_id: $('#distrito_id').val(),
            maps_url: $('#maps_url').val().trim() || null,
            telefono: $('#telefono').val().trim() || null,
            celular: $('#celular').val().trim() || null,
            _token: $('meta[name="csrf-token"]').attr('content'),
            opcion: 'Guardar'
        };

        Swal.fire({
            title: '¿Guardar datos de la empresa?',
            html: `
                <div class="text-start">
                    <p><strong>RUC:</strong> ${formData.ruc}</p>
                    <p><strong>Razón Social:</strong> ${formData.razon_social}</p>
                    <p><strong>Dirección:</strong> ${formData.direccion}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                enviarDatos(formData);
            }
        });
    }

    function enviarDatos(formData) {
        $.ajax({
            url: 'datosEmpresa',
            method: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                mostrarLoading('Guardando datos...');
            },
            success: function(response) {
                Swal.close();
                if (response && response.success) {
                    empresaActual = response.data || {};
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        html: `
                            <p>Datos de la empresa actualizados correctamente.</p>
                            <div class="text-start small mt-3">
                                <p><strong>RUC:</strong> ${empresaActual.ruc}</p>
                                <p><strong>Razón Social:</strong> ${empresaActual.razon_social}</p>
                                <p><strong>Actualizado:</strong> ${new Date().toLocaleDateString('es-ES')}</p>
                            </div>
                        `,
                        confirmButtonText: 'Entendido'
                    }).then(() => {
                        actualizarVistaPrevia();
                    });
                } else {
                    mostrarError('Error', response?.message || 'No se pudieron guardar los datos');
                }
            },
            error: function(xhr) {
                let message = 'Error al guardar los datos';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join(', ');
                }
                mostrarError('Error', message);
            }
        });
    }

    function validarFormulario() {
        let isValid = true;

        const requiredFields = [
            'ruc', 'razon_social', 'propietario_nombre', 'propietario_apellido',
            'direccion', 'ubigeo', 'departamento_id', 'provincia_id', 'distrito_id'
        ];
        
        requiredFields.forEach(field => {
            const element = $(`#${field}`);
            if (!element.val() || !element.val().toString().trim()) {
                element.addClass('is-invalid');
                isValid = false;
            } else {
                element.removeClass('is-invalid');
            }
        });

        const ruc = $('#ruc').val().trim();
        if (ruc.length !== 11 || !/^\d+$/.test(ruc)) {
            $('#ruc').addClass('is-invalid');
            $('#errorRUC').text('El RUC debe tener exactamente 11 dígitos numéricos.');
            isValid = false;
        }

        const mapsUrl = $('#maps_url').val().trim();
        if (mapsUrl && !isValidUrl(mapsUrl)) {
            $('#maps_url').addClass('is-invalid');
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

    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    $('#departamento_id, #provincia_id, #distrito_id').on('change', function() {
        actualizarVistaPreviaEnTiempoReal();
    });

    function actualizarVistaPrevia() {
        if (!empresaActual) return;

        $('#previewRUC').text(empresaActual.ruc || '-');
        $('#previewRazonSocial').text(empresaActual.razon_social || '-');
        $('#previewNombreComercial').text(empresaActual.nombre_comercial || '-');
        
        const propietario = (empresaActual.propietario_nombre || '') + ' ' + (empresaActual.propietario_apellido || '');
        $('#previewPropietario').text(propietario.trim() || '-');
        $('#previewTelefono').text(empresaActual.telefono || '-');
        $('#previewCelular').text(empresaActual.celular || '-');
        $('#previewDireccion').text(empresaActual.direccion || '-');
        $('#previewUbigeo').text(empresaActual.ubigeo || '-');
        
        const distritoText = $('#distrito_id option:selected').text();
        const provinciaText = $('#provincia_id option:selected').text();
        const departamentoText = $('#departamento_id option:selected').text();
        
        let ubicacionCompleta = '';
        if (distritoText && distritoText !== 'Seleccione distrito') {
            ubicacionCompleta += distritoText;
        }
        if (provinciaText && provinciaText !== 'Seleccione provincia') {
            ubicacionCompleta += (ubicacionCompleta ? ', ' : '') + provinciaText;
        }
        if (departamentoText && departamentoText !== 'Seleccione departamento') {
            ubicacionCompleta += (ubicacionCompleta ? ', ' : '') + departamentoText;
        }
        
        $('#previewUbicacion').text(ubicacionCompleta || '-');
        
        if (empresaActual.created_at) {
            const creado = new Date(empresaActual.created_at);
            $('#previewCreado').text('Creado: ' + creado.toLocaleDateString('es-ES'));
        }
        if (empresaActual.updated_at) {
            const actualizado = new Date(empresaActual.updated_at);
            $('#previewActualizado').text('Actualizado: ' + actualizado.toLocaleDateString('es-ES'));
        }
    }

    function formatTelefono(numero) {
        if (!numero) return '';
        
        const soloNumeros = numero.replace(/\D/g, '');
        
        if (soloNumeros.startsWith('51')) {
            const celular = soloNumeros.substring(2);
            if (celular.length === 9) {
                return `+51 ${celular.substring(0, 3)} ${celular.substring(3, 6)} ${celular.substring(6)}`;
            }
        }
        
        return numero;
    }


    function inicializarEventos() {
        $('#btnGuardar, #btnGuardarFooter').on('click', guardarDatosEmpresa);
        $('#btnValidarRUC').on('click', function() {
            const ruc = $('#ruc').val().trim();
            
            if (!ruc) {
                Swal.fire({
                    icon: 'warning',
                    title: 'RUC vacío',
                    text: 'Por favor ingrese un RUC para validar',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            validarRUC(ruc).then(resultado => {
                const rucFeedback = $('#rucFeedback');
                rucFeedback.show();
                
                if (resultado.valido) {
                    rucFeedback.html(`
                        <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>RUC válido</strong> - ${resultado.mensaje}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    $('#ruc').removeClass('is-invalid').addClass('is-valid');
                } else {
                    rucFeedback.html(`
                        <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>RUC inválido</strong> - ${resultado.mensaje}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    $('#ruc').addClass('is-invalid').removeClass('is-valid');
                }
            });
        });

        $('#btnRestablecer').on('click', function() {
            Swal.fire({
                title: '¿Restablecer cambios?',
                text: 'Se perderán todos los cambios no guardados',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, restablecer',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    llenarFormulario(empresaActual);
                    actualizarVistaPrevia();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Restablecido',
                        text: 'Los cambios se han restablecido',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });

        $('#btnAbrirMaps').on('click', function() {
            const mapsUrl = $('#maps_url').val().trim();
            if (mapsUrl && isValidUrl(mapsUrl)) {
                window.open(mapsUrl, '_blank');
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'URL inválida',
                    text: 'Ingrese una URL válida de Google Maps',
                    confirmButtonText: 'Entendido'
                });
            }
        });

        $('#btnWhatsApp').on('click', function() {
            const celular = $('#celular').val().trim();
            if (celular) {
                const celularLimpio = celular.replace(/\D/g, '');
                const celularInternacional = celularLimpio.startsWith('51') ? celularLimpio : '51' + celularLimpio;
                window.open(`https://wa.me/${celularInternacional}`, '_blank');
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Celular vacío',
                    text: 'Ingrese un número de celular primero',
                    confirmButtonText: 'Entendido'
                });
            }
        });

        $('#btnExportarPDF').on('click', function() {
            Swal.fire({
                title: 'Exportar a PDF',
                text: '¿Desea generar un PDF con los datos de la empresa?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Generar PDF',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    generarPDF();
                }
            });
        });

        $('#btnAyudaUbigeo').on('click', function() {
            if (modalAyudaUbigeo) {
                modalAyudaUbigeo.show();
            }
        });

        $('input, select, textarea').on('input change', function() {
            $(this).removeClass('is-invalid is-valid');
            actualizarVistaPreviaEnTiempoReal();
            
            if ($(this).attr('id') === 'telefono' || $(this).attr('id') === 'celular') {
                const valor = $(this).val();
                const formateado = formatTelefono(valor);
                if (formateado && formateado !== valor) {
                    $(this).val(formateado);
                }
            }
        });

        $('#departamento').on('change', function() {
            const departamento = $(this).val();
            if (departamento === 'Lima') {
                $('#provincia').val('Lima');
                $('#distrito').val('Lima');
            }
        });
    }

    function actualizarVistaPreviaEnTiempoReal() {
        $('#previewRUC').text($('#ruc').val() || '-');
        $('#previewRazonSocial').text($('#razon_social').val() || '-');
        $('#previewNombreComercial').text($('#nombre_comercial').val() || '-');
        
        const propietario = ($('#propietario_nombre').val() || '') + ' ' + ($('#propietario_apellido').val() || '');
        $('#previewPropietario').text(propietario.trim() || '-');
        $('#previewTelefono').text($('#telefono').val() || '-');
        $('#previewCelular').text($('#celular').val() || '-');
        
        $('#previewDireccion').text($('#direccion').val() || '-');
        $('#previewUbigeo').text($('#ubigeo').val() || '-');
        
        const distritoText = $('#distrito_id option:selected').text();
        const provinciaText = $('#provincia_id option:selected').text();
        const departamentoText = $('#departamento_id option:selected').text();
        
        let ubicacionCompleta = '';
        if (distritoText && distritoText !== 'Seleccione distrito') {
            ubicacionCompleta += distritoText;
        }
        if (provinciaText && provinciaText !== 'Seleccione provincia') {
            ubicacionCompleta += (ubicacionCompleta ? ', ' : '') + provinciaText;
        }
        if (departamentoText && departamentoText !== 'Seleccione departamento') {
            ubicacionCompleta += (ubicacionCompleta ? ', ' : '') + departamentoText;
        }
        
        $('#previewUbicacion').text(ubicacionCompleta || '-');
    }

    function generarPDF() {
        mostrarLoading('Generando PDF...');
        
        setTimeout(() => {
            Swal.close();
            Swal.fire({
                icon: 'info',
                title: 'Función en desarrollo',
                text: 'La generación de PDF estará disponible próximamente',
                confirmButtonText: 'Entendido'
            });
        }, 1500);
    }

    function mostrarLoading(mensaje) {
        Swal.fire({
            title: mensaje || 'Procesando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
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

    $('#formDatosEmpresa').on('keypress', function(e) {
        if (e.which === 13 && !$(e.target).is('textarea')) {
            e.preventDefault();
        }
    });

    $('.card').hover(
        function() {
            $(this).addClass('shadow-sm');
        },
        function() {
            $(this).removeClass('shadow-sm');
        }
    );

    $('[data-bs-toggle="tooltip"]').tooltip();
});

$('#departamento_id').on('change', function () {
    let departamentoId = $(this).val();

    $('#provincia_id').prop('disabled', true).html('<option>Cargando...</option>');
    $('#distrito_id').prop('disabled', true).html('<option>Seleccione distrito</option>');

    if (!departamentoId) return;

    $.get(`/get-provincias/${departamentoId}`, function (data) {
        let options = '<option value="">Seleccione provincia</option>';
        data.forEach(p => {
            options += `<option value="${p.id}">${p.nombre}</option>`;
        });
        $('#provincia_id').html(options).prop('disabled', false);
    });
});

$('#provincia_id').on('change', function () {
    let provinciaId = $(this).val();

    $('#distrito_id').prop('disabled', true).html('<option>Cargando...</option>');

    if (!provinciaId) return;

    $.get(`/get-distritos/${provinciaId}`, function (data) {
        let options = '<option value="">Seleccione distrito</option>';
        data.forEach(d => {
            options += `<option value="${d.id}">${d.nombre}</option>`;
        });
        $('#distrito_id').html(options).prop('disabled', false);
    });
});
