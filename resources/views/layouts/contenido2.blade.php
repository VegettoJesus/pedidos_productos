<!DOCTYPE html>
<html lang="es">
<meta name="csrf-token" content="{{ csrf_token() }}">
<head>
    @include('layouts.head2')
</head>
<body>
    @include('layouts.header2')
    @include('layouts.sidebar2')
    <!-- Después del header, antes del contenido principal -->
    @if(Auth::check() && Auth::user()->id_rol == 2)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                fetch('/api/mis-valoraciones/count')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const badge = document.getElementById('valoracionesCount');
                            if (badge) {
                                badge.textContent = data.count;
                                if (data.count > 0) {
                                    badge.style.display = 'flex';
                                } else {
                                    badge.style.display = 'none';
                                }
                            }
                        }
                    })
                    .catch(() => {
                        const badge = document.getElementById('valoracionesCount');
                        if (badge) badge.style.display = 'none';
                    });
            });
        </script>
    @endif
    <main class="content">
        @isset($contenido2)
            @include($contenido2)
        @endisset
    </main>
    @include('layouts.footer2')
    <div class="modal fade" id="ratingAuthModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-star-fill text-warning me-2"></i>
                        Iniciar sesión
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">
                        Inicia sesión o crea una cuenta para continuar.
                    </p>

                    <!-- Tabs de login/registro -->
                    <ul class="nav nav-tabs nav-fill mb-3" id="ratingAuthTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ratingLoginTab" data-bs-toggle="tab" 
                                    data-bs-target="#ratingLoginFormPanel" type="button" role="tab">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ratingRegisterTab" data-bs-toggle="tab" 
                                    data-bs-target="#ratingRegisterFormPanel" type="button" role="tab">
                                <i class="bi bi-person-plus me-1"></i> Registrarse
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ratingRecoverTab" data-bs-toggle="tab" 
                                    data-bs-target="#ratingRecoverPanel" type="button" role="tab">
                                <i class="bi bi-key me-1"></i> Recuperar
                            </button>
                        </li>
                    </ul>

                    <div class="rating-auth-tab-content" id="ratingAuthTabContent">
                        <!-- Login -->
                        <div class="tab-pane fade show active" id="ratingLoginFormPanel" role="tabpanel">
                            <form id="ratingLoginForm" onsubmit="return false;">
                                <div class="mb-3">
                                    <label class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" id="ratingLoginEmail" 
                                        placeholder="tu@email.com" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="ratingLoginPassword" 
                                        placeholder="••••••••" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" id="ratingLoginBtn">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar sesión
                                </button>
                                <div class="text-center mt-3">
                                    <a href="#" class="text-muted small text-decoration-none" id="btnOlvidastePassword">
                                        ¿Olvidaste tu contraseña?
                                    </a>
                                </div>
                                <div id="ratingLoginMessage" class="mt-2 small"></div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="ratingRegisterFormPanel" role="tabpanel">
                            <form id="ratingRegisterForm" onsubmit="return false;">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label">Nombres <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ratingRegisterNombres" 
                                            placeholder="Juan" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ratingRegisterApellidos" 
                                            placeholder="Pérez" required>
                                    </div>
                                </div>
                                
                                <div class="mb-2 mt-2">
                                    <label class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="ratingRegisterEmail" 
                                        placeholder="tu@email.com" required>
                                    <div id="ratingRegisterEmailError" class="small text-danger d-none">El correo ya está registrado</div>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="ratingRegisterPassword" 
                                        placeholder="Mínimo 6 caracteres" required minlength="6">
                                </div>

                                <!-- 🔥 DATOS ADICIONALES DEL CLIENTE -->
                                <div class="row g-2 mt-2">
                                    <div class="col-6">
                                        <label class="form-label">Tipo Documento</label>
                                        <select class="form-select form-select-sm" id="ratingRegisterTipoDoc">
                                            <option value="">Seleccione</option>
                                            <option value="DNI">DNI</option>
                                            <option value="CARNET DE EXTRANJERIA">Carnet de Extranjería</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">N° Documento</label>
                                        <input type="text" class="form-control form-control-sm" id="ratingRegisterNumeroDoc" 
                                            placeholder="12345678">
                                    </div>
                                </div>

                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <label class="form-label">Celular</label>
                                        <input type="text" class="form-control form-control-sm" id="ratingRegisterCelular" 
                                            placeholder="999999999">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Fecha Nacimiento</label>
                                        <input type="date" class="form-control form-control-sm" id="ratingRegisterFechaNac">
                                    </div>
                                </div>

                                <div class="row g-2 mt-1">
                                    <div class="col-12">
                                        <label class="form-label">Nacionalidad</label>
                                        <select class="form-select form-select-sm" id="ratingRegisterNacionalidad">
                                            <option value="">Seleccione</option>
                                            <option value="Perú" selected>Perú</option>
                                            <option value="Argentina">Argentina</option>
                                            <option value="Bolivia">Bolivia</option>
                                            <option value="Brasil">Brasil</option>
                                            <option value="Chile">Chile</option>
                                            <option value="Colombia">Colombia</option>
                                            <option value="Ecuador">Ecuador</option>
                                            <option value="España">España</option>
                                            <option value="Estados Unidos">Estados Unidos</option>
                                            <option value="México">México</option>
                                            <option value="Venezuela">Venezuela</option>
                                        </select>
                                    </div>
                                </div>

                                <hr class="my-2">
                                <p class="fw-bold small mb-1"><i class="bi bi-geo-alt"></i> Domicilio</p>

                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label">Departamento</label>
                                        <select class="form-select form-select-sm" id="ratingRegisterDepartamento">
                                            <option value="">Seleccione</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Provincia</label>
                                        <select class="form-select form-select-sm" id="ratingRegisterProvincia" disabled>
                                            <option value="">Seleccione</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Distrito</label>
                                        <select class="form-select form-select-sm" id="ratingRegisterDistrito" disabled>
                                            <option value="">Seleccione</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-2 mt-1">
                                    <div class="col-5">
                                        <label class="form-label">Calle</label>
                                        <input type="text" class="form-control form-control-sm" id="ratingRegisterCalle" 
                                            placeholder="Av. Ejemplo">
                                    </div>
                                    <div class="col-2">
                                        <label class="form-label">N°</label>
                                        <input type="text" class="form-control form-control-sm" id="ratingRegisterNumero" 
                                            placeholder="123">
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label">Dpto/Piso/Otros</label>
                                        <input type="text" class="form-control form-control-sm" id="ratingRegisterDirOtros" 
                                            placeholder="Piso 1, Dpto 101">
                                    </div>
                                </div>

                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <label class="form-label">Código Postal</label>
                                        <input type="text" class="form-control form-control-sm" id="ratingRegisterCodPostal" 
                                            placeholder="1484">
                                    </div>
                                </div>

                                <!-- 🔥 Campo oculto para forzar rol client -->
                                <input type="hidden" id="ratingRegisterRol" value="2">
                                
                                <button type="submit" class="btn btn-success w-100 mt-3" id="ratingRegisterBtn">
                                    <i class="bi bi-person-plus me-2"></i> Crear cuenta
                                </button>
                                <div id="ratingRegisterMessage" class="mt-2 small"></div>
                            </form>
                        </div>
                        <!-- ===== PANEL DE RECUPERACIÓN DE CONTRASEÑA ===== -->
                        <div class="tab-pane fade" id="ratingRecoverPanel" role="tabpanel">
                            <!-- PASO 1: Solicitar código -->
                            <div id="recoverStep1">
                                <p class="text-muted mb-3 small">
                                    Ingresa tu correo y te enviaremos un código de verificación de 6 dígitos.
                                </p>
                                <form id="recoverEmailForm" onsubmit="return false;">
                                    <div class="mb-3">
                                        <label class="form-label">Correo electrónico</label>
                                        <input type="email" class="form-control" id="recoverEmail" 
                                            placeholder="tu@email.com" required>
                                    </div>
                                    <button type="button" class="btn btn-primary w-100" id="recoverSendCodeBtn">
                                        <i class="bi bi-envelope-paper me-2"></i> Enviar código
                                    </button>
                                    <div id="recoverEmailMessage" class="mt-2 small"></div>
                                </form>
                                <div class="text-center mt-3">
                                    <a href="#" class="text-muted small text-decoration-none" id="recoverBackToLogin">
                                        <i class="bi bi-arrow-left me-1"></i> Volver a iniciar sesión
                                    </a>
                                </div>
                            </div>

                            <!-- PASO 2: Validar código -->
                            <div id="recoverStep2" class="d-none">
                                <p class="text-muted mb-3 small">
                                    Enviamos un código a <strong id="recoverEmailDisplay"></strong>.
                                    Expira en <span id="recoverCountdown">15:00</span>.
                                </p>
                                <form id="recoverCodeForm" onsubmit="return false;">
                                    <div class="mb-3">
                                        <label class="form-label">Código de verificación</label>
                                        <input type="text" class="form-control text-center fw-bold fs-4" 
                                            id="recoverCode" placeholder="000000" maxlength="6" 
                                            inputmode="numeric" autocomplete="one-time-code"
                                            style="letter-spacing: 8px;">
                                    </div>
                                    <button type="button" class="btn btn-primary w-100" id="recoverValidateCodeBtn">
                                        <i class="bi bi-shield-check me-2"></i> Validar código
                                    </button>
                                    <div id="recoverCodeMessage" class="mt-2 small"></div>
                                </form>
                                <div class="d-flex justify-content-between mt-3">
                                    <a href="#" class="text-muted small text-decoration-none" id="recoverBackToStep1">
                                        <i class="bi bi-arrow-left me-1"></i> Cambiar correo
                                    </a>
                                    <a href="#" class="text-muted small text-decoration-none" id="recoverResendCode">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Reenviar código
                                    </a>
                                </div>
                            </div>

                            <!-- PASO 3: Nueva contraseña -->
                            <div id="recoverStep3" class="d-none">
                                <p class="text-muted mb-3 small">
                                    Crea una nueva contraseña para tu cuenta.
                                </p>
                                <form id="recoverPasswordForm" onsubmit="return false;">
                                    <div class="mb-3">
                                        <label class="form-label">Nueva contraseña</label>
                                        <input type="password" class="form-control" id="recoverNewPassword" 
                                            placeholder="Mínimo 6 caracteres" minlength="6" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirmar contraseña</label>
                                        <input type="password" class="form-control" id="recoverConfirmPassword" 
                                            placeholder="Repite la contraseña" minlength="6" required>
                                    </div>
                                    <button type="button" class="btn btn-success w-100" id="recoverChangePasswordBtn">
                                        <i class="bi bi-check-circle me-2"></i> Cambiar contraseña
                                    </button>
                                    <div id="recoverPasswordMessage" class="mt-2 small"></div>
                                </form>
                            </div>

                            <!-- PASO 4: Éxito -->
                            <div id="recoverStep4" class="d-none text-center py-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">¡Contraseña actualizada!</h5>
                                <p class="text-muted small">Ya puedes iniciar sesión con tu nueva contraseña.</p>
                                <button type="button" class="btn btn-primary w-100" id="recoverGoToLogin">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ratingCommentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-star-fill text-warning me-2"></i>
                        Agregar comentario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">
                        Calificaste este producto con <strong id="commentRatingDisplay">0</strong> estrella(s).
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Comentario <span class="text-muted">(opcional)</span></label>
                        <textarea class="form-control" id="ratingCommentText" rows="3" 
                            placeholder="Escribe tu opinión sobre el producto..."></textarea>
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="ratingCommentSubmitBtn">
                        <i class="bi bi-check-circle me-2"></i> Enviar valoración
                    </button>
                    <div id="ratingCommentMessage" class="mt-2 small"></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>