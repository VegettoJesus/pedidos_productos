function addcl() { 
	let parent = this.parentNode.parentNode;
	parent.classList.add("focus");
}

function remcl() {
	let parent = this.parentNode.parentNode;
	if (this.value == "") {
		parent.classList.remove("focus");
	}
}

function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon = document.getElementById('eyeIcon');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !isPassword);
        icon.classList.toggle('bi-eye-slash', isPassword);
    }

    document.getElementById('loginForm')?.addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        btn.classList.add('loading');
        btn.querySelector('.btn-text').textContent = 'Ingresando...';
        btn.disabled = true;
    });

const inputs = document.querySelectorAll(".input");

inputs.forEach(input => {
	input.addEventListener("focus", addcl);
	input.addEventListener("blur", remcl);
});
// Toggle sidebar en desktop/tablet (minimizar/expandir)
const menuBtn = document.getElementById('menu-btn');
const sidebar = document.getElementById('sidebar');

if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('minimize');
        
        // Rotar el icono
        const icon = menuBtn.querySelector('i');
        if (sidebar.classList.contains('minimize')) {
            icon.style.transform = 'rotate(180deg)';
        } else {
            icon.style.transform = 'rotate(0deg)';
        }
    });
}

// Toggle sidebar en móvil (mostrar/ocultar)
const sidebarBtn = document.getElementById('sidebar-btn');
const sidebarOverlay = document.getElementById('sidebar-overlay');
const body = document.body;
const darkModeBtn = document.getElementById('dark-mode-btn');

if (sidebarBtn && sidebarOverlay) {
    // Abrir/cerrar sidebar con el botón
    sidebarBtn.addEventListener('click', () => {
        body.classList.toggle('sidebar-hidden');
        
        // Desplazar al principio del sidebar cuando se abre
        if (body.classList.contains('sidebar-hidden')) {
            sidebar.scrollTop = 0;
        }
    });
    
    // Cerrar sidebar al hacer clic en el overlay
    sidebarOverlay.addEventListener('click', () => {
        body.classList.remove('sidebar-hidden');
    });
}

// Función para detectar si estamos en móvil
function isMobile() {
    return window.innerWidth <= 767;
}

// Función para detectar si estamos en tablet
function isTablet() {
    return window.innerWidth >= 768 && window.innerWidth <= 1023;
}

// Función para detectar si estamos en desktop
function isDesktop() {
    return window.innerWidth >= 1024;
}

// Cerrar sidebar en móvil al hacer clic fuera
document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    const sidebarBtn = document.getElementById('sidebar-btn');
    
    if (isMobile() && sidebar && sidebarBtn && body.classList.contains('sidebar-hidden')) {
        // Si se hace clic fuera del sidebar y del botón
        if (!sidebar.contains(e.target) && !sidebarBtn.contains(e.target)) {
            body.classList.remove('sidebar-hidden');
        }
    }
});

// Redimensionar ventana
window.addEventListener('resize', () => {
    // Si cambiamos de móvil a tablet/desktop, asegurar que sidebar esté visible
    if (!isMobile()) {
        body.classList.remove('sidebar-hidden');
        sidebar?.classList.remove('minimize');
        
        // Restaurar icono del chevron
        const menuBtnIcon = document.querySelector('#menu-btn i');
        if (menuBtnIcon) {
            menuBtnIcon.style.transform = 'rotate(0deg)';
        }
    } else {
        // Si cambiamos a móvil, asegurar que sidebar esté oculto
        body.classList.remove('sidebar-hidden');
        sidebar?.classList.remove('minimize');
    }
    
    // Ajustar sidebar width dinámicamente para tablet
    if (isTablet()) {
        document.documentElement.style.setProperty('--sidebar-width', '8rem');
    } else if (isDesktop()) {
        document.documentElement.style.setProperty('--sidebar-width', '7rem');
    }
});

// Cerrar sidebar al presionar ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && body.classList.contains('sidebar-hidden') && isMobile()) {
        body.classList.remove('sidebar-hidden');
    }
});

// Prevenir que el body haga scroll cuando el sidebar está abierto (solo en móvil)
let scrollPosition = 0;

function preventBodyScroll() {
    if (isMobile() && body.classList.contains('sidebar-hidden')) {
        // Guardar posición actual
        scrollPosition = window.pageYOffset;
        
        // Bloquear scroll del body
        body.style.overflow = 'hidden';
        body.style.position = 'fixed';
        body.style.top = `-${scrollPosition}px`;
        body.style.width = '100%';
    } else {
        // Restaurar scroll del body
        body.style.overflow = '';
        body.style.position = '';
        body.style.top = '';
        body.style.width = '';
        
        // Restaurar posición de scroll
        window.scrollTo(0, scrollPosition);
    }
}

// Observar cambios en la clase sidebar-hidden
const observer = new MutationObserver(preventBodyScroll);
observer.observe(body, { attributes: true, attributeFilter: ['class'] });

// Inicializar según el tamaño actual
if (isTablet()) {
    document.documentElement.style.setProperty('--sidebar-width', '8rem');
}

// =============================================
// SISTEMA DE RECUPERACIÓN DE CONTRASEÑA
// =============================================
(function() {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let modalInstance = null;
    let countdownInterval = null;
    let resendInterval = null;
    let emailActual = '';
    let codigoActual = '';
    let segundosRestantes = 0;

    // Inicializar modal
    function initModal() {
        const modalEl = document.getElementById('modalRecuperarPassword');
        if (!modalEl) return;
        modalInstance = new bootstrap.Modal(modalEl);

        // Reset al cerrar
        modalEl.addEventListener('hidden.bs.modal', () => {
            clearInterval(countdownInterval);
            clearInterval(resendInterval);
            irAPaso(1);
            document.getElementById('emailRecuperacion').value = '';
            document.getElementById('codigoInput').value = '';
            document.getElementById('nuevaPassword').value = '';
            document.getElementById('confirmarPassword').value = '';
            document.getElementById('mensajePaso1').innerHTML = '';
            document.getElementById('mensajePaso2').innerHTML = '';
            document.getElementById('mensajePaso3').innerHTML = '';
        });
    }

    // Cambiar de paso
    function irAPaso(paso) {
        document.querySelectorAll('.modal-paso').forEach(el => el.style.display = 'none');
        document.getElementById('paso' + paso).style.display = 'block';
    }

    // Abrir modal desde el link
    document.querySelector('.forgot-link')?.addEventListener('click', function(e) {
        e.preventDefault();
        // Pre-llenar con el email si ya está escrito
        const emailLogin = document.getElementById('emailInput').value.trim();
        if (emailLogin) {
            document.getElementById('emailRecuperacion').value = emailLogin;
        }
        modalInstance.show();
    });

    // ============================================
    // PASO 1: Enviar código
    // ============================================
    document.getElementById('btnEnviarCodigo')?.addEventListener('click', async function() {
        const btn = this;
        const email = document.getElementById('emailRecuperacion').value.trim();
        const msg = document.getElementById('mensajePaso1');

        if (!email) {
            msg.innerHTML = '<span class="text-danger">Ingresa tu correo electrónico.</span>';
            return;
        }

        btn.disabled = true;
        btn.querySelector('.btn-text').textContent = 'Enviando...';
        msg.innerHTML = '';

        try {
            const res = await fetch('/password/solicitar-codigo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email })
            });

            const data = await res.json();

            if (!data.success) {
                msg.innerHTML = `<span class="text-danger">${data.message}</span>`;
                return;
            }

            emailActual = email;
            document.getElementById('emailMostrado').textContent = email;
            segundosRestantes = data.segundos_restantes;
            
            irAPaso(2);
            iniciarContador();
            iniciarReenvio();

            if (data.reutilizado) {
                // Avisar que ya estaba activo
                Swal.fire({
                    icon: 'info',
                    title: 'Código activo',
                    text: 'Ya tenías un código activo. Revisa tu correo anterior.',
                    timer: 2500,
                    showConfirmButton: false,
                    confirmButtonColor: '#F4AB28'
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Código enviado',
                    text: 'Revisa tu bandeja de entrada.',
                    timer: 2000,
                    showConfirmButton: false,
                    confirmButtonColor: '#F4AB28'
                });
            }

        } catch (err) {
            console.error(err);
            msg.innerHTML = '<span class="text-danger">Error de conexión. Intenta nuevamente.</span>';
        } finally {
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = 'Enviar código';
        }
    });

    // ============================================
    // CONTADOR
    // ============================================
    function iniciarContador() {
        clearInterval(countdownInterval);
        actualizarContador();
        countdownInterval = setInterval(actualizarContador, 1000);
    }

    function actualizarContador() {
        if (segundosRestantes <= 0) {
            clearInterval(countdownInterval);
            document.getElementById('contadorTexto').textContent = '00:00';
            document.getElementById('contadorContainer').classList.add('expirado');
            document.getElementById('contadorContainer').innerHTML = 
                '<i class="bi bi-exclamation-triangle-fill me-2"></i><span>El código ha expirado. Solicita uno nuevo.</span>';
            return;
        }

        segundosRestantes--;
        const min = Math.floor(segundosRestantes / 60);
        const seg = segundosRestantes % 60;
        document.getElementById('contadorTexto').textContent = 
            `${String(min).padStart(2, '0')}:${String(seg).padStart(2, '0')}`;
    }

    // ============================================
    // REENVÍO (bloqueado 60s)
    // ============================================
    function iniciarReenvio() {
        clearInterval(resendInterval);
        let segundos = 60;
        const btn = document.getElementById('btnReenviarCodigo');
        const txt = document.getElementById('textoReenviar');
        
        btn.disabled = true;
        txt.textContent = `Reenviar en ${segundos}s`;

        resendInterval = setInterval(() => {
            segundos--;
            if (segundos <= 0) {
                clearInterval(resendInterval);
                btn.disabled = false;
                txt.textContent = 'Reenviar código';
            } else {
                txt.textContent = `Reenviar en ${segundos}s`;
            }
        }, 1000);
    }

    document.getElementById('btnReenviarCodigo')?.addEventListener('click', async function() {
        const msg = document.getElementById('mensajePaso2');
        msg.innerHTML = '';

        try {
            const res = await fetch('/password/solicitar-codigo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email: emailActual })
            });

            const data = await res.json();

            if (!data.success) {
                msg.innerHTML = `<span class="text-danger">${data.message}</span>`;
                return;
            }

            segundosRestantes = data.segundos_restantes;
            iniciarContador();
            iniciarReenvio();

            // Restaurar contenedor del contador
            const cont = document.getElementById('contadorContainer');
            cont.classList.remove('expirado');
            cont.innerHTML = '<i class="bi bi-clock-history me-2"></i><span>El código expira en: </span><strong id="contadorTexto">15:00</strong>';

            Swal.fire({
                icon: 'success',
                title: 'Código reenviado',
                timer: 1500,
                showConfirmButton: false
            });
        } catch (err) {
            msg.innerHTML = '<span class="text-danger">Error al reenviar.</span>';
        }
    });

    // ============================================
    // PASO 2: Validar código
    // ============================================
    document.getElementById('codigoInput')?.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    document.getElementById('btnValidarCodigo')?.addEventListener('click', async function() {
        const btn = this;
        const codigo = document.getElementById('codigoInput').value.trim();
        const msg = document.getElementById('mensajePaso2');

        if (codigo.length !== 6) {
            msg.innerHTML = '<span class="text-danger">Ingresa el código completo de 6 dígitos.</span>';
            return;
        }

        btn.disabled = true;
        btn.querySelector('.btn-text').textContent = 'Validando...';
        msg.innerHTML = '';

        try {
            const res = await fetch('/password/validar-codigo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email: emailActual, codigo })
            });

            const data = await res.json();

            if (!data.success) {
                msg.innerHTML = `<span class="text-danger">${data.message}</span>`;
                
                if (data.expirado) {
                    clearInterval(countdownInterval);
                    const cont = document.getElementById('contadorContainer');
                    cont.classList.add('expirado');
                    cont.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i><span>El código ha expirado.</span>';
                }
                return;
            }

            codigoActual = codigo;
            clearInterval(countdownInterval);
            irAPaso(3);

        } catch (err) {
            msg.innerHTML = '<span class="text-danger">Error de conexión.</span>';
        } finally {
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = 'Validar código';
        }
    });

    // Volver al paso 1
    document.getElementById('btnVolverPaso1')?.addEventListener('click', () => {
        clearInterval(countdownInterval);
        clearInterval(resendInterval);
        irAPaso(1);
    });

    // ============================================
    // PASO 3: Cambiar contraseña
    // ============================================
    const pass1 = document.getElementById('nuevaPassword');
    const pass2 = document.getElementById('confirmarPassword');

    function validarRequisitos() {
        const val1 = pass1.value;
        const val2 = pass2.value;
        const reqLength = document.getElementById('req-length');
        const reqMatch = document.getElementById('req-match');

        // Longitud
        if (val1.length >= 6) {
            reqLength.classList.add('ok');
            reqLength.querySelector('i').className = 'bi bi-check-circle-fill';
        } else {
            reqLength.classList.remove('ok');
            reqLength.querySelector('i').className = 'bi bi-circle';
        }

        // Coincidencia
        if (val1 && val2 && val1 === val2) {
            reqMatch.classList.add('ok');
            reqMatch.querySelector('i').className = 'bi bi-check-circle-fill';
        } else {
            reqMatch.classList.remove('ok');
            reqMatch.querySelector('i').className = 'bi bi-circle';
        }
    }

    pass1?.addEventListener('input', validarRequisitos);
    pass2?.addEventListener('input', validarRequisitos);

    document.getElementById('btnCambiarPassword')?.addEventListener('click', async function() {
        const btn = this;
        const val1 = pass1.value;
        const val2 = pass2.value;
        const msg = document.getElementById('mensajePaso3');

        if (val1.length < 6) {
            msg.innerHTML = '<span class="text-danger">La contraseña debe tener al menos 6 caracteres.</span>';
            return;
        }
        if (val1 !== val2) {
            msg.innerHTML = '<span class="text-danger">Las contraseñas no coinciden.</span>';
            return;
        }

        btn.disabled = true;
        btn.querySelector('.btn-text').textContent = 'Cambiando...';
        msg.innerHTML = '';

        try {
            const res = await fetch('/password/cambiar-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    email: emailActual,
                    codigo: codigoActual,
                    password: val1,
                    password_confirmation: val2
                })
            });

            const data = await res.json();

            if (!data.success) {
                msg.innerHTML = `<span class="text-danger">${data.message}</span>`;
                return;
            }

            // Cerrar modal y mostrar éxito
            modalInstance.hide();
            
            Swal.fire({
                icon: 'success',
                title: '¡Contraseña actualizada!',
                text: 'Ya puedes iniciar sesión con tu nueva contraseña.',
                confirmButtonColor: '#F4AB28',
                confirmButtonText: 'Ir al login'
            }).then(() => {
                document.getElementById('emailInput').value = emailActual;
                document.getElementById('passwordInput').focus();
            });

        } catch (err) {
            msg.innerHTML = '<span class="text-danger">Error de conexión.</span>';
        } finally {
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = 'Cambiar contraseña';
        }
    });

    // ============================================
    // TOGGLE PASSWORD EN MODAL
    // ============================================
    window.togglePasswordModal = function(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !isPassword);
        icon.classList.toggle('bi-eye-slash', isPassword);
    };

    // ============================================
    // AUTO-VERIFICAR CÓDIGO ACTIVO AL REABRIR
    // ============================================
    // Escuchar cuando se abre el modal y el usuario ya escribió email
    document.getElementById('emailRecuperacion')?.addEventListener('blur', async function() {
        const email = this.value.trim();
        if (!email) return;

        try {
            const res = await fetch('/password/verificar-codigo-activo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email })
            });

            const data = await res.json();

            if (data.success && data.activo) {
                emailActual = email;
                segundosRestantes = data.segundos_restantes;
                document.getElementById('emailMostrado').textContent = email;
                irAPaso(2);
                iniciarContador();
                iniciarReenvio();

                Swal.fire({
                    icon: 'info',
                    title: 'Código activo',
                    text: `Tienes un código activo. Expira en ${Math.ceil(data.segundos_restantes / 60)} min.`,
                    timer: 2500,
                    showConfirmButton: false
                });
            }
        } catch (err) {
            // Ignorar silenciosamente
        }
    });

    // Inicializar
    document.addEventListener('DOMContentLoaded', initModal);

})();