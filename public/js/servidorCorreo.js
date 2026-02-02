$(document).ready(function() {
    // Variables globales
    let configuracionActual = null;
    let configuracionesPredefinidas = {};
    let modalProbarConexion = null;
    let modalAyudaServidores = null;
    let contraseñaModificada = false;
    
    // Inicializar modales
    setTimeout(() => {
        const modalPrueba = document.getElementById('modalProbarConexion');
        const modalAyuda = document.getElementById('modalAyudaServidores');
        
        if (modalPrueba) modalProbarConexion = new bootstrap.Modal(modalPrueba);
        if (modalAyuda) modalAyudaServidores = new bootstrap.Modal(modalAyuda);
    }, 100);

    // Inicializar
    cargarConfiguracion();
    cargarConfiguracionesPredefinidas();
    inicializarEventos();
    actualizarInfoSeguridad();

    // ===== FUNCIONES PRINCIPALES =====

    function cargarConfiguracion() {
        $.ajax({
            url: 'servidorCorreo',
            method: 'POST',
            data: {
                opcion: 'Listar', 
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            beforeSend: function() {
                mostrarLoading('Cargando configuración...');
            },
            success: function(response) {
                Swal.close();
                if (response && response.success) {
                    configuracionActual = response.data || {};
                    llenarFormulario(configuracionActual);
                    actualizarInfoConexion();
                } else {
                    mostrarError('Error', response?.message || 'No se pudo cargar la configuración');
                }
            },
            error: function(xhr) {
                mostrarError('Error de conexión', 'No se pudo conectar con el servidor');
            }
        });
    }

    function cargarConfiguracionesPredefinidas() {
        $.ajax({
            url: 'servidorCorreo',
            method: 'POST',
            data: {
                opcion: 'GetPredefinidas', 
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success && response.configuraciones) {
                    configuracionesPredefinidas = response.configuraciones;
                    mostrarConfiguracionesRapidas();
                }
            }
        });
    }

    function llenarFormulario(data) {
        if (!data) return;
        
        // Configuración SMTP
        $('#servidor_correo').val(data.servidor_correo || '');
        $('#puerto').val(data.puerto || 587);
        $('#seguridad').val(data.seguridad || 'tls');
        $('#activo').prop('checked', data.activo !== false);
        
        // Credenciales
        $('#nombre_acceso').val(data.nombre_acceso || '');
        // No llenar contraseña por seguridad
        $('#infoContraseñaActual').text(data.contraseña ? '(Contraseña guardada)' : '');
        
        // Actualizar info seguridad
        actualizarInfoSeguridad();
    }

    function guardarConfiguracion() {
        if (!validarFormulario()) {
            return;
        }

        const formData = {
            servidor_correo: $('#servidor_correo').val().trim(),
            puerto: parseInt($('#puerto').val()) || 587,
            nombre_acceso: $('#nombre_acceso').val().trim(),
            seguridad: $('#seguridad').val(),
            activo: $('#activo').is(':checked') ? 1 : 0,
            _token: $('meta[name="csrf-token"]').attr('content'),
            opcion: 'Guardar'
        };

        // Solo incluir contraseña si se modificó
        if (contraseñaModificada && $('#contraseña').val()) {
            formData.contraseña = $('#contraseña').val();
            formData.confirmar_contraseña = $('#confirmar_contraseña').val();
        }

        Swal.fire({
            title: '¿Guardar configuración?',
            text: 'Se actualizará la configuración del servidor de correo',
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
            url: 'servidorCorreo',
            method: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                mostrarLoading('Guardando configuración...');
            },
            success: function(response) {
                Swal.close();
                if (response && response.success) {
                    configuracionActual = response.data || {};
                    contraseñaModificada = false;
                    
                    // Limpiar campos de contraseña
                    $('#contraseña').val('');
                    $('#confirmar_contraseña').val('');
                    $('#confirmarContraseñaContainer').hide();
                    $('#infoContraseñaActual').text('(Contraseña actualizada)');
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: response.message || 'Configuración guardada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        actualizarInfoConexion();
                    });
                } else {
                    mostrarError('Error', response?.message || 'No se pudo guardar la configuración');
                }
            },
            error: function(xhr) {
                let message = 'Error al guardar la configuración';
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
        const requiredFields = ['servidor_correo', 'puerto', 'nombre_acceso', 'seguridad'];
        
        requiredFields.forEach(field => {
            const element = $(`#${field}`);
            if (!element.val() || !element.val().toString().trim()) {
                element.addClass('is-invalid');
                isValid = false;
            } else {
                element.removeClass('is-invalid');
            }
        });

        // Validar puerto
        const puerto = parseInt($('#puerto').val());
        if (isNaN(puerto) || puerto < 1 || puerto > 65535) {
            $('#puerto').addClass('is-invalid');
            isValid = false;
        }

        // Validar contraseña si se modificó
        if (contraseñaModificada) {
            const contraseña = $('#contraseña').val();
            const confirmar = $('#confirmar_contraseña').val();
            
            if (!contraseña) {
                $('#contraseña').addClass('is-invalid');
                $('#errorContraseña').text('La contraseña es requerida');
                isValid = false;
            } else if (contraseña.length < 6) {
                $('#contraseña').addClass('is-invalid');
                $('#errorContraseña').text('La contraseña debe tener al menos 6 caracteres');
                isValid = false;
            } else if (contraseña !== confirmar) {
                $('#confirmar_contraseña').addClass('is-invalid');
                $('#errorConfirmarContraseña').text('Las contraseñas no coinciden');
                isValid = false;
            }
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

    function validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    // ===== FUNCIONES DE VISUALIZACIÓN =====

    function mostrarConfiguracionesRapidas() {
        const container = $('#configuracionesRapidas');
        container.empty();
        
        Object.entries(configuracionesPredefinidas).forEach(([key, config]) => {
            const card = `
                <div class="col-md-4">
                    <div class="card config-rapida-card border h-100" data-key="${key}" style="cursor: pointer;">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-lightning-charge text-warning me-2"></i>${config.descripcion}
                            </h6>
                            <p class="card-text small text-muted mb-2">
                                <i class="bi bi-server me-1"></i> ${config.servidor_correo}
                            </p>
                            <p class="card-text small text-muted mb-0">
                                <i class="bi bi-signpost me-1"></i> Puerto: ${config.puerto} | 
                                <i class="bi bi-shield me-1"></i> ${config.seguridad.toUpperCase()}
                            </p>
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });

        // Evento para seleccionar configuración rápida
        $('.config-rapida-card').on('click', function() {
            const key = $(this).data('key');
            const config = configuracionesPredefinidas[key];
            
            if (config) {
                Swal.fire({
                    title: `Usar configuración ${config.descripcion}?`,
                    text: 'Se llenarán automáticamente los campos del servidor',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0dcaf0',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, usar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#servidor_correo').val(config.servidor_correo);
                        $('#puerto').val(config.puerto);
                        $('#seguridad').val(config.seguridad);
                        actualizarInfoSeguridad();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Configuración aplicada',
                            text: 'Complete los campos restantes (usuario y contraseña)',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    }

    function actualizarInfoSeguridad() {
        const seguridad = $('#seguridad').val();
        let info = '';
        
        switch(seguridad) {
            case 'tls':
                info = 'Conexión cifrada después del handshake (recomendado, puerto 587).';
                break;
            case 'ssl':
                info = 'Conexión cifrada desde el inicio (puerto 465).';
                break;
            case 'ninguna':
                info = 'Sin encriptación (no recomendado para producción, puerto 25).';
                break;
        }
        
        $('#infoSeguridad').text(info);
    }

    function actualizarInfoConexion() {
        if (configuracionActual && configuracionActual.updated_at) { // ← Cambiar aquí
            const fecha = new Date(configuracionActual.updated_at);
            $('#ultimaActualizacion').text(fecha.toLocaleString('es-ES'));
        }
        
        if (configuracionActual && configuracionActual.activo) {
            $('#estadoConexion').html('<span class="badge bg-success">Activo</span>');
        } else {
            $('#estadoConexion').html('<span class="badge bg-warning">Inactivo</span>');
        }
        
        // Actualizar detalles técnicos
        const detalles = `
        Servidor: ${$('#servidor_correo').val() || 'No configurado'}
        Puerto: ${$('#puerto').val() || '587'}
        Seguridad: ${$('#seguridad').val()?.toUpperCase() || 'TLS'}
        Usuario: ${$('#nombre_acceso').val() || 'No configurado'}
        Estado: ${$('#activo').is(':checked') ? 'ACTIVO' : 'INACTIVO'}
        `.trim();
        
        $('#detallesConfiguracion').text(detalles);
    }

    // ===== FUNCIONES AUXILIARES =====

    function inicializarEventos() {
        // Guardar desde botones
        $('#btnGuardar, #btnGuardarFooter').on('click', guardarConfiguracion);

        // Probar conexión
        $('#btnProbarConexion').on('click', function() {
            if (modalProbarConexion) {
                // Preguntar email antes de abrir modal
                Swal.fire({
                    title: 'Email de prueba',
                    input: 'email',
                    inputLabel: 'Ingrese el email donde enviar la prueba',
                    inputPlaceholder: 'ejemplo@email.com',
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Debe ingresar un email';
                        }
                        if (!validarEmail(value)) {
                            return 'Ingrese un email válido';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#emailPrueba').val(result.value);
                        modalProbarConexion.show();
                    }
                });
            }
        });

        // Iniciar prueba de conexión
        $('#btnIniciarPrueba').on('click', function() {
            const email = $('#emailPrueba').val().trim();
            
            if (!email || !validarEmail(email)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Email inválido',
                    text: 'Por favor ingrese un email válido',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Obtener configuración actual del formulario
            const configPrueba = {
                servidor_correo: $('#servidor_correo').val().trim(),
                puerto: parseInt($('#puerto').val()) || 587,
                nombre_acceso: $('#nombre_acceso').val().trim(),
                contraseña: $('#contraseña').val() || (contraseñaModificada ? '' : '[usar guardada]'),
                seguridad: $('#seguridad').val()
            };

            // Validar que tengamos los datos necesarios
            if (!configPrueba.servidor_correo || !configPrueba.nombre_acceso) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Configuración incompleta',
                    text: 'Complete los campos de servidor y usuario antes de probar',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Si la contraseña está vacía y no se modificó, usar la guardada
            if (!configPrueba.contraseña && !contraseñaModificada) {
                delete configPrueba.contraseña;
            }

            enviarPruebaConexion(email, configPrueba);
        });

        // Probar envío de correo
        $('#btnProbarCorreo').on('click', function() {
            Swal.fire({
                title: 'Probar envío de correo',
                text: 'Se enviará un email de prueba usando la configuración guardada',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#0dcaf0',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Email de prueba',
                        input: 'email',
                        inputLabel: 'Ingrese el email de destino',
                        inputPlaceholder: 'ejemplo@email.com',
                        showCancelButton: true,
                        confirmButtonText: 'Enviar prueba',
                        cancelButtonText: 'Cancelar',
                        inputValidator: (value) => {
                            if (!value) return 'Debe ingresar un email';
                            if (!validarEmail(value)) return 'Email inválido';
                        }
                    }).then((emailResult) => {
                        if (emailResult.isConfirmed) {
                            enviarPruebaConexion(emailResult.value, null);
                        }
                    });
                }
            });
        });

        // Restablecer formulario
        $('#btnRestablecer').on('click', function() {
            Swal.fire({
                title: '¿Restablecer cambios?',
                text: 'Se perderán los cambios no guardados',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, restablecer',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    llenarFormulario(configuracionActual);
                    contraseñaModificada = false;
                    $('#contraseña').val('');
                    $('#confirmar_contraseña').val('');
                    $('#confirmarContraseñaContainer').hide();
                    
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

        // Mostrar/Ocultar contraseña
        $('#btnMostrarContraseña').on('click', function() {
            const input = $('#contraseña');
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('bi-eye').addClass('bi-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('bi-eye-slash').addClass('bi-eye');
            }
        });

        // Detectar cambios en contraseña
        $('#contraseña').on('input', function() {
            const valor = $(this).val();
            contraseñaModificada = valor.length > 0;
            
            if (contraseñaModificada) {
                $('#confirmarContraseñaContainer').show();
                $('#infoContraseñaActual').text('(Nueva contraseña)');
            } else {
                $('#confirmarContraseñaContainer').hide();
                $('#infoContraseñaActual').text(configuracionActual?.contraseña ? '(Contraseña guardada)' : '');
            }
        });

        // Ayuda servidores
        $('#btnAyudaServidor').on('click', function() {
            if (modalAyudaServidores) {
                modalAyudaServidores.show();
            }
        });

        // Validación en tiempo real
        $('input, select').on('input change', function() {
            $(this).removeClass('is-invalid');
            actualizarInfoConexion();
            
            if ($(this).attr('id') === 'seguridad') {
                actualizarInfoSeguridad();
            }
        });
    }

    function enviarPruebaConexion(email, configPrueba) {
        const datos = {
            email: email,
            configuracion: configPrueba,
            _token: $('meta[name="csrf-token"]').attr('content'),
            opcion: 'ProbarConexion'
        };

        $.ajax({
            url: 'servidorCorreo',
            method: 'POST',
            data: datos,
            dataType: 'json',
            beforeSend: function() {
                if (modalProbarConexion) {
                    modalProbarConexion.hide();
                }
                mostrarLoading('Enviando email de prueba...');
            },
            success: function(response) {
                Swal.close();
                if (response && response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        html: `
                            <p>Email de prueba enviado correctamente a:</p>
                            <p class="fw-bold">${email}</p>
                            <p class="small text-muted mt-2">Verifica la bandeja de entrada (y spam) del correo.</p>
                        `,
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    mostrarError('Error al enviar', response?.message || 'No se pudo enviar el email de prueba');
                }
            },
            error: function(xhr) {
                let message = 'Error al enviar el email de prueba';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                mostrarError('Error', message);
            }
        });
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
    $('#formServidorCorreo').on('keypress', function(e) {
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