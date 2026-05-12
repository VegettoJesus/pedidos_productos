<!DOCTYPE html>
<html lang="es">
<meta name="csrf-token" content="{{ csrf_token() }}">
<head>
    @include('layouts.head2')
</head>
<body>
    @include('layouts.header2')
    @include('layouts.sidebar2')

    <main class="content">
        @isset($contenido2)
            @include($contenido2)
        @endisset
    </main>
    @include('layouts.footer2')
    <!-- Modal de Autenticación (Registro/Login) -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#loginPanel" type="button" role="tab">Iniciar Sesión</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#registerPanel" type="button" role="tab">Crear Cuenta</button>
                        </li>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="tab-content">
                        <!-- Login -->
                        <div class="tab-pane fade show active" id="loginPanel">
                            <form id="loginFormModal">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="loginEmail" required>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="loginPassword" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                            </form>
                        </div>
                        <!-- Registro -->
                        <div class="tab-pane fade" id="registerPanel">
                            <form id="registerFormModal">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="regNombres" class="form-label">Nombres</label>
                                        <input type="text" class="form-control" id="regNombres" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="regApellidos" class="form-label">Apellidos</label>
                                        <input type="text" class="form-control" id="regApellidos" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="regEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="regEmail" required>
                                </div>
                                <div class="mb-3">
                                    <label for="regPassword" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="regPassword" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Registrarse</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Variable global con datos del usuario autenticado (inyectada desde el backend)
        window.authUser = @json(auth()->check() ? auth()->user() : null);
        
        // Función para actualizar el botón del header (reemplaza el actual)
        function updateAuthUI(user) {
            const loginBtnContainer = document.querySelector('.login-btn-container');
            if (!loginBtnContainer) return;
            
            if (user) {
                const nombreCompleto = `${user.nombres} ${user.apellidos}`;
                loginBtnContainer.innerHTML = `
                    <div class="dropdown">
                        <button class="login-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="${user.foto || '/img/user.png'}" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; margin-right: 8px;">
                            <span>${nombreCompleto}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" id="logoutBtnHeader">Cerrar sesión</a></li>
                        </ul>
                    </div>
                `;
                // Evento logout
                document.getElementById('logoutBtnHeader')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    fetch('/logout-cliente', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } })
                        .then(() => location.reload());
                });
            } else {
                loginBtnContainer.innerHTML = `
                    <button class="login-btn" id="openAuthModalBtn">
                        <i class="bi bi-person"></i> <span>Iniciar Sesión</span>
                    </button>
                `;
                document.getElementById('openAuthModalBtn')?.addEventListener('click', () => {
                    new bootstrap.Modal(document.getElementById('authModal')).show();
                });
            }
        }
        
        // Inicializar UI
        updateAuthUI(window.authUser);
        
        // Manejar login vía modal
        document.getElementById('loginFormModal')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            fetch('/login-cliente', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ email, password })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.authUser = data.user;
                    updateAuthUI(data.user);
                    bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
                    location.reload(); // recargar para actualizar valoraciones
                } else {
                    Swal.fire('Error', data.message || 'Credenciales incorrectas', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Error de conexión', 'error'));
        });
        
        // Manejar registro
        document.getElementById('registerFormModal')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                nombres: document.getElementById('regNombres').value,
                apellidos: document.getElementById('regApellidos').value,
                email: document.getElementById('regEmail').value,
                password: document.getElementById('regPassword').value
            };
            fetch('/registro-cliente', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.authUser = data.user;
                    updateAuthUI(data.user);
                    bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
                    location.reload();
                } else {
                    Swal.fire('Error', data.message || 'Error en el registro', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Error de conexión', 'error'));
        });
        
        // Mostrar modal cuando se intente valorar sin estar autenticado
        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', (e) => {
                if (!window.authUser) {
                    e.preventDefault();
                    e.stopPropagation();
                    new bootstrap.Modal(document.getElementById('authModal')).show();
                }
            });
        });
    </script>
</body>
</html>