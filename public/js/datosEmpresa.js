$(document).ready(function() {
    // Variables globales
    let empresaActual = null;
    let modalAyudaUbigeo = null;
    
    // Inicializar modales
    setTimeout(() => {
        const modalUbigeo = document.getElementById('modalAyudaUbigeo');
        if (modalUbigeo) modalAyudaUbigeo = new bootstrap.Modal(modalUbigeo);
    }, 100);

    // Inicializar
    cargarDatosEmpresa();
    inicializarEventos();
    actualizarVistaPrevia();

    // ===== FUNCIONES PRINCIPALES =====

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
        
        // Información Fiscal
        $('#ruc').val(data.ruc || '');
        $('#razon_social').val(data.razon_social || '');
        $('#nombre_comercial').val(data.nombre_comercial || '');
        $('#propietario_nombre').val(data.propietario_nombre || '');
        $('#propietario_apellido').val(data.propietario_apellido || '');
        
        // Ubicación
        $('#direccion').val(data.direccion || '');
        $('#ubigeo').val(data.ubigeo || '');
        $('#maps_url').val(data.maps_url || '');
        
        // Contacto
        $('#telefono').val(data.telefono || '');
        $('#celular').val(data.celular || '');
        
        // Cargar ubicación con AJAX encadenado
        if (data.departamento_id) {
            // Primero, seleccionar el departamento
            $('#departamento_id').val(data.departamento_id);
            
            // Cargar provincias del departamento seleccionado
            cargarProvincias(data.departamento_id, function() {
                if (data.provincia_id) {
                    // Una vez cargadas las provincias, seleccionar la provincia
                    $('#provincia_id').val(data.provincia_id);
                    
                    // Cargar distritos de la provincia seleccionada
                    cargarDistritos(data.provincia_id, function() {
                        if (data.distrito_id) {
                            // Una vez cargados los distritos, seleccionar el distrito
                            $('#distrito_id').val(data.distrito_id);
                            
                            // Actualizar vista previa después de cargar todo
                            setTimeout(() => {
                                actualizarVistaPreviaEnTiempoReal();
                            }, 200);
                        }
                    });
                }
            });
        }
    }

    // Función para cargar provincias
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

    // Función para cargar distritos
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

    // ===== FUNCIONES DE VALIDACIÓN =====

    function validarFormulario() {
        let isValid = true;
        
        // Validar campos requeridos
        const requiredFields = [
            'ruc', 'razon_social', 'propietario_nombre', 'propietario_apellido',
            'direccion', 'ubigeo', 'departamento_id', 'provincia_id', 'distrito_id'
        ];
        
        requiredFields.forEach(field => {
            const element = $(`#${field}`);
            console.log(element)
            if (!element.val() || !element.val().toString().trim()) {
                element.addClass('is-invalid');
                isValid = false;
            } else {
                element.removeClass('is-invalid');
            }
        });

        // Validar RUC (11 dígitos)
        const ruc = $('#ruc').val().trim();
        if (ruc.length !== 11 || !/^\d+$/.test(ruc)) {
            $('#ruc').addClass('is-invalid');
            $('#errorRUC').text('El RUC debe tener exactamente 11 dígitos numéricos.');
            isValid = false;
        }

        // Validar URL de Google Maps si existe
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

    /* function validarRUC(ruc) {
        return new Promise((resolve) => {
            if (!ruc || ruc.length !== 11 || !/^\d+$/.test(ruc)) {
                resolve({ valido: false, mensaje: 'Formato inválido (debe tener 11 dígitos)' });
                return;
            }

            $.ajax({
                url: 'datosEmpresa',
                method: 'POST',
                data: {
                    opcion: 'ConsultarRUC',
                    ruc: ruc,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                beforeSend: function() {
                    mostrarLoading('Validando RUC...');
                },
                success: function(response) {
                    Swal.close();
                    if (response && response.success) {
                        resolve({ 
                            valido: response.ruc_valido || false, 
                            mensaje: response.message,
                            datos: response.data
                        });
                    } else {
                        resolve({ 
                            valido: false, 
                            mensaje: response?.message || 'Error al validar RUC' 
                        });
                    }
                },
                error: function() {
                    Swal.close();
                    resolve({ 
                        valido: false, 
                        mensaje: 'Error de conexión al validar RUC' 
                    });
                }
            });
        });
    } */

    // Agrega esto en la función inicializarEventos():
    $('#departamento_id, #provincia_id, #distrito_id').on('change', function() {
        actualizarVistaPreviaEnTiempoReal();
    });

    // ===== FUNCIONES DE VISUALIZACIÓN =====

    function actualizarVistaPrevia() {
        if (!empresaActual) return;
        
        // Información Fiscal
        $('#previewRUC').text(empresaActual.ruc || '-');
        $('#previewRazonSocial').text(empresaActual.razon_social || '-');
        $('#previewNombreComercial').text(empresaActual.nombre_comercial || '-');
        
        // Propietario
        const propietario = (empresaActual.propietario_nombre || '') + ' ' + (empresaActual.propietario_apellido || '');
        $('#previewPropietario').text(propietario.trim() || '-');
        $('#previewTelefono').text(empresaActual.telefono || '-');
        $('#previewCelular').text(empresaActual.celular || '-');
        
        // Ubicación
        $('#previewDireccion').text(empresaActual.direccion || '-');
        $('#previewUbigeo').text(empresaActual.ubigeo || '-');
        
        // Obtener nombres de ubicación de los selects
        const distritoText = $('#distrito_id option:selected').text();
        const provinciaText = $('#provincia_id option:selected').text();
        const departamentoText = $('#departamento_id option:selected').text();
        
        // Construir la ubicación completa
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
        
        // Timestamps
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
        
        // Eliminar todo excepto números
        const soloNumeros = numero.replace(/\D/g, '');
        
        // Formato Perú: +51 987 654 321
        if (soloNumeros.startsWith('51')) {
            const celular = soloNumeros.substring(2);
            if (celular.length === 9) {
                return `+51 ${celular.substring(0, 3)} ${celular.substring(3, 6)} ${celular.substring(6)}`;
            }
        }
        
        return numero;
    }

    // ===== FUNCIONES AUXILIARES =====

    function inicializarEventos() {
        // Guardar desde botones
        $('#btnGuardar, #btnGuardarFooter').on('click', guardarDatosEmpresa);

        // Validar RUC
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

        // Verificar RUC (más completo)
        /* $('#btnVerificarRUC').on('click', function() {
            Swal.fire({
                title: 'Verificar RUC en SUNAT',
                input: 'text',
                inputLabel: 'Ingrese el RUC a verificar',
                inputPlaceholder: '20123456789',
                inputAttributes: {
                    maxlength: 11,
                    pattern: '[0-9]{11}'
                },
                showCancelButton: true,
                confirmButtonText: 'Verificar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debe ingresar un RUC';
                    }
                    if (value.length !== 11 || !/^\d+$/.test(value)) {
                        return 'El RUC debe tener 11 dígitos numéricos';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    validarRUC(result.value).then(resultado => {
                        Swal.fire({
                            icon: resultado.valido ? 'success' : 'error',
                            title: resultado.valido ? 'RUC Válido' : 'RUC Inválido',
                            html: `
                                <div class="text-start">
                                    <p><strong>RUC:</strong> ${result.value}</p>
                                    <p><strong>Estado:</strong> ${resultado.mensaje}</p>
                                    ${resultado.datos ? `
                                        <hr>
                                        <p><strong>Razón Social:</strong> ${resultado.datos.razon_social || 'N/A'}</p>
                                        <p><strong>Estado:</strong> ${resultado.datos.estado || 'N/A'}</p>
                                        <p><strong>Condición:</strong> ${resultado.datos.condicion || 'N/A'}</p>
                                    ` : ''}
                                </div>
                            `,
                            confirmButtonText: 'Entendido'
                        });
                    });
                }
            });
        }); */

        // Restablecer formulario
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

        // Abrir Google Maps
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

        // Abrir WhatsApp
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

        // Exportar PDF
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

        // Ayuda UBIGEO
        $('#btnAyudaUbigeo').on('click', function() {
            if (modalAyudaUbigeo) {
                modalAyudaUbigeo.show();
            }
        });

        // Validación en tiempo real y actualización de vista previa
        $('input, select, textarea').on('input change', function() {
            $(this).removeClass('is-invalid is-valid');
            actualizarVistaPreviaEnTiempoReal();
            
            // Formatear teléfono en tiempo real
            if ($(this).attr('id') === 'telefono' || $(this).attr('id') === 'celular') {
                const valor = $(this).val();
                const formateado = formatTelefono(valor);
                if (formateado && formateado !== valor) {
                    $(this).val(formateado);
                }
            }
        });

        // Autocompletar provincia y distrito al seleccionar departamento
        $('#departamento').on('change', function() {
            const departamento = $(this).val();
            if (departamento === 'Lima') {
                $('#provincia').val('Lima');
                $('#distrito').val('Lima');
            }
            // Puedes agregar más lógica para otros departamentos
        });
    }

    function actualizarVistaPreviaEnTiempoReal() {
        // Actualizar vista previa dinámicamente
        $('#previewRUC').text($('#ruc').val() || '-');
        $('#previewRazonSocial').text($('#razon_social').val() || '-');
        $('#previewNombreComercial').text($('#nombre_comercial').val() || '-');
        
        const propietario = ($('#propietario_nombre').val() || '') + ' ' + ($('#propietario_apellido').val() || '');
        $('#previewPropietario').text(propietario.trim() || '-');
        $('#previewTelefono').text($('#telefono').val() || '-');
        $('#previewCelular').text($('#celular').val() || '-');
        
        $('#previewDireccion').text($('#direccion').val() || '-');
        $('#previewUbigeo').text($('#ubigeo').val() || '-');
        
        // Obtener nombres de ubicación de los selects
        const distritoText = $('#distrito_id option:selected').text();
        const provinciaText = $('#provincia_id option:selected').text();
        const departamentoText = $('#departamento_id option:selected').text();
        
        // Construir la ubicación completa
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
        // Aquí puedes implementar la generación de PDF
        // Por ejemplo, usando jsPDF o enviando una petición al servidor
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

    // Prevenir envío accidental con Enter
    $('#formDatosEmpresa').on('keypress', function(e) {
        if (e.which === 13 && !$(e.target).is('textarea')) {
            e.preventDefault();
        }
    });

    // Efecto hover en tarjetas
    $('.card').hover(
        function() {
            $(this).addClass('shadow-sm');
        },
        function() {
            $(this).removeClass('shadow-sm');
        }
    );

    // Inicializar tooltips
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
