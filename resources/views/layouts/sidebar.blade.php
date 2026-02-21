@php
    use App\Services\MenuService;
    $menus = MenuService::obtenerMenusPorUsuarioConVersion();
@endphp

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Botón menú hamburguesa para móvil -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Botón toggle (solo visible en desktop y tablet) -->
    <div class="toggle-btn" id="toggleSidebar">
        <i class="bi bi-chevron-right"></i>
    </div>

    <!-- Header con logo y empresa -->
    <div class="sidebar-header">
        <div class="logo-container">
            @php
                use App\Helpers\ConfiguracionHelper;
            @endphp
            
            <img src="{{ ConfiguracionHelper::getFavicon() }}" 
                alt="Logo"
                onerror="this.src='{{ asset('img/img-empresa-default.png') }}'">
            
            <div class="company-info">
                <div class="company-name">
                    <span class="abbreviation">{{ ConfiguracionHelper::getAbbreviation() }}</span>
                </div>
                <div class="company-role">{{ Auth::user()->rol->name ?? 'Usuario' }}</div>
            </div>
        </div>
    </div>

    <nav>
        <ul>
            <!-- Elemento estático Inicio (siempre visible) -->
            <li class="menu-item">
                <a href="{{ route('main') }}" class="d-flex align-items-center" data-tooltip="Inicio">
                    <i class="bi bi-house-door"></i>
                    <span>Inicio</span>
                </a>
            </li>

            <!-- Menús dinámicos -->
            @foreach($menus as $menu)
                @if(empty($menu['submenu']))
                    <!-- Menú sin submenú (redirección directa) -->
                    <li class="menu-item">
                        <a href="{{ url($menu['url']) }}" class="d-flex align-items-center" data-tooltip="{{ $menu['nombre'] }}">
                            <i class="{{ $menu['icono'] }}"></i>
                            <span>{{ $menu['nombre'] }}</span>
                        </a>
                    </li>
                @else
                    <!-- Menú con submenú -->
                    <li class="menu-item">
                        <button type="button" class="parent-menu" data-menu="menu-{{ $menu['id'] }}">
                            <div class="menu-content">
                                <i class="{{ $menu['icono'] }}"></i>
                                <span>{{ $menu['nombre'] }}</span>
                            </div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </button>
                        <ul class="submenu" id="submenu-menu-{{ $menu['id'] }}">
                            @foreach($menu['submenu'] as $submenu)
                                <li>
                                    <a href="{{ url($submenu['url']) }}" data-tooltip="{{ $submenu['nombre'] }}">
                                        <i class="bi bi-caret-right-fill"></i>
                                        <span>{{ $submenu['nombre'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach

            <!-- Elementos estáticos adicionales (siempre visibles) -->
            <li class="menu-item">
                <a href="{{ url('/notificaciones') }}" data-tooltip="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <span>Notificaciones</span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="{{ route('perfil.configuracion') }}" data-tooltip="Configuración">
                    <i class="bi bi-gear"></i>
                    <span>Configuración</span>
                </a>
            </li>

            <!-- Logout -->
            <li class="logout">
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" data-tooltip="Cerrar Sesión">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Cerrar Sesión</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </li>
        </ul>
    </nav>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Marcar el menú activo basado en la URL actual
    const currentUrl = window.location.pathname;
    
    document.querySelectorAll('.sidebar nav a').forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentUrl.startsWith(href) && href !== '#') {
            link.classList.add('active');
            
            // Expandir el menú padre si existe
            const parentLi = link.closest('li');
            if (parentLi && parentLi.closest('.submenu')) {
                const parentButton = parentLi.closest('.menu-item')?.querySelector('.parent-menu');
                if (parentButton) {
                    parentButton.classList.add('active');
                    parentLi.closest('.submenu').classList.add('show');
                }
            }
        }
    });
});
</script>
@endpush