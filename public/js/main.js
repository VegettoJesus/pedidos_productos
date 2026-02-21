let mensajesGlobalLoader = "";
window.iconosGlobales = [];
const menu = document.getElementById('menu');
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('main');
sidebar.classList.add('menu-toggle');
main.classList.add('menu-toggle');
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');
    const toggleBtn = document.getElementById('toggleSidebar');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const overlay = document.getElementById('sidebarOverlay');

    let isExpanded = false;
    let isMobile = window.innerWidth <= 768;

    window.addEventListener('resize', function() {
        isMobile = window.innerWidth <= 768;

        if (!isMobile) {
            overlay.classList.remove('active');
            if (!sidebar.classList.contains('expanded')) {
                sidebar.style.transform = '';
                main.classList.remove('expanded');
            }
        } else {
            if (sidebar.classList.contains('expanded')) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
                sidebar.style.transform = 'translateX(-100%)';
            }
        }
    });

    function toggleSidebar() {
        isExpanded = !isExpanded;

        if (isMobile) {
            if (isExpanded) {
                sidebar.classList.add('expanded');
                sidebar.style.transform = 'translateX(0)';
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden'; 
                main.classList.remove('expanded');
            } else {
                sidebar.classList.remove('expanded');
                sidebar.style.transform = 'translateX(-100%)';
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            const menuIcon = mobileMenuBtn.querySelector('i');
            if (isExpanded) {
                menuIcon.classList.remove('bi-list');
                menuIcon.classList.add('bi-x-lg');
            } else {
                menuIcon.classList.remove('bi-x-lg');
                menuIcon.classList.add('bi-list');
            }
        } else {
            if (isExpanded) {
                sidebar.classList.add('expanded');
                main.classList.add('expanded');
                document.querySelectorAll('.parent-menu.active-submenu').forEach(button => {
                    const menuId = button.getAttribute('data-menu');
                    const submenu = document.getElementById(`submenu-${menuId}`);
                    if (submenu) {
                        submenu.classList.add('active');
                    }
                });
            } else {
                sidebar.classList.remove('expanded');
                main.classList.remove('expanded');

                document.querySelectorAll('.submenu').forEach(submenu => {
                    submenu.classList.remove('active');
                });
            }

            const toggleIcon = toggleBtn.querySelector('i');
            if (isExpanded) {
                toggleIcon.classList.remove('bi-chevron-right');
                toggleIcon.classList.add('bi-chevron-left');
            } else {
                toggleIcon.classList.remove('bi-chevron-left');
                toggleIcon.classList.add('bi-chevron-right');
            }
        }
    }

    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleSidebar();
    });

    mobileMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleSidebar();
    });

    overlay.addEventListener('click', function() {
        if (isMobile && sidebar.classList.contains('expanded')) {
            toggleSidebar();
        }
    });

    document.querySelectorAll('.parent-menu[data-menu]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();

            const menuId = this.getAttribute('data-menu');
            const submenu = document.getElementById(`submenu-${menuId}`);

            if (!submenu) return;

            if (isMobile) {
                if (sidebar.classList.contains('expanded')) {
                    toggleSubmenu(this, submenu);
                }
            } else {
                if (!sidebar.classList.contains('expanded')) {
                    toggleSidebar();

                    setTimeout(() => {
                        toggleSubmenu(this, submenu);
                    }, 300);
                } else {
                    toggleSubmenu(this, submenu);
                }
            }
        });
    });

    function toggleSubmenu(button, submenu) {
        document.querySelectorAll('.parent-menu.active-submenu').forEach(btn => {
            if (btn !== button) {
                const otherMenuId = btn.getAttribute('data-menu');
                const otherSubmenu = document.getElementById(`submenu-${otherMenuId}`);
                if (otherSubmenu) {
                    btn.classList.remove('active-submenu');
                    otherSubmenu.classList.remove('active');
                }
            }
        });

        button.classList.toggle('active-submenu');

        if (submenu.classList.contains('active')) {
            submenu.classList.remove('active');
        } else {
            submenu.classList.add('active');
        }
    }

    function setActiveMenu() {
        const currentPath = window.location.pathname;

        document.querySelectorAll('.parent-menu.active, a.active').forEach(el => {
            el.classList.remove('active');
        });

        document.querySelectorAll('a[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPath) {
                link.classList.add('active');
                const parentSubmenu = link.closest('.submenu');
                if (parentSubmenu) {
                    const parentButton = parentSubmenu.closest('.menu-item')?.querySelector('.parent-menu');
                    if (parentButton) {
                        parentButton.classList.add('active-submenu');
                        parentSubmenu.classList.add('active');
                    }
                }
            }
        });
    }

    setActiveMenu();

    document.addEventListener('touchmove', function(e) {
        if (isMobile && sidebar.classList.contains('expanded')) {
            e.preventDefault();
        }
    }, {
        passive: false
    });

    const logoutLink = document.querySelector('a[href="/logout"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
        });
    }
});


let statusInterval;

function showPreloader(title = 'Procesando...', action = 'default') {
    const statusMessagesMap = {
        cargar: [
            "Conectando con el servidor...",
            "Obteniendo registros...",
            "Procesando información...",
            "Generando tabla...",
            "Aplicando filtros...",
            "¡Datos cargados correctamente!"
        ],
        eliminar: [
            "Preparando eliminación...",
            "Validando permisos...",
            "Eliminando registros...",
            "Liberando espacio...",
            "Actualizando vista...",
            "¡Registros eliminados!"
        ],
        editar: [
            "Verificando cambios...",
            "Validando datos...",
            "Guardando información...",
            "Aplicando modificaciones...",
            "Sincronizando...",
            "¡Edición completada!"
        ],
        default: [
            "Iniciando proceso...",
            "Verificando información...",
            "Procesando datos...",
            "Realizando cambios...",
            "Finalizando...",
            "¡Proceso completado!"
        ],
        registrar: [
            "Verificando cambios...",
            "Validando datos...",
            "Guardando información...",
            "Aplicando registro...",
            "Sincronizando...",
            "Registro completado!"
        ],
    };

    const statusMessages = statusMessagesMap[action] || statusMessagesMap.default;

    Swal.fire({
        title: title,
        html: `
            <div class="preloader-container">
                <div class="progress-container">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                <div class="status-text" id="status-text">${statusMessages[0]}</div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            let currentStatus = 0;
            const statusElement = document.getElementById('status-text');
            const progressBar = document.getElementById('progress-bar');

            const totalSteps = statusMessages.length - 1;
            const intervalTime = 1000;
            const stepPercent = 100 / totalSteps;

            statusInterval = setInterval(() => {
                if (currentStatus < totalSteps) {
                    currentStatus++;
                    statusElement.textContent = statusMessages[currentStatus];
                    progressBar.style.width = (currentStatus * stepPercent) + "%";
                } else {
                    clearInterval(statusInterval);
                }
            }, intervalTime);

            Swal.getPopup().addEventListener('hidden', () => {
                clearInterval(statusInterval);
            });

            // Estilos
            if (!document.querySelector("#preloader-style")) {
                const style = document.createElement("style");
                style.id = "preloader-style";
                style.innerHTML = `
                .preloader-container { padding: 2rem; text-align: center; }
                .progress-container {
                    height: 14px; background-color: #f0f0f0;
                    border-radius: 10px; overflow: hidden; margin: 1.5rem 0;
                }
                .progress-bar {
                    height: 100%; width: 0%;
                    background: var(--sidebar-bg);
                    border-radius: 10px; transition: width 1s ease;
                }
                .status-text {
                    font-size: 0.95rem; color: #333;
                    margin-top: 1rem; font-weight: 500; min-height: 1.5rem;
                }
                .swal2-popup { border-radius: 15px; padding: 2rem; }
                .swal2-title { margin-bottom: 1rem !important; }
                `;
                document.head.appendChild(style);
            }
        }
    });

    return statusMessages;
}

function hidePreloader(statusMessages) {
    const statusElement = document.getElementById('status-text');
    const progressBar = document.getElementById('progress-bar');

    clearInterval(statusInterval);

    if (statusElement) {
        statusElement.textContent = statusMessages[statusMessages.length - 1];
    }
    if (progressBar) {
        progressBar.style.width = "100%";
    }

    if (Swal.isVisible()) {
        setTimeout(() => Swal.close(), 600);
    }
}

function generarGridIconos(iconoSeleccionado = '', filtro = '') {
    let iconos = window.iconosDisponibles || [];

    let iconoSeleccionadoNombre = '';
    if (iconoSeleccionado) {
        if (iconoSeleccionado.includes('bi-')) {
            iconoSeleccionadoNombre = iconoSeleccionado.split('bi-')[1];
        } else if (iconoSeleccionado.includes(' ')) {
            const parts = iconoSeleccionado.split(' ');
            iconoSeleccionadoNombre = parts.length > 1 ? parts[1].replace('bi-', '') : iconoSeleccionado;
        } else {
            iconoSeleccionadoNombre = iconoSeleccionado;
        }
    }

    if (filtro) {
        const termino = filtro.toLowerCase();
        iconos = iconos.filter(icono =>
            icono.nombre.toLowerCase().includes(termino) ||
            icono.icono.toLowerCase().includes(termino)
        );
    }

    if (iconos.length === 0) {
        return '<div class="text-center w-100 p-3">No se encontraron iconos con el término "' + filtro + '"</div>';
    }

    return iconos.map(icono => {
        const iconName = icono.icono;
        const isSelected = iconoSeleccionadoNombre === iconName ? 'selected' : '';
        const iconClass = icono.prefijo + ' ' + icono.prefijo + '-' + iconName;

        return `
            <button type="button" 
                    class="icon-btn ${isSelected}" 
                    data-icon="${iconName}"
                    title="${icono.nombre} (${iconName})">
                <i class="${iconClass}"></i>
            </button>
        `;
    }).join('');
}

function bindBotones(container, hiddenInput) {
    container.querySelectorAll('.icon-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            container.querySelectorAll('.icon-btn').forEach(b =>
                b.classList.remove('selected')
            );

            this.classList.add('selected');

            const iconName = this.getAttribute('data-icon');
            hiddenInput.value = iconName;
        });
    });
}

function bindBotones(contenedor, inputHidden) {
    const buttons = contenedor.querySelectorAll('.icon-btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            buttons.forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            inputHidden.value = btn.dataset.icon;
        });
    });
}