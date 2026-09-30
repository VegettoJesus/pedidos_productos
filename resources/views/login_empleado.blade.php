<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Empleados — {{ ConfiguracionHelper::getCompanyName() }}</title>
    
    <link rel="icon" href="{{ ConfiguracionHelper::getFavicon() }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ ConfiguracionHelper::getFavicon() }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fuente Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Bootstrap 5 JS (bundle con Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- CSS del login  -->
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
	<script src="{{ asset('js/login.js') }}" defer></script>
</head>
<body>

<div class="login-wrapper">

    <aside class="login-brand">

        <div class="brand-header">
            <div class="brand-logo">
                <i class="bi bi-shop"></i>
            </div>
            <div class="brand-info">
                <span class="brand-name">{{ ConfiguracionHelper::getCompanyName() }}</span>
                <span class="brand-tag">Panel de empleados</span>
            </div>
        </div>

        <div class="brand-illustration">
			<img 
				src="{{ asset('img/login-illustration.png') }}" 
				alt="Tienda virtual - Panel de empleados"
				loading="eager"
				width="500"
				height="500"
			>
		</div>

        <div class="brand-footer">
            <p class="brand-quote">
                "Gestiona tu tienda virtual desde un solo lugar"
            </p>
            <div class="brand-dots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>

    </aside>

    <main class="login-panel">

        <div class="login-card">

            <div class="login-header">
                <h1 class="login-title">Bienvenido</h1>
                <p class="login-subtitle">
                    Ingresa tus credenciales para acceder al panel
                </p>
            </div>

            <form method="POST" action="{{ url('/login') }}" class="login-form" id="loginForm">
                @csrf

                <div class="form-group">
                    <label for="emailInput" class="form-label">Correo electrónico</label>
                    <div class="input-wrapper">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input
                            type="email"
                            id="emailInput"
                            name="email"
                            class="form-input"
                            placeholder="empleado@tienda.com"
                            autocomplete="email"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="passwordInput" class="form-label">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input
                            type="password"
                            id="passwordInput"
                            name="password"
                            class="form-input"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            aria-label="Mostrar/ocultar contraseña"
                        >
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember" id="rememberCheck">
                        <span class="checkbox-label">Recordarme</span>
                    </label>
                    <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="btn-text">Iniciar sesión</span>
                    <i class="bi bi-arrow-right btn-icon"></i>
                </button>

            </form>

            <div class="login-footer">
                <p>
                    <i class="bi bi-shield-lock-fill"></i>
                    Acceso restringido a personal autorizado
                </p>
            </div>

        </div>

    </main>

</div>
<!-- Modal de recuperación de contraseña -->
<div class="modal fade" id="modalRecuperarPassword" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden;">
            
            <!-- PASO 1: Ingresar correo -->
            <div id="paso1" class="modal-paso">
                <div class="modal-header" style="background: linear-gradient(135deg, #f35b08, #f77819); color: white; border: none; padding: 1.5rem;">
                    <h5 class="modal-title" style="color: white !important;">
                        <i class="bi bi-key-fill me-2"></i> Recuperar contraseña
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4" style="font-size: 0.9rem;">
                        Ingresa tu correo electrónico y te enviaremos un código de verificación.
                    </p>
                    <div class="mb-3">
                        <label for="emailRecuperacion" class="form-label" style="font-weight: 500;">
                            Correo electrónico
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope-fill input-icon"></i>
                            <input 
                                type="email" 
                                id="emailRecuperacion" 
                                class="form-input" 
                                placeholder="empleado@tienda.com"
                                autocomplete="email"
                            >
                        </div>
                    </div>
                    <div id="mensajePaso1" class="small"></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-submit-modal" id="btnEnviarCodigo">
                        <span class="btn-text">Enviar código</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- PASO 2: Ingresar código -->
            <div id="paso2" class="modal-paso" style="display: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #f35b08, #f77819); color: white; border: none; padding: 1.5rem;">
                    <h5 class="modal-title" style="color: white !important;">
                        <i class="bi bi-shield-lock-fill me-2"></i> Verifica tu código
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">
                        Ingresa el código de 6 dígitos que enviamos a <strong id="emailMostrado"></strong>
                    </p>

                    <!-- Contador -->
                    <div class="contador-container" id="contadorContainer">
                        <i class="bi bi-clock-history me-2"></i>
                        <span>El código expira en: </span>
                        <strong id="contadorTexto">15:00</strong>
                    </div>

                    <div class="mb-3 mt-3">
                        <label for="codigoInput" class="form-label" style="font-weight: 500;">
                            Código de verificación
                        </label>
                        <input 
                            type="text" 
                            id="codigoInput" 
                            class="form-input codigo-input" 
                            placeholder="000000"
                            maxlength="6"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            style="text-align: center; font-size: 1.5rem; letter-spacing: 8px; font-weight: 600;"
                        >
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn-link-reenviar" id="btnReenviarCodigo" disabled>
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            <span id="textoReenviar">Reenviar en 60s</span>
                        </button>
                    </div>

                    <div id="mensajePaso2" class="small mt-2"></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" id="btnVolverPaso1">
                        <i class="bi bi-arrow-left me-1"></i> Atrás
                    </button>
                    <button type="button" class="btn-submit-modal" id="btnValidarCodigo">
                        <span class="btn-text">Validar código</span>
                        <i class="bi bi-check-lg"></i>
                    </button>
                </div>
            </div>

            <!-- PASO 3: Nueva contraseña -->
            <div id="paso3" class="modal-paso" style="display: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #f35b08, #f77819); color: white; border: none; padding: 1.5rem;">
                    <h5 class="modal-title" style="color: white !important;">
                        <i class="bi bi-shield-check me-2"></i> Nueva contraseña
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4" style="font-size: 0.9rem;">
                        Crea una nueva contraseña segura para tu cuenta.
                    </p>

                    <div class="mb-3">
                        <label for="nuevaPassword" class="form-label" style="font-weight: 500;">
                            Nueva contraseña
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock-fill input-icon"></i>
                            <input 
                                type="password" 
                                id="nuevaPassword" 
                                class="form-input" 
                                placeholder="••••••••"
                                autocomplete="new-password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePasswordModal('nuevaPassword', 'eyeNueva')">
                                <i class="bi bi-eye" id="eyeNueva"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmarPassword" class="form-label" style="font-weight: 500;">
                            Confirmar contraseña
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock-fill input-icon"></i>
                            <input 
                                type="password" 
                                id="confirmarPassword" 
                                class="form-input" 
                                placeholder="••••••••"
                                autocomplete="new-password"
                            >
                        </div>
                    </div>

                    <!-- Requisitos -->
                    <div class="password-requirements">
                        <div class="req-item" id="req-length">
                            <i class="bi bi-circle"></i> Mínimo 6 caracteres
                        </div>
                        <div class="req-item" id="req-match">
                            <i class="bi bi-circle"></i> Las contraseñas coinciden
                        </div>
                    </div>

                    <div id="mensajePaso3" class="small mt-2"></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn-submit-modal" id="btnCambiarPassword" style="width: 100%;">
                        <span class="btn-text">Cambiar contraseña</span>
                        <i class="bi bi-check-circle"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
@if(session('login_error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error de acceso',
            text: @json(session('login_error')),
            confirmButtonColor: '#F4AB28',
            confirmButtonText: 'Entendido'
        });
    </script>
@endif
</body>
</html>