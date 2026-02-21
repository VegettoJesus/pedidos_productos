let distritoEditar = window.datosUsuario?.distrito || '';
let provinciaEditar = window.datosUsuario?.provincia || '';
let departamentoEditar = window.datosUsuario?.departamento || '';

$(document).ready(function() {
    // Mostrar preview de imagen al seleccionar
    $('#imagen').on('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo muy grande',
                    text: 'La imagen no debe superar los 2MB',
                    confirmButtonColor: '#f77819'
                });
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImagen').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // Toggle contraseña
    $('#togglePassword').on('click', function() {
        const password = $('#password');
        const type = password.attr('type') === 'password' ? 'text' : 'password';
        password.attr('type', type);
        $(this).find('i').toggleClass('bi-eye bi-eye-slash');
    });

    // Medidor de fuerza de contraseña
    $('#password').on('input', function() {
        const password = $(this).val();
        const strengthBar = $('#passwordStrength');
        
        if (!password) {
            strengthBar.removeClass('weak medium strong').css('width', '0');
            return;
        }
        
        let strength = 0;
        
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        strengthBar.removeClass('weak medium strong');
        
        if (strength <= 2) {
            strengthBar.addClass('weak');
        } else if (strength <= 4) {
            strengthBar.addClass('medium');
        } else {
            strengthBar.addClass('strong');
        }
    });

    // Función para cargar provincias
    function cargarProvincias(departamentoId, seleccionarProvincia = null) {
        if (!departamentoId) {
            $('#provincia_id').html('<option value="">Seleccione</option>').prop('disabled', true);
            $('#distrito_id').html('<option value="">Seleccione</option>').prop('disabled', true);
            return;
        }

        // Usar el preloader global
        let loader = showPreloader("Cargando provincias...", "cargar");

        $.ajax({
            url: '/get-provincias/' + departamentoId,
            type: 'GET',
            success: function(data) {
                hidePreloader(loader);
                
                let options = '<option value="">Seleccione provincia</option>';
                $.each(data, function(key, provincia) {
                    options += '<option value="' + provincia.id + '">' + provincia.nombre + '</option>';
                });
                
                $('#provincia_id').html(options).prop('disabled', false);
                
                // Si hay provincia para seleccionar
                if (seleccionarProvincia) {
                    $('#provincia_id').val(seleccionarProvincia);
                    
                    // Cargar distritos inmediatamente
                    cargarDistritos(seleccionarProvincia, distritoEditar);
                } else {
                    $('#distrito_id').html('<option value="">Primero seleccione provincia</option>').prop('disabled', true);
                }
            },
            error: function(xhr, status, error) {
                hidePreloader(loader);
                console.error('Error cargando provincias:', error);
                
                $('#provincia_id').html('<option value="">Error al cargar</option>').prop('disabled', true);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar las provincias',
                    confirmButtonColor: '#f77819'
                });
            }
        });
    }

    // Función para cargar distritos
    function cargarDistritos(provinciaId, seleccionarDistrito = null) {
        if (!provinciaId) {
            $('#distrito_id').html('<option value="">Seleccione</option>').prop('disabled', true);
            return;
        }

        let loader = showPreloader("Cargando distritos...", "cargar");

        $.ajax({
            url: '/get-distritos/' + provinciaId,
            type: 'GET',
            success: function(data) {
                hidePreloader(loader);
                
                let options = '<option value="">Seleccione distrito</option>';
                $.each(data, function(key, distrito) {
                    options += '<option value="' + distrito.id + '">' + distrito.nombre + '</option>';
                });
                
                $('#distrito_id').html(options).prop('disabled', false);

                // Si hay distrito para seleccionar
                if (seleccionarDistrito) {
                    $('#distrito_id').val(seleccionarDistrito);
                }
            },
            error: function(xhr, status, error) {
                hidePreloader(loader);
                console.error('Error cargando distritos:', error);
                
                $('#distrito_id').html('<option value="">Error al cargar</option>').prop('disabled', true);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los distritos',
                    confirmButtonColor: '#f77819'
                });
            }
        });
    }

    // Evento change de departamento
    $('#departamento_id').on('change', function() {
        let departamentoId = $(this).val();
        cargarProvincias(departamentoId);
    });

    // Evento change de provincia
    $('#provincia_id').on('change', function() {
        let provinciaId = $(this).val();
        cargarDistritos(provinciaId);
    });

    function cargarDatosIniciales() {
        if (departamentoEditar) {
            $('#departamento_id').val(departamentoEditar);
            cargarProvincias(departamentoEditar, provinciaEditar);
        } else {
        }
    }

    if (document.readyState === 'complete') {
        cargarDatosIniciales();
    } else {
        window.addEventListener('load', cargarDatosIniciales);
    }

    // Enviar formulario
    $('#formPerfil').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let isValid = true;
        
        // Limpiar mensajes de error anteriores
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Validar campos obligatorios
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
                
                let fieldName = $(this).attr('name');
                let fieldLabel = $(this).siblings('label').text() || fieldName;
                $(this).after('<div class="invalid-feedback">El campo ' + fieldLabel + ' es obligatorio</div>');
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor complete todos los campos obligatorios',
                confirmButtonColor: '#f77819'
            });
            return false;
        }

        // Validar contraseña si se ingresa
        let password = $('#password').val();
        if (password) {
            let regexPassword = /^(?=.*[A-Z])(?=.*[a-zA-Z])(?=.*\d).{6,}$/;
            if (!regexPassword.test(password)) {
                $('#password').addClass('is-invalid');
                $('#password').after('<div class="invalid-feedback">La contraseña debe tener mínimo 6 caracteres, al menos una mayúscula, letras y números</div>');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña inválida',
                    html: 'La contraseña debe tener:<br>- Mínimo 6 caracteres<br>- Al menos una letra mayúscula<br>- Letras y números',
                    confirmButtonColor: '#f77819'
                });
                return false;
            }
        }

        let mensajeLoader = showPreloader("Guardando cambios...", "guardar");

        $.ajax({
            url: '/perfil/actualizar',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hidePreloader(mensajeLoader);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false,
                        background: 'white',
                        iconColor: '#f77819'
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                hidePreloader(mensajeLoader);
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    
                    for (let field in errors) {
                        errorMsg += '• ' + errors[field][0] + '\n';
                        $('#' + field).addClass('is-invalid');
                        $('#' + field).after('<div class="invalid-feedback">' + errors[field][0] + '</div>');
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        html: errorMsg.replace(/\n/g, '<br>'),
                        confirmButtonColor: '#f77819'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo guardar los cambios',
                        confirmButtonColor: '#f77819'
                    });
                }
            }
        });
    });
});