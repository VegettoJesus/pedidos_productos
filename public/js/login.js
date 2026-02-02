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
	const passwordInput = document.getElementById("passwordInput");
	const eyeIcon = document.getElementById("eyeIcon");

	if (passwordInput.type === "password") {
		passwordInput.type = "text";
		eyeIcon.classList.remove("fa-eye");
		eyeIcon.classList.add("fa-eye-slash");
	} else {
		passwordInput.type = "password";
		eyeIcon.classList.remove("fa-eye-slash");
		eyeIcon.classList.add("fa-eye");
	}
}

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