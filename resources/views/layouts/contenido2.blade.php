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