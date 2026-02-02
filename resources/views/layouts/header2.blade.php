<header class="header">
    <!-- Fila 1: Información de contacto -->
    <div class="top-bar">
        <div class="top-bar__container">
            <div class="company-name">High Technology Innovation</div>
            <div class="contact-info">
                <a href="tel:+1234567890" class="contact-item">
                    <i class="bi bi-phone contact-icon"></i>
                <span>+1 (234) 567-890</span>
                </a>
                <a href="mailto:info@hitech.com" class="contact-item">
                    <i class="bi bi-envelope contact-icon"></i>
                    <span>info@hitech.com</span>
                </a>
                <button class="login-btn">
                    <i class="bi bi-person"></i>
                    <span>Iniciar Sesión</span>
                </button>
            </div>
        </div>
    </div>
        
    <!-- Fila 2: Logo, búsqueda y acciones -->
    <div class="middle-bar">
        <div class="middle-bar__container">
            <div class="logo-container">
                <img src="{{ asset('img/brand-light.webp') }}" alt="High Technology Innovation" class="logo">
                <div class="logo-text">High Technology Innovation</div>
            </div>
            
            <div class="search-container">
                <div class="search-box">
                    <input type="text" placeholder="Buscar productos, categorías..." class="search-input">
                    <button class="search-btn">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
                
            <div class="action-icons">
                <button class="action-btn" title="Favoritos">
                    <i class="bi bi-heart"></i>
                    <span class="badge">3</span>
                </button>
                <button class="action-btn" title="Carrito de compras">
                    <i class="bi bi-cart3"></i>
                    <span class="badge">5</span>
                </button>
            </div>
        </div>
    </div>
        
    <!-- Fila 3: Navegación principal -->
<div class="bottom-bar">
    <div class="bottom-bar__container">
        <!-- Botón para móvil/tablet -->
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>
            
        <!-- MENÚ DE ESCRITORIO (solo visible en desktop) -->
        <ul class="nav-menu desktop-only" id="navMenuDesktop">
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="bi bi-house"></i>
                    <span>Inicio</span>
                </a>
            </li>
                
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false">
                    <i class="bi bi-grid"></i>
                    <span>Categorías</span>
                </a>

                <!-- Dropdown de desktop -->
                <ul class="dropdown-menu dropdown-menu-custom">
                    <!-- Encabezado -->
                    <li class="dropdown-header-custom">
                        <i class="bi bi-tags-fill"></i>
                        Categorías
                    </li>
                    
                    <!-- Item con badge -->
                    <li class="dropdown-submenu">
                        <a class="dropdown-item dropdown-item-custom" href="#">
                            <i class="bi bi-laptop"></i>
                            Computadoras & Laptops
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>

                        <!-- SUBCATEGORÍAS -->
                        <ul class="dropdown-menu dropdown-submenu-menu">
                            <li>
                                <a class="dropdown-item dropdown-item-custom" href="#">
                                    <i class="bi bi-laptop"></i> Laptops
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item dropdown-item-custom" href="#">
                                    <i class="bi bi-pc-display"></i> PCs de Escritorio
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item dropdown-item-custom" href="#">
                                    <i class="bi bi-controller"></i> PCs Gamer
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Item normal -->
                    <li>
                        <a class="dropdown-item dropdown-item-custom" href="#">
                            <i class="bi bi-phone"></i>
                            Smartphones & Tablets
                        </a>
                    </li>
                    
                    <!-- Item normal -->
                    <li>
                        <a class="dropdown-item dropdown-item-custom" href="#">
                            <i class="bi bi-cpu"></i>
                            Componentes Electrónicos
                        </a>
                    </li>
                    
                    <!-- Separador -->
                    <li><hr class="dropdown-divider-custom"></li>
                    
                    <!-- Item con badge -->
                    <li>
                        <a class="dropdown-item dropdown-item-custom" href="#">
                            <i class="bi bi-smartwatch"></i>
                            Gadgets Inteligentes
                            <span class="badge-custom">Nuevo</span>
                        </a>
                    </li>
                    
                    <!-- Item normal -->
                    <li>
                        <a class="dropdown-item dropdown-item-custom" href="#">
                            <i class="bi bi-headphones"></i>
                            Accesorios Tecnológicos
                        </a>
                    </li>
                    
                    <!-- Item normal -->
                    <li>
                        <a class="dropdown-item dropdown-item-custom" href="#">
                            <i class="bi bi-motherboard"></i>
                            Hardware & Periféricos
                        </a>
                    </li>
                    
                    <!-- Footer del dropdown -->
                    <li class="dropdown-footer-custom">
                        <a href="#">
                            Ver todas las categorías
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </li>
                </ul>
            </li>
                
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="bi bi-percent"></i>
                    <span>Ofertas</span>
                </a>
            </li>
                
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="bi bi-info-circle"></i>
                    <span>Nosotros</span>
                </a>
            </li>
                
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="bi bi-headset"></i>
                    <span>Contactanos</span>
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- Overlay para móvil -->
<div class="mobile-overlay" id="mobileOverlay"></div>
<!-- Menú móvil/tablet completo (fuera de bottom-bar) -->
<div class="mobile-menu-container" id="mobileMenu">
    <div class="mobile-screen active" id="categoriesScreen">
        <div class="mobile-menu-header">
            <img src="{{ asset('img/brand-light.webp') }}" alt="Logo" class="mobile-menu-logo">
            <button class="mobile-close" id="mobileClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <ul class="mobile-list">
            <li class="mobile-item">
                <a href="#" class="mobile-link">
                    <i class="bi bi-house"></i>
                    <span>Inicio</span>
                </a>
            </li>
            
            <li class="mobile-item">
                <a href="#" class="mobile-link">
                    <i class="bi bi-percent"></i>
                    <span>Ofertas</span>
                </a>
            </li>
                
            <li class="mobile-item">
                <a href="#" class="mobile-link">
                    <i class="bi bi-info-circle"></i>
                    <span>Nosotros</span>
                </a>
            </li>
                
            <li class="mobile-item">
                <a href="#" class="mobile-link">
                    <i class="bi bi-headset"></i>
                    <span>Contactanos</span>
                </a>
            </li>
            
            <li class="mobile-divider">Categorías</li>
            
            <li class="mobile-item">
                <button class="mobile-link has-children" data-target="computersScreen">
                    <i class="bi bi-laptop"></i>
                    <span>Computadoras & Laptops</span>
                    <i class="bi bi-chevron-right mobile-chev-rig"></i>
                </button>
            </li>

            <li class="mobile-item">
                <a class="mobile-link" href="#">
                    <i class="bi bi-phone"></i>
                    <span>Smartphones & Tablets</span>
                </a>
            </li>

            <li class="mobile-item">
                <a class="mobile-link" href="#">
                    <i class="bi bi-cpu"></i>
                    <span>Componentes</span>
                </a>
            </li>
            
            <li class="mobile-item">
                <a class="mobile-link" href="#">
                    <i class="bi bi-smartwatch"></i>
                    <span>Gadgets Inteligentes</span>
                    <span class="mobile-badge">Nuevo</span>
                </a>
            </li>
        </ul>
        
        <!-- Footer del menú móvil -->
        <div class="mobile-footer">
            <button class="login-btn">
                <i class="bi bi-person"></i>
                Iniciar Sesión
            </button>
            
            <div class="contact-info">
                <a href="tel:+123456789" class="contact-item">
                    <i class="bi bi-telephone"></i>
                    +1 234 567 89
                </a>
                <a href="mailto:info@tienda.com" class="contact-item">
                    <i class="bi bi-envelope"></i>
                    info@tienda.com
                </a>
            </div>
        </div>
    </div>

    <!-- PANTALLA SUBCATEGORÍAS -->
    <div class="mobile-screen" id="computersScreen">
        <div class="mobile-header">
            <button class="back-btn" data-back="categoriesScreen">
                <i class="bi bi-arrow-left"></i>
            </button>
            <span>Computadoras</span>
        </div>

        <ul class="mobile-list">
            <li class="mobile-item">
                <a class="mobile-link" href="#">
                    <i class="bi bi-laptop"></i>
                    <span>Laptops</span>
                </a>
            </li>
            <li class="mobile-item">
                <a class="mobile-link" href="#">
                    <i class="bi bi-pc-display"></i>
                    <span>PC Escritorio</span>
                </a>
            </li>
            <li class="mobile-item">
                <a class="mobile-link" href="#">
                    <i class="bi bi-controller"></i>
                    <span>PC Gamer</span>
                </a>
            </li>
        </ul>
    </div>
</div>
</header>