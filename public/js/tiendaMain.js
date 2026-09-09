const menuToggle = document.getElementById('menuToggle');
const mobileMenu = document.getElementById('mobileMenu'); // Cambiado
const mobileOverlay = document.getElementById('mobileOverlay');
const searchBtn = document.querySelector('.search-btn');
const searchInput = document.querySelector('.search-input');

const searchInputTienda = document.getElementById('searchInput');
const suggestionsDiv = document.getElementById('searchSuggestions');
let debounceTimer;
let loaderInicializado = false;

function mostrarLoaderInicial() {
    if (loaderInicializado) return;
    
    const cartLoader = window.cartLoader;
    if (cartLoader) {
        cartLoader.show({
            title: 'Cargando producto',
            text: 'Preparando los detalles...'
        });
    }
}

function ocultarLoader() {
    const cartLoader = window.cartLoader;
    if (cartLoader) {
        cartLoader.close();
        loaderInicializado = true;
    }
}

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

searchInputTienda.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const query = this.value.trim();
        if (query.length >= 2) {
            // Redirigir a la página de resultados
            window.location.href = `/buscar?q=${encodeURIComponent(query)}`;
        }
    }
});

// También en el botón de búsqueda (si existe)
const searchSubmitBtn = document.querySelector('.search-submit-btn');
if (searchSubmitBtn) {
    searchSubmitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const query = searchInputTienda.value.trim();
        if (query.length >= 2) {
            window.location.href = `/buscar?q=${encodeURIComponent(query)}`;
        }
    });
}

function renderSuggestions(data) {
    let html = '';
    
    if (data.productos?.length) {
        html += '<div class="suggestion-group"><strong>Productos</strong>';
        data.productos.forEach(p => {
            // Mostrar el precio formateado
            let precioDisplay = p.precio_formateado || '';
            if (!precioDisplay) {
                // Fallback si no viene formateado
                if (typeof p.precio === 'number') {
                    precioDisplay = 'S/.' + p.precio.toFixed(2);
                } else {
                    precioDisplay = p.precio || '';
                }
            }
            
            html += `<a href="${p.url}" class="suggestion-item">
                        <img src="${p.imagen}" width="30" height="30" style="object-fit:cover; border-radius:4px;"> 
                        ${escapeHtml(p.nombre)}
                        <span class="ms-auto text-primary fw-bold">${precioDisplay}</span>
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
// =============================================
// MANEJO DE VARIACIONES EN TARJETA
// =============================================
function initCardVariations() {
    document.querySelectorAll('.variation-options-card').forEach(container => {
        const productId = container.dataset.productId;
        
        const card = container.closest('.product-card-enhanced');
        if (!card) return;
        
        const slider = card.querySelector('.product-image-slider');
        if (!slider) return;
        
        let sliderContainer = slider.querySelector('.slider-container');
        const sliderImages = slider?.querySelector('.slider-images');
        const slides = sliderImages?.querySelectorAll('.slide');
        
        if (!sliderContainer || !sliderImages || !slides || slides.length === 0) return;
        
        const imagenesOriginales = [];
        slides.forEach(slide => {
            const img = slide.querySelector('img');
            if (img) {
                imagenesOriginales.push(img.src);
            }
        });
        
        const discountBadge = slider.querySelector('.discount-badge');
        const discountHtml = discountBadge ? discountBadge.outerHTML : '';
        
        function reconstruirSlider(imagenes) {
            if (!imagenes || imagenes.length === 0) {
                imagenes = imagenesOriginales;
            }
            
            const sliderActual = card.querySelector('.product-image-slider');
            if (!sliderActual) return;
            
            const containerActual = sliderActual.querySelector('.slider-container');
            if (!containerActual) return;
            
            const nuevoSliderContainer = document.createElement('div');
            nuevoSliderContainer.className = 'slider-container';
            
            const nuevoSliderImages = document.createElement('div');
            nuevoSliderImages.className = 'slider-images';
            
            imagenes.forEach((imgUrl, index) => {
                const slide = document.createElement('div');
                slide.className = `slide ${index === 0 ? 'active' : ''}`;
                slide.innerHTML = `<img src="${imgUrl}" alt="Producto" loading="lazy">`;
                nuevoSliderImages.appendChild(slide);
            });
            
            nuevoSliderContainer.appendChild(nuevoSliderImages);
            
            if (imagenes.length > 1) {
                const prevBtn = document.createElement('button');
                prevBtn.className = 'slider-prev';
                prevBtn.setAttribute('aria-label', 'Anterior');
                prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
                nuevoSliderContainer.appendChild(prevBtn);
                
                const nextBtn = document.createElement('button');
                nextBtn.className = 'slider-next';
                nextBtn.setAttribute('aria-label', 'Siguiente');
                nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
                nuevoSliderContainer.appendChild(nextBtn);
                
                const dotsContainer = document.createElement('div');
                dotsContainer.className = 'slider-dots';
                imagenes.forEach((_, index) => {
                    const dot = document.createElement('span');
                    dot.className = `dot ${index === 0 ? 'active' : ''}`;
                    dot.dataset.index = index;
                    dotsContainer.appendChild(dot);
                });
                nuevoSliderContainer.appendChild(dotsContainer);
            }
            
            try {
                sliderActual.replaceChild(nuevoSliderContainer, containerActual);
            } catch (e) {
                const sliderPadre = sliderActual.parentNode;
                if (sliderPadre) {
                    const nuevoSlider = sliderActual.cloneNode(false);
                    nuevoSlider.innerHTML = '';
                    nuevoSlider.appendChild(nuevoSliderContainer);
                    
                    if (discountHtml) {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = discountHtml;
                        const newBadge = tempDiv.firstElementChild;
                        if (newBadge) {
                            nuevoSlider.appendChild(newBadge);
                        }
                    }
                    
                    sliderPadre.replaceChild(nuevoSlider, sliderActual);
                }
            }
            
            setTimeout(() => {
                initSliderForCard(card);
            }, 100);
        }
        
        function restaurarSliderOriginal() {
            reconstruirSlider(imagenesOriginales);
        }
        
        container.querySelectorAll('.variation-option-card:not(.disabled)').forEach(option => {
            option.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                container.querySelectorAll('.variation-option-card').forEach(opt => {
                    opt.classList.remove('active');
                });
                
                this.classList.add('active');
                
                const variacionId = this.dataset.variacionId;
                
                if (!variacionId) {
                    restaurarSliderOriginal();
                    return;
                }
                
                let imagenes = [];
                
                const cardLocal = container.closest('.product-card-enhanced');
                if (cardLocal) {
                    const imagenesData = cardLocal.dataset.variacionImagenes;
                    if (imagenesData) {
                        try {
                            const data = JSON.parse(imagenesData);
                            if (data[variacionId]) {
                                imagenes = data[variacionId].map(img => {
                                    if (!img.startsWith('/') && !img.startsWith('http')) {
                                        return '/' + img;
                                    }
                                    return img;
                                });
                            }
                        } catch (e) {
                            console.error('Error parseando imagenes:', e);
                        }
                    }
                }
                
                if (imagenes.length === 0) {
                    try {
                        const response = await fetch(`/api/productos/${productId}/variacion/${variacionId}/imagenes`);
                        const data = await response.json();
                        if (data.success && data.imagenes) {
                            imagenes = data.imagenes.map(img => {
                                if (!img.startsWith('/') && !img.startsWith('http')) {
                                    return '/' + img;
                                }
                                return img;
                            });
                        }
                    } catch (error) {
                        console.error('Error al obtener imágenes de variación:', error);
                    }
                }
                
                if (imagenes.length === 0) {
                    restaurarSliderOriginal();
                    return;
                }
                
                reconstruirSlider(imagenes);
            });
        });

        setTimeout(() => initSliderForCard(card), 100);
    });
}

function initSliderForCard(card) {
    const slider = card.querySelector('.product-image-slider');
    if (!slider) return;
    
    const container = slider.querySelector('.slider-images');
    const slides = container?.querySelectorAll('.slide');
    const prevBtn = slider.querySelector('.slider-prev');
    const nextBtn = slider.querySelector('.slider-next');
    
    if (!slides || slides.length <= 1) {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
        return;
    }
    
    if (prevBtn) prevBtn.style.display = 'flex';
    if (nextBtn) nextBtn.style.display = 'flex';
    
    let dots = slider.querySelectorAll('.dot');
    let current = 0;
    const total = slides.length;
    
    function showSlide(index) {
        if (index < 0) index = total - 1;
        if (index >= total) index = 0;
        current = index;
        
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        
        dots = slider.querySelectorAll('.dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }
    
    function nextSlide() {
        showSlide((current + 1) % total);
    }
    
    function prevSlide() {
        showSlide((current - 1 + total) % total);
    }
    
    if (prevBtn) {
        const newPrev = prevBtn.cloneNode(true);
        prevBtn.parentNode.replaceChild(newPrev, prevBtn);
        newPrev.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            prevSlide();
        });
    }
    
    if (nextBtn) {
        const newNext = nextBtn.cloneNode(true);
        nextBtn.parentNode.replaceChild(newNext, nextBtn);
        newNext.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            nextSlide();
        });
    }
    
    dots = slider.querySelectorAll('.dot');
    dots.forEach((dot, idx) => {
        const newDot = dot.cloneNode(true);
        dot.parentNode.replaceChild(newDot, dot);
        newDot.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showSlide(idx);
        });
    });
    
    showSlide(0);
}

 // =============================================
// SISTEMA DE VALORACIÓN CON ESTRELLAS Y COMENTARIO
// =============================================

let pendingRatingData = null;

async function initProductRating() {
    // 🔥 Obtener authCheck UNA SOLA VEZ al inicio
    let authCheck = null;
    try {
        authCheck = await axios.get('/api/auth/check').catch(() => ({ data: { authenticated: false } }));
    } catch (e) {
        authCheck = { data: { authenticated: false } };
    }
    
    document.querySelectorAll('.stars-wrapper').forEach(async wrapper => {
        const stars = wrapper.querySelectorAll('.star');
        
        // OBTENER PRODUCT ID
        let productId = null;
        const card = wrapper.closest('.product-card-enhanced');
        if (card) {
            productId = card.dataset.productId;
        }
        if (!productId) {
            const container = document.querySelector('.product-detail-container');
            if (container) {
                productId = container.dataset.productoId;
            }
        }
        if (!productId) {
            productId = wrapper.dataset.productId;
        }
        if (!productId) {
            const parentWithId = wrapper.closest('[data-product-id]');
            if (parentWithId) {
                productId = parentWithId.dataset.productId;
            }
        }
        
        if (!productId) {
            console.warn('⚠️ No se encontró productId');
            return;
        }
        
        let currentRating = parseFloat(wrapper.dataset.rating) || 0;
        
        // 🔥 Usar authCheck que ya tenemos
        if (authCheck.data.authenticated && authCheck.data.user?.rol_id === 2) {
            await cargarValoracionUsuario(productId, wrapper);
        }
        
        // 🔥 Si el usuario ya calificó, usar su rating como currentRating
        const userRating = parseInt(wrapper.dataset.userRating) || 0;
        if (userRating > 0) {
            currentRating = userRating;
        }

        function setStars(rating) {
            stars.forEach((star, idx) => {
                if (idx < Math.floor(rating)) {
                    star.classList.add('bi-star-fill');
                    star.classList.remove('bi-star', 'bi-star-half');
                    star.style.color = '#ffc107';
                } else if (idx < rating && rating % 1 !== 0) {
                    star.classList.add('bi-star-half');
                    star.classList.remove('bi-star', 'bi-star-fill');
                    star.style.color = '#ffc107';
                } else {
                    star.classList.add('bi-star');
                    star.classList.remove('bi-star-fill', 'bi-star-half');
                    star.style.color = '#ddd';
                }
            });
        }
        setStars(currentRating);

        stars.forEach(star => {
            star.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const rating = parseInt(star.dataset.value);
                
                try {
                    // 🔥 Reutilizar authCheck o verificamos nuevamente
                    let currentAuth = authCheck;
                    if (!currentAuth) {
                        currentAuth = await axios.get('/api/auth/check').catch(() => ({ data: { authenticated: false } }));
                    }
                    
                    if (!currentAuth.data.authenticated) {
                        showRatingAuthModal(rating, productId, wrapper, stars, setStars);
                        return;
                    }
                    
                    if (currentAuth.data.user?.rol_id !== 2) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sin permiso',
                            text: 'Solo los clientes pueden calificar productos.',
                            confirmButtonColor: '#f39c12'
                        });
                        return;
                    }
                    
                    // 🔥 Mostrar modal de comentario
                    showRatingCommentModal(rating, productId, wrapper, stars, setStars);
                    
                } catch (error) {
                    if (error.response?.status === 401 || error.response?.status === 403) {
                        showRatingAuthModal(rating, productId, wrapper, stars, setStars);
                    } else {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.response?.data?.message || 'Ocurrió un error.',
                            confirmButtonColor: '#e74c3c'
                        });
                    }
                }
            });

            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.value);
                stars.forEach((s, idx) => {
                    if (idx < rating) {
                        s.classList.add('bi-star-fill');
                        s.classList.remove('bi-star', 'bi-star-half');
                        s.style.color = '#ffc107';
                    } else {
                        s.classList.remove('bi-star-fill', 'bi-star-half');
                        s.classList.add('bi-star');
                        s.style.color = '#ddd';
                    }
                });
            });

            star.addEventListener('mouseleave', function() {
                setStars(currentRating);
            });
        });
    });
}

// 🔥 Mostrar modal de comentario
function showRatingCommentModal(rating, productId, wrapper, stars, setStars) {
    if (!productId) {
        console.error(' productId es requerido para valorar');
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo identificar el producto.',
            confirmButtonColor: '#e74c3c'
        });
        return;
    }
    
    pendingRatingData = { rating, productId, wrapper, stars, setStars };
    document.getElementById('commentRatingDisplay').textContent = rating;
    document.getElementById('ratingCommentText').value = '';
    document.getElementById('ratingCommentMessage').innerHTML = '';
    
    const modal = new bootstrap.Modal(document.getElementById('ratingCommentModal'));
    modal.show();
}

// 🔥 Enviar valoración con comentario (ÚNICA FUNCIÓN)
async function enviarValoracion(rating, productId, comentario, wrapper, stars, setStars) {
    if (!productId) {
        console.error(' productId es requerido');
        return;
    }
    
    try {
        const response = await axios.post('/producto/valorar', {
            producto_id: parseInt(productId),
            puntuacion: rating,
            comentario: comentario || null
        });

        if (response.data.success) {
            const newRating = response.data.rating;
            const newCount = response.data.count;
            const userRating = response.data.user_rating || rating;
            
            // 🔥 Actualizar el rating del usuario en el wrapper
            wrapper.dataset.userRating = userRating;
            wrapper.dataset.rating = newRating;
            
            // 🔥 Mostrar estrellas según el rating del usuario (no el promedio)
            setStars(userRating);
            
            // Actualizar contador de reseñas
            const countSpan = wrapper.closest('.product-rating')?.querySelector('.rating-count');
            if (countSpan) countSpan.textContent = `(${newCount} reseñas)`;
            
            const detailRatingCount = document.querySelector('.product-rating .rating-count');
            if (detailRatingCount && detailRatingCount !== countSpan) {
                detailRatingCount.textContent = `(${newCount} reseñas)`;
            }
            
            const commentModal = bootstrap.Modal.getInstance(document.getElementById('ratingCommentModal'));
            if (commentModal) commentModal.hide();
            
            Swal.fire({
                icon: 'success',
                title: '¡Valoración guardada!',
                text: `Calificaste este producto con ${rating} estrella${rating > 1 ? 's' : ''}.`,
                timer: 2000,
                showConfirmButton: false
            });
            actualizarContadorValoraciones();

            return true;
        }
    } catch (error) {
        console.error('Error:', error);
        const msg = error.response?.data?.message || 'No se pudo guardar la valoración.';
        if (document.getElementById('ratingCommentMessage')) {
            document.getElementById('ratingCommentMessage').innerHTML = 
                `<span class="text-danger"> ${msg}</span>`;
        }
        return false;
    }
}

function actualizarContadorValoraciones() {
    if (!document.getElementById('valoracionesCount')) return;
    
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
        .catch(() => {});
}

// 🔥 Inicializar handlers de modales
function initRatingModals() {
    // Modal de comentario - Enviar
    document.getElementById('ratingCommentSubmitBtn')?.addEventListener('click', async function() {
        if (!pendingRatingData) return;
        
        const comentario = document.getElementById('ratingCommentText').value.trim();
        const { rating, productId, wrapper, stars, setStars } = pendingRatingData;
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando...';
        
        await enviarValoracion(rating, productId, comentario, wrapper, stars, setStars);
        
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-check-circle me-2"></i> Enviar valoración';
        pendingRatingData = null;
    });

    // Modal de comentario - Omitir
    document.getElementById('ratingCommentSkipBtn')?.addEventListener('click', async function() {
        if (!pendingRatingData) return;
        
        const { rating, productId, wrapper, stars, setStars } = pendingRatingData;
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando...';
        
        await enviarValoracion(rating, productId, null, wrapper, stars, setStars);
        
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-x-circle me-2"></i> Omitir comentario';
        pendingRatingData = null;
    });
}

// =============================================
// CARGAR VALORACIÓN DEL USUARIO AL INICIAR SESIÓN
// =============================================
async function cargarValoracionUsuario(productId, wrapper) {
    try {
        const response = await axios.get(`/api/producto/${productId}/valoracion-usuario`);
        
        if (response.data.success && response.data.user_rating > 0) {
            const stars = wrapper.querySelectorAll('.star');
            const userRating = response.data.user_rating;
            
            stars.forEach((star, idx) => {
                if (idx < userRating) {
                    star.classList.add('bi-star-fill');
                    star.classList.remove('bi-star', 'bi-star-half');
                    star.style.color = '#ffc107';
                } else {
                    star.classList.remove('bi-star-fill', 'bi-star-half');
                    star.classList.add('bi-star');
                    star.style.color = '#ddd';
                }
            });
            
            wrapper.dataset.userRating = userRating;
        }
    } catch (error) {
    }
}

// 🔥 Mostrar modal de autenticación
function showRatingAuthModal(selectedRating, productId, wrapper, stars, setStars) {
    const modal = new bootstrap.Modal(document.getElementById('ratingAuthModal'));
    modal.show();
    
    window._pendingRating = {
        rating: selectedRating,
        productId: productId,
        wrapper: wrapper,
        stars: stars,
        setStars: setStars
    };
}

// =============================================
// HANDLERS DEL MODAL DE AUTENTICACIÓN
// =============================================
function initRatingAuthModal() {
    // Cargar departamentos para el registro
    cargarDepartamentosRegistro();

    // Login desde el modal - SOLO CLIENTES
    document.getElementById('ratingLoginBtn')?.addEventListener('click', async function() {
        const email = document.getElementById('ratingLoginEmail').value.trim();
        const password = document.getElementById('ratingLoginPassword').value;
        const messageDiv = document.getElementById('ratingLoginMessage');
        
        if (!email || !password) {
            messageDiv.innerHTML = '<span class="text-danger">Por favor, completa todos los campos.</span>';
            return;
        }
        
        try {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Iniciando...';
            messageDiv.innerHTML = '';
            
            const response = await axios.post('/login-cliente', { email, password });
            
            if (response.data.success) {
                if (response.data.user.rol_id !== 2) {
                    messageDiv.innerHTML = '<span class="text-danger">Este usuario no tiene los permisos para acceder.</span>';
                    await axios.post('/logout-cliente');
                    return;
                }
                
                messageDiv.innerHTML = '<span class="text-success">Inicio de sesión exitoso</span>';
                updateAuthUI(response.data.user);
                
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ratingAuthModal'));
                    if (modal) modal.hide();
                    
                    if (window._pendingRating) {
                        const { rating, productId, wrapper, stars, setStars } = window._pendingRating;
                        showRatingCommentModal(rating, productId, wrapper, stars, setStars);
                        window._pendingRating = null;
                    }else{
                        location.reload();
                    }
                }, 800);
            }
            
        } catch (error) {
            messageDiv.innerHTML = `<span class="text-danger"> ${error.response?.data?.message || 'Error al iniciar sesión'}</span>`;
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i> Iniciar sesión';
        }
    });

    // Registro desde el modal
    document.getElementById('ratingRegisterBtn')?.addEventListener('click', async function() {
        const nombres = document.getElementById('ratingRegisterNombres').value.trim();
        const apellidos = document.getElementById('ratingRegisterApellidos').value.trim();
        const email = document.getElementById('ratingRegisterEmail').value.trim();
        const password = document.getElementById('ratingRegisterPassword').value;
        const messageDiv = document.getElementById('ratingRegisterMessage');
        
        if (!nombres || !apellidos || !email || !password) {
            messageDiv.innerHTML = '<span class="text-danger">Por favor, completa todos los campos obligatorios (*).</span>';
            return;
        }
        
        if (password.length < 6) {
            messageDiv.innerHTML = '<span class="text-danger">La contraseña debe tener al menos 6 caracteres.</span>';
            return;
        }
        
        try {
            const checkEmail = await axios.get(`/api/auth/check-email?email=${encodeURIComponent(email)}`);
            if (checkEmail.data.exists) {
                document.getElementById('ratingRegisterEmailError').classList.remove('d-none');
                messageDiv.innerHTML = '';
                return;
            }
        } catch (e) {}
        
        try {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Creando cuenta...';
            messageDiv.innerHTML = '';
            document.getElementById('ratingRegisterEmailError').classList.add('d-none');
            
            const data = {
                nombres: nombres,
                apellidos: apellidos,
                email: email,
                password: password,
                id_rol: 2,
                tipoDoc: document.getElementById('ratingRegisterTipoDoc')?.value,
                numeroDoc: document.getElementById('ratingRegisterNumeroDoc')?.value.trim(),
                celular: document.getElementById('ratingRegisterCelular')?.value.trim(),
                fecha_nacimiento: document.getElementById('ratingRegisterFechaNac')?.value,
                nacionalidad: document.getElementById('ratingRegisterNacionalidad')?.value,
                departamento_id: document.getElementById('ratingRegisterDepartamento')?.value,
                provincia_id: document.getElementById('ratingRegisterProvincia')?.value,
                distrito_id: document.getElementById('ratingRegisterDistrito')?.value,
                calle: document.getElementById('ratingRegisterCalle')?.value.trim(),
                numero: document.getElementById('ratingRegisterNumero')?.value.trim(),
                dir_otros: document.getElementById('ratingRegisterDirOtros')?.value.trim(),
                cod_postal: document.getElementById('ratingRegisterCodPostal')?.value.trim(),
            };
            
            const response = await axios.post('/registro-cliente', data);
            
            if (response.data.success) {
                messageDiv.innerHTML = '<span class="text-success">Cuenta creada exitosamente</span>';
                updateAuthUI(response.data.user);
                
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ratingAuthModal'));
                    if (modal) modal.hide();
                    
                    if (window._pendingRating) {
                        const { rating, productId, wrapper, stars, setStars } = window._pendingRating;
                        showRatingCommentModal(rating, productId, wrapper, stars, setStars);
                        window._pendingRating = null;
                    }else{
                        location.reload();
                    }
                }, 800);
            }
            
        } catch (error) {
            const msg = error.response?.data?.message || 'Error al crear la cuenta';
            messageDiv.innerHTML = `<span class="text-danger"> ${msg}</span>`;
            
            if (error.response?.status === 422 && msg.includes('email')) {
                document.getElementById('ratingRegisterEmailError').classList.remove('d-none');
            }
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-person-plus me-2"></i> Crear cuenta';
        }
    });

    // Enter en los formularios
    document.getElementById('ratingLoginForm')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('ratingLoginBtn').click();
        }
    });
    
    document.getElementById('ratingRegisterForm')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('ratingRegisterBtn').click();
        }
    });

    // Carga dinámica de provincias y distritos
    document.getElementById('ratingRegisterDepartamento')?.addEventListener('change', function() {
        const deptoId = this.value;
        const provinciaSelect = document.getElementById('ratingRegisterProvincia');
        const distritoSelect = document.getElementById('ratingRegisterDistrito');
        
        provinciaSelect.innerHTML = '<option value="">Seleccione</option>';
        distritoSelect.innerHTML = '<option value="">Seleccione</option>';
        distritoSelect.disabled = true;
        
        if (!deptoId) {
            provinciaSelect.disabled = true;
            return;
        }
        
        provinciaSelect.disabled = false;
        
        axios.get(`/get-provincias/${deptoId}`)
            .then(response => {
                response.data.forEach(p => {
                    provinciaSelect.innerHTML += `<option value="${p.id}">${p.nombre}</option>`;
                });
            })
            .catch(err => console.error('Error al cargar provincias:', err));
    });

    document.getElementById('ratingRegisterProvincia')?.addEventListener('change', function() {
        const provinciaId = this.value;
        const distritoSelect = document.getElementById('ratingRegisterDistrito');
        
        distritoSelect.innerHTML = '<option value="">Seleccione</option>';
        
        if (!provinciaId) {
            distritoSelect.disabled = true;
            return;
        }
        
        distritoSelect.disabled = false;
        
        axios.get(`/get-distritos/${provinciaId}`)
            .then(response => {
                response.data.forEach(d => {
                    distritoSelect.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
                });
            })
            .catch(err => console.error('Error al cargar distritos:', err));
    });
}

// Cargar departamentos para el registro
function cargarDepartamentosRegistro() {
    axios.get('/get-departamentos')
        .then(response => {
            const select = document.getElementById('ratingRegisterDepartamento');
            if (select) {
                response.data.forEach(d => {
                    select.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
                });
            }
        })
        .catch(err => console.error('Error al cargar departamentos:', err));
}

// =============================================
// MANEJO DEL MENÚ MI CUENTA
// =============================================
function openAuthModal(tab = 'login') {
    const modal = new bootstrap.Modal(document.getElementById('ratingAuthModal'));
    modal.show();
    
    if (tab === 'register') {
        const registerTab = document.getElementById('ratingRegisterTab');
        if (registerTab) {
            const tabInstance = new bootstrap.Tab(registerTab);
            tabInstance.show();
        }
    } else {
        const loginTab = document.getElementById('ratingLoginTab');
        if (loginTab) {
            const tabInstance = new bootstrap.Tab(loginTab);
            tabInstance.show();
        }
    }
}

// Usar la función genérica en todos los lugares
document.getElementById('openLoginModalBtn')?.addEventListener('click', function() {
    openAuthModal('login');
});

document.getElementById('openRegisterModalBtn')?.addEventListener('click', function() {
    openAuthModal('register');
});

document.getElementById('mobileOpenLoginModalBtn')?.addEventListener('click', function() {
    openAuthModal('login');
});

document.getElementById('mobileOpenRegisterModalBtn')?.addEventListener('click', function() {
    openAuthModal('register');
});

document.getElementById('openAuthFromValoraciones')?.addEventListener('click', function() {
    openAuthModal('login');
});

// Cerrar sesión
document.getElementById('logoutBtnHeader')?.addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({
        title: '¿Cerrar sesión?',
        text: '¿Estás seguro que deseas cerrar sesión?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
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
});
document.getElementById('mobileLogoutBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({
        title: '¿Cerrar sesión?',
        text: '¿Estás seguro que deseas cerrar sesión?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
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
});
// =============================================
// INICIALIZACIÓN
// =============================================
document.addEventListener('DOMContentLoaded', () => {
    initProductSliders();
    initProductRating();
    initRatingAuthModal();
    initRatingModals();
    initCardVariations();
});

// Exportar funciones
window.initProductRating = initProductRating;
window.enviarValoracion = enviarValoracion;
window.showRatingAuthModal = showRatingAuthModal;
window.updateAuthUI = updateAuthUI;

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

    // =============================================
    // ACTUALIZAR UI DESPUÉS DE LOGIN/REGISTRO
    // =============================================

    function updateAuthUI(user) {

        // Actualizar el botón de cuenta
        const accountName = document.getElementById('accountName');
        const accountMenu = document.getElementById('accountMenu');
        
        if (accountName) {
            accountName.textContent = user.nombres;
        }
        
        if (accountMenu) {
            // Reconstruir el menú para usuario logueado
            accountMenu.innerHTML = `
                <li class="dropdown-item-text text-muted small">
                    <i class="bi bi-envelope me-1"></i> ${user.email}
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="/configuracion">
                        <i class="bi bi-person-gear me-2"></i> Mi perfil
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-box-seam me-2"></i> Mis pedidos
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-geo-alt me-2"></i> Dirección de entrega
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button class="dropdown-item text-danger" id="logoutBtnHeader">
                        <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                    </button>
                </li>
            `;
            
            document.getElementById('logoutBtnHeader')?.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                Swal.fire({
                    title: '¿Cerrar sesión?',
                    text: '¿Estás seguro que deseas cerrar sesión?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#95a5a6',
                    confirmButtonText: 'Sí, cerrar sesión',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('/logout-cliente', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Error al cerrar sesión:', error);
                            location.reload();
                        });
                    }
                });
            });
        }
        
        // Actualizar el botón de login en el menú móvil si existe
        const mobileLoginBtn = document.querySelector('.mobile-footer .login-btn');
        if (mobileLoginBtn) {
            mobileLoginBtn.innerHTML = `
                <i class="bi bi-person-circle me-1"></i>
                ${user.nombres}
            `;
        }
    }
    