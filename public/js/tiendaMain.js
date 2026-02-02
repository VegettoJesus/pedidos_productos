const menuToggle = document.getElementById('menuToggle');
const mobileMenu = document.getElementById('mobileMenu'); // Cambiado
const mobileOverlay = document.getElementById('mobileOverlay');
const searchBtn = document.querySelector('.search-btn');
const searchInput = document.querySelector('.search-input');

// Alternar menú móvil (solo para móvil/tablet)
menuToggle.addEventListener('click', () => {
    mobileMenu.classList.add('active');
    mobileOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
});

// Cerrar menú al hacer clic en overlay
mobileOverlay.addEventListener('click', () => {
    mobileMenu.classList.remove('active');
    mobileOverlay.classList.remove('active');
    document.body.style.overflow = '';
});

// Cerrar menú con botón X
const mobileClose = document.getElementById('mobileClose');
mobileClose.addEventListener('click', () => {
    mobileMenu.classList.remove('active');
    mobileOverlay.classList.remove('active');
    document.body.style.overflow = '';
});

// Navegación entre pantallas móviles
document.querySelectorAll('.mobile-link.has-children').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const target = btn.dataset.target;
        document.getElementById('categoriesScreen').classList.remove('active');
        document.getElementById(target).classList.add('active');
    });
});

// Botones de retroceso
document.querySelectorAll('.back-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const backTarget = btn.dataset.back;
        document.querySelectorAll('.mobile-screen').forEach(screen => {
            screen.classList.remove('active');
        });
        document.getElementById(backTarget).classList.add('active');
    });
});

// Búsqueda (compartido)
searchBtn.addEventListener('click', () => {
    if (searchInput.value.trim()) {
        console.log('Buscando:', searchInput.value);
    }
});

searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && searchInput.value.trim()) {
        console.log('Buscando (Enter):', searchInput.value);
    }
});

// Login (compartido)
document.querySelector('.login-btn').addEventListener('click', () => {
    console.log('Redirigiendo a login');
});

// Sticky header (compartido)
const bottomBar = document.querySelector('.bottom-bar');
const header = document.querySelector('.header');

window.addEventListener('scroll', () => {
    const stickyOffset = header.offsetTop + header.offsetHeight;
    if (window.innerWidth > 991 && window.scrollY > stickyOffset) {
        bottomBar.classList.add('sticky');
        document.body.classList.add('has-sticky-nav');
    } else {
        bottomBar.classList.remove('sticky');
        document.body.classList.remove('has-sticky-nav');
    }
});

// Cerrar menú al presionar ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
        mobileMenu.classList.remove('active');
        mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Ajustar sticky en resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 991) {
        // Cerrar menú móvil si se cambia a desktop
        mobileMenu.classList.remove('active');
        mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Resetear pantallas móviles
        document.querySelectorAll('.mobile-screen').forEach(screen => {
            screen.classList.remove('active');
        });
        document.getElementById('categoriesScreen').classList.add('active');
    }
});