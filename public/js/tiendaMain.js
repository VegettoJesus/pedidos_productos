const menuToggle = document.getElementById('menuToggle');
const mobileMenu = document.getElementById('mobileMenu'); // Cambiado
const mobileOverlay = document.getElementById('mobileOverlay');
const searchBtn = document.querySelector('.search-btn');
const searchInput = document.querySelector('.search-input');

const searchInputTienda = document.getElementById('searchInput');
const suggestionsDiv = document.getElementById('searchSuggestions');
let debounceTimer;

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

searchInputTienda.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const query = this.value.trim();
    if (query.length < 2) {
        suggestionsDiv.innerHTML = '';
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    debounceTimer = setTimeout(() => {
        fetch(`/buscar/sugerencias?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                renderSuggestions(data);
            })
            .catch(err => console.error(err));
    }, 300);
});

function renderSuggestions(data) {
    let html = '';
    
    if (data.productos?.length) {
        html += '<div class="suggestion-group"><strong>Productos</strong>';
        data.productos.forEach(p => {
            html += `<a href="${p.url}" class="suggestion-item">
                        <img src="${p.imagen}" width="30" height="30"> 
                        ${escapeHtml(p.nombre)} - $${p.precio}
                    </a>`;
        });
        html += '</div>';
    }
    
    if (data.categorias?.length) {
        html += '<div class="suggestion-group"><strong>Categorías</strong>';
        data.categorias.forEach(c => {
            html += `<a href="${c.url}" class="suggestion-item">
                        <i class="bi ${c.icono || 'bi-folder'}"></i> ${escapeHtml(c.nombre)}
                    </a>`;
        });
        html += '</div>';
    }
    
    if (data.subcategorias?.length) {
        html += '<div class="suggestion-group"><strong>Subcategorías</strong>';
        data.subcategorias.forEach(s => {
            html += `<a href="${s.url}" class="suggestion-item">
                        <i class="bi ${s.icono || 'bi-tag'}"></i> ${escapeHtml(s.nombre)} (${escapeHtml(s.categoria_nombre)})
                    </a>`;
        });
        html += '</div>';
    }
    
    if (html === '') {
        html = '<div class="suggestion-empty">No se encontraron resultados</div>';
    }
    
    suggestionsDiv.innerHTML = html;
    suggestionsDiv.style.display = 'block';
}

// Ocultar sugerencias al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!searchInputTienda.contains(e.target) && !suggestionsDiv.contains(e.target)) {
        suggestionsDiv.style.display = 'none';
    }
});

function escapeHtml(str) {
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}
function initProductSliders() {
        document.querySelectorAll('.product-image-slider').forEach(slider => {
            const container = slider.querySelector('.slider-images');
            const slides = slider.querySelectorAll('.slide');
            const prevBtn = slider.querySelector('.slider-prev');
            const nextBtn = slider.querySelector('.slider-next');
            const dots = slider.querySelectorAll('.dot');
            if (!slides.length || slides.length <= 1) return;

            let current = 0;
            const total = slides.length;

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
            }

            function next() {
                current = (current + 1) % total;
                showSlide(current);
            }
            function prev() {
                current = (current - 1 + total) % total;
                showSlide(current);
            }

            if (prevBtn) prevBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); prev(); });
            if (nextBtn) nextBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); next(); });
            dots.forEach((dot, idx) => {
                dot.addEventListener('click', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    current = idx;
                    showSlide(current);
                });
            });
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'logoutBtnHeader') {
            e.preventDefault();
            fetch('/logout-cliente', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => {
                location.reload();
            });
        }
    });

    // Inicializar sistema de valoración
    function initProductRating() {
        document.querySelectorAll('.stars-wrapper').forEach(wrapper => {
            const stars = wrapper.querySelectorAll('.star');
            const productId = wrapper.closest('.product-card-enhanced').dataset.productId;
            let currentRating = parseFloat(wrapper.dataset.rating) || 0;

            function setStars(rating) {
                stars.forEach((star, idx) => {
                    if (idx < Math.floor(rating)) {
                        star.classList.add('bi-star-fill');
                        star.classList.remove('bi-star', 'bi-star-half');
                    } else if (idx < rating && rating % 1 !== 0) {
                        star.classList.add('bi-star-half');
                        star.classList.remove('bi-star', 'bi-star-fill');
                    } else {
                        star.classList.add('bi-star');
                        star.classList.remove('bi-star-fill', 'bi-star-half');
                    }
                });
            }
            setStars(currentRating);

            stars.forEach(star => {
                star.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const value = parseInt(star.dataset.value);
                    
                    fetch('/producto/valorar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            producto_id: productId,
                            puntuacion: value
                        })
                    })
                    .then(response => {
                        if (response.status === 401 || response.status === 403) {
                            // Mostrar modal de autenticación
                            const authModal = new bootstrap.Modal(document.getElementById('authModal'));
                            authModal.show();
                            throw new Error('Unauthenticated');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            wrapper.dataset.rating = data.rating;
                            setStars(data.rating);
                            const countSpan = wrapper.closest('.product-rating').querySelector('.rating-count');
                            if (countSpan) countSpan.textContent = `(${data.count} reseñas)`;
                            Swal.fire({
                                icon: 'success',
                                title: '¡Gracias por tu valoración!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    })
                    .catch(err => {
                        if (err.message !== 'Unauthenticated') {
                            Swal.fire({
                                icon: 'error',
                                title: 'No se pudo guardar la valoración',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                background: '#f8d7da',
                                color: '#721c24'
                            });
                        }
                    });
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initProductSliders();
        initProductRating();
    });
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.querySelector('.product-carousel');
        const prevBtn = document.querySelector('.carousel-prev');
        const nextBtn = document.querySelector('.carousel-next');

        if (carousel && prevBtn && nextBtn) {
            // Obtener el ancho de un slide dinámicamente
            let slideWidth = 300; // valor por defecto
            const slide = document.querySelector('.carousel-slide');
            if (slide) {
                slideWidth = slide.offsetWidth + 24; // 24 es el gap
            }
            
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                carousel.scrollBy({ left: -slideWidth, behavior: 'smooth' });
            });

            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                carousel.scrollBy({ left: slideWidth, behavior: 'smooth' });
            });
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        const accordionItems = document.querySelectorAll('.filter-accordion-item');
        
        accordionItems.forEach(item => {
            const key = item.dataset.accordion;
            const isOpen = localStorage.getItem('accordion_' + key) === 'true';
            if (isOpen) {
                item.classList.add('open');
            }
        });
        
        accordionItems.forEach(item => {
            const header = item.querySelector('.filter-accordion-header');
            header.addEventListener('click', (e) => {
                e.preventDefault();
                item.classList.toggle('open');
                const key = item.dataset.accordion;
                localStorage.setItem('accordion_' + key, item.classList.contains('open'));
            });
        });
    });