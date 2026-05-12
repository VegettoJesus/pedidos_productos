<header class="header">
    <!-- Fila 1: Información de contacto desde EmpresaInformacion -->
    <div class="top-bar">
        <div class="top-bar__container">
            <div class="company-name">{{ e(\App\Helpers\ConfiguracionHelper::getCompanyName()) }}</div>
            <div class="contact-info">
                @php
                    $phone = \App\Helpers\ConfiguracionHelper::getPhone();
                    $email = \App\Helpers\ConfiguracionHelper::getEmail();
                @endphp
                @if($phone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="contact-item">
                    <i class="bi bi-phone contact-icon"></i>
                    <span>{{ e($phone) }}</span>
                </a>
                @endif
                @if($email)
                <a href="mailto:{{ $email }}" class="contact-item">
                    <i class="bi bi-envelope contact-icon"></i>
                    <span>{{ e($email) }}</span>
                </a>
                @endif
                <button class="login-btn" onclick="window.location.href='{{ route('login') }}'">
                    <i class="bi bi-person"></i>
                    <span>Iniciar Sesión</span>
                </button>
            </div>
        </div>
    </div>
        
    <!-- Fila 2: Logo y búsqueda -->
    <div class="middle-bar">
        <div class="middle-bar__container">
            <div class="logo-container">
                <img src="{{ \App\Helpers\ConfiguracionHelper::getFavicon() }}" alt="Logo" class="logo">
                <div class="logo-text">{{ e(\App\Helpers\ConfiguracionHelper::getAbbreviation()) }}</div>
            </div>
            
            <div class="search-container">
                <form action="{{ route('buscar') }}" method="GET" class="search-box">
                    <input type="text" name="q" placeholder="Buscar productos, categorías..." class="search-input" id="searchInput" autocomplete="off">
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
                <div id="searchSuggestions" class="search-suggestions"></div>
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
        
    <!-- Fila 3: Navegación (categorías dinámicas) -->
    <div class="bottom-bar">
        <div class="bottom-bar__container">
            <button class="menu-toggle" id="menuToggle">
                <i class="bi bi-list"></i>
            </button>
                
            <ul class="nav-menu desktop-only" id="navMenuDesktop">
                <li class="nav-item">
                    <a href="{{ route('tienda.home') }}" class="nav-link {{ request()->routeIs('tienda.home') ? 'active' : '' }}">
                        <i class="bi bi-house"></i>
                        <span>Inicio</span>
                    </a>
                </li>
                
                <!-- Categorías con subcategorías dinámicas -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-grid"></i>
                        <span>Categorías</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-custom">
                        <li class="dropdown-header-custom">
                            <i class="bi bi-tags-fill"></i> Categorías
                        </li>
                        @php
                            $categorias = \App\Models\Categoria::with('subcategorias')->get();
                        @endphp
                        @foreach($categorias as $categoria)
                            @if($categoria->subcategorias->count() > 0)
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-item-custom" href="#">
                                        <i class="bi {{ $categoria->icono ?? 'bi-folder' }}"></i>
                                        {{ e($categoria->nombre) }}
                                        <i class="bi bi-chevron-right ms-auto"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-submenu-menu">
                                        @foreach($categoria->subcategorias as $sub)
                                        <li>
                                            <a class="dropdown-item dropdown-item-custom" href="{{ route('productos.subcategoria', $sub->id) }}">
                                                <i class="bi {{ $sub->icono ?? 'bi-tag' }}"></i> {{ e($sub->nombre) }}
                                            </a>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li>
                                    <a class="dropdown-item dropdown-item-custom" href="{{ route('productos.categoria', $categoria->id) }}">
                                        <i class="bi {{ $categoria->icono ?? 'bi-folder' }}"></i> {{ e($categoria->nombre) }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                        <li><hr class="dropdown-divider-custom"></li>
                        <li class="dropdown-footer-custom">
                            <a href="{{ route('categorias.todas') }}">Ver todas las categorías <i class="bi bi-arrow-right"></i></a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="{{ route('ofertas') }}" class="nav-link">
                        <i class="bi bi-percent"></i>
                        <span>Ofertas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('nosotros') }}" class="nav-link">
                        <i class="bi bi-info-circle"></i>
                        <span>Nosotros</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('contacto') }}" class="nav-link">
                        <i class="bi bi-headset"></i>
                        <span>Contactanos</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Overlay y menú móvil dinámico (similar al escritorio) -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
    <div class="mobile-menu-container" id="mobileMenu">
        <div class="mobile-screen active" id="categoriesScreen">
            <div class="mobile-menu-header">
                <img src="{{ \App\Helpers\ConfiguracionHelper::getFavicon() }}" alt="Logo" class="mobile-menu-logo">
                <button class="mobile-close" id="mobileClose"><i class="bi bi-x-lg"></i></button>
            </div>
            <ul class="mobile-list">
                <li class="mobile-item"><a href="{{ route('tienda.home') }}" class="mobile-link"><i class="bi bi-house"></i><span>Inicio</span></a></li>
                <li class="mobile-item"><a href="{{ route('ofertas') }}" class="mobile-link"><i class="bi bi-percent"></i><span>Ofertas</span></a></li>
                <li class="mobile-item"><a href="{{ route('nosotros') }}" class="mobile-link"><i class="bi bi-info-circle"></i><span>Nosotros</span></a></li>
                <li class="mobile-item"><a href="{{ route('contacto') }}" class="mobile-link"><i class="bi bi-headset"></i><span>Contactanos</span></a></li>
                <li class="mobile-divider">Categorías</li>
                @foreach($categorias as $categoria)
                    @if($categoria->subcategorias->count() > 0)
                    <li class="mobile-item">
                        <button class="mobile-link has-children" data-target="subcatScreen{{ $categoria->id }}">
                            <i class="bi {{ $categoria->icono ?? 'bi-folder' }}"></i>
                            <span>{{ e($categoria->nombre) }}</span>
                            <i class="bi bi-chevron-right mobile-chev-rig"></i>
                        </button>
                    </li>
                    @else
                    <li class="mobile-item">
                        <a class="mobile-link" href="{{ route('productos.categoria', $categoria->id) }}">
                            <i class="bi {{ $categoria->icono ?? 'bi-folder' }}"></i>
                            <span>{{ e($categoria->nombre) }}</span>
                        </a>
                    </li>
                    @endif
                @endforeach
            </ul>
            <div class="mobile-footer">
                <button class="login-btn" onclick="window.location.href='{{ route('login') }}'">
                    <i class="bi bi-person"></i> Iniciar Sesión
                </button>
                <div class="contact-info">
                    @if($phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="contact-item">
                        <i class="bi bi-phone contact-icon"></i>
                        <span>{{ e($phone) }}</span>
                    </a>
                    @endif
                    @if($email)
                    <a href="mailto:{{ $email }}" class="contact-item">
                        <i class="bi bi-envelope contact-icon"></i>
                        <span>{{ e($email) }}</span>
                    </a>
                    @endif

                    <div class="login-btn-container">
                        @if(isset($authUser) && $authUser)
                            <div class="dropdown">
                                <button class="login-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <img src="{{ $authUser['foto'] }}" style="width:28px; height:28px; border-radius:50%; object-fit:cover; margin-right:8px;">
                                    <span>{{ $authUser['nombres'] }} {{ $authUser['apellidos'] }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" id="logoutBtnHeader">Cerrar sesión</a></li>
                                </ul>
                            </div>
                        @else
                            <button class="login-btn" id="openAuthModalBtn">
                                <i class="bi bi-person"></i> <span>Iniciar Sesión</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pantallas dinámicas de subcategorías (generadas con JS o aquí directamente) -->
        @foreach($categorias as $categoria)
            @if($categoria->subcategorias->count() > 0)
            <div class="mobile-screen" id="subcatScreen{{ $categoria->id }}">
                <div class="mobile-header">
                    <button class="back-btn" data-back="categoriesScreen"><i class="bi bi-arrow-left"></i></button>
                    <span>{{ e($categoria->nombre) }}</span>
                </div>
                <ul class="mobile-list">
                    @foreach($categoria->subcategorias as $sub)
                    <li class="mobile-item">
                        <a class="mobile-link" href="{{ route('productos.subcategoria', $sub->id) }}">
                            <i class="bi {{ $sub->icono ?? 'bi-tag' }}"></i>
                            <span>{{ e($sub->nombre) }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        @endforeach
    </div>
</header>