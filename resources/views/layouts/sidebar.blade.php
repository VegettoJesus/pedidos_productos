<div class="menu-btn sidebar-btn" id="sidebar-btn">
        <i class="bi bi-list"></i>
        <i class="bi bi-x"></i>
    </div>
    <div class="dark-mode-btn" id="dark-mode-btn">
        <i class="bi bi-moon"></i>
        <i class="bi bi-sun"></i>
    </div>
    <div class="sidebar" id="sidebar">
        <div class="header">
            <div class="menu-btn" id="menu-btn">
                <i class="bi bi-chevron-left"></i>
            </div>
            <div class="brand">
                <img class="brand-light" src="{{ asset('img/brand-light.webp') }}" alt="logo">
                <img class="brand-dark" src="{{ asset('img/brand-dark.webp') }}" alt="logo">
                <span>H.T.I</span>
            </div>
        </div>
        
        <div class="menu-container">
            <div class="search">
                <i class="bi bi-search"></i>
                <input type="search" placeholder="Search">
            </div>
            <ul class="menu">
                <li class="menu-item menu-item-static {{ request()->is('main') ? 'active' : '' }}">
                    <a href="{{ route('main') }}" class="menu-link">
                        <i class="bi bi-house"></i>
                        <span>Inicio</span>
                    </a>
                </li>
                @foreach ($menusDinamicos as $menu)
                    @php
                        $hasActiveSub = collect($menu['submenu'])->contains(fn($sub) => request()->is($sub['url']));
                    @endphp

                    @if (count($menu['submenu']) > 0)
                        <li class="menu-item menu-item-dropdown {{ $hasActiveSub ? 'sub-menu-toggle active' : '' }}">
                            <a href="javascript:void(0)" class="menu-link" role="button">
                                <i class="{{ $menu['icono'] }}"></i>
                                <span>{{ $menu['nombre'] }}</span>
                                <i class="bi bi-chevron-down"></i>
                            </a>
                            <ul class="sub-menu" style="{{ $hasActiveSub ? 'height:auto; padding:0.2rem 0;' : '' }}">
                                @foreach ($menu['submenu'] as $sub)
                                    <li>
                                        <a href="{{ url($sub['url']) }}" 
                                        class="sub-menu-link {{ request()->is($sub['url']) ? 'active' : '' }}">
                                            {{ $sub['nombre'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="menu-item menu-item-static {{ request()->is($menu['url']) ? 'active' : '' }}">
                            <a href="{{ url($menu['url']) }}" class="menu-link">
                                <i class="{{ $menu['icono'] }}"></i>
                                <span>{{ $menu['nombre'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach

            </ul>
        </div>
        <div class="footer">
            <ul class="menu">
                <li class="menu-item menu-item-static {{ request()->is('notificaciones') ? 'active' : '' }}">
                    <a href="{{ url('notificaciones') }}" class="menu-link">
                        <i class="bi bi-bell"></i>
                        <span>Notificaciones</span>
                    </a>
                </li>
                <li class="menu-item menu-item-static {{ request()->is('configuracion') ? 'active' : '' }}">
                    <a href="{{ url('configuracion') }}" class="menu-link">
                        <i class="bi bi-gear"></i>
                        <span>Configuración</span>
                    </a>
                </li>
            </ul>
            <div class="user">
                <div class="user-img">
                    @php
                        $imagen = Auth::user()->datos->imagen ?? null;
                    @endphp

                    @if ($imagen)
                        <img class="brand-light" src="{{ asset('perfil_usuario/' . $imagen) }}" alt="user">
                        <img class="brand-dark" src="{{ asset('perfil_usuario/' . $imagen) }}" alt="user">
                    @else
                        <img class="brand-light" src="{{ asset('img/user.png') }}" alt="user">
                        <img class="brand-dark" src="{{ asset('img/user2.png') }}" alt="user">
                    @endif
                </div>
                <div class="user-data">
                    <span class="name">{{ Auth::user()->nombres }} {{ Auth::user()->apellidos }}</span>
                    <span class="email">{{ Auth::user()->email }}</span>
                </div>
                <div class="user-icon">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Cerrar Sesión" style="all: unset; cursor: pointer;">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>