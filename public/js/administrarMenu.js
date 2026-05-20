$(document).ready(function () {
    let currentMenuOrder = {};
    let isDragging = false;
    let rolFiltroActual = '';

    function inicializarSortable() {
        $('.sortable-container').each(function() {
            const container = $(this);
            const padreId = container.data('padre-id') || 0;
            
            new Sortable(container[0], {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: '.grip-handle',
                
                onStart: function() {
                    isDragging = true;
                    $('#btnGuardarOrden').fadeIn();
                },
                
                onEnd: function() {
                    isDragging = false;
                    actualizarNumeracion(container);
                    actualizarOrdenLocal(container, padreId);
                }
            });
        });
    }
    
    // Actualizar numeración visual
    function actualizarNumeracion(container) {
        container.find('tr').each(function(index) {
            const ordenCell = $(this).find('.orden-cell');
            const ordenBadge = $(this).find('.orden-badge');
            const newOrder = index + 1;
            
            if (ordenCell.length) {
                ordenCell.text(newOrder);
            }
            if (ordenBadge.length) {
                ordenBadge.text(newOrder);
            }
        });
    }
    
    // Actualizar orden en memoria
    function actualizarOrdenLocal(container, padreId) {
        const menus = [];
        container.find('tr').each(function(index) {
            const menuId = $(this).data('menu-id');
            if (menuId) {
                menus.push({
                    id: menuId,
                    orden: index + 1
                });
            }
        });
        
        if (!currentMenuOrder[padreId]) {
            currentMenuOrder[padreId] = [];
        }
        currentMenuOrder[padreId] = menus;
    }
    
    // Guardar orden en servidor
    function guardarOrden(padreId = 0) {
        const menus = currentMenuOrder[padreId];
        if (!menus || menus.length === 0) {
            Swal.fire('Info', 'No hay cambios para guardar', 'info');
            return;
        }
        
        mensajesGlobalLoader = showPreloader("Guardando orden...", "guardar");
        
        $.post('administrarMenu', {
            opcion: 'ActualizarOrden',
            menus: menus,
            id_padre: padreId,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function(res) {
            hidePreloader(mensajesGlobalLoader);
            
            if (res.respuesta === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: res.mensaje,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    $('#btnGuardarOrden').fadeOut();
                    // Recargar solo la pestaña actual con el filtro actual
                    const activeTab = $('.tab-pane.active');
                    const activeTabId = activeTab.attr('id');
                    if (activeTabId === 'main-tab-pane') {
                        cargarMenusPrincipales(rolFiltroActual);
                    } else {
                        const menuId = activeTabId.replace('pane-', '');
                        cargarSubmenus(menuId, rolFiltroActual);
                    }
                });
            } else {
                Swal.fire('Error', res.mensaje, 'error');
            }
        }, 'json');
    }
    
    // Cargar menús principales
    function cargarMenusPrincipales(idRol = '') {
        const container = $('#mainMenusBody');
        container.html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
        
        $.post('administrarMenu', {
            opcion: 'Listar',
            id_padre: 0,
            id_rol: idRol,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function(res) {
            if (res.respuesta === 'ok') {
                renderizarMenus(container, res.menus, res.permisosVista, true);
                inicializarSortable();
                
                // Actualizar información del filtro
                actualizarInfoFiltro('main', idRol, res.menus.length);
            } else {
                container.html('<tr><td colspan="8" class="text-center py-4 text-danger">Error al cargar los menús</td></tr>');
            }
        }, 'json');
    }

    function actualizarInfoFiltro(tabId, idRol, cantidad) {
        const rolSeleccionado = $('#filtro_permiso option:selected').text();
        let infoText = '';
        
        if (idRol) {
            infoText = `<span class="badge bg-primary">Filtrado por: ${rolSeleccionado}</span> `;
            infoText += `<span class="badge bg-info">${cantidad} menús con permiso</span>`;
        } else {
            infoText = '<span class="badge bg-secondary">Mostrando todos los menús</span>';
        }
        
        if (tabId === 'main') {
            $('#filtroInfoMain').html(infoText);
        } else {
            $(`#filtroInfo${tabId}`).html(infoText);
        }
    }
    
    // Cargar submenús de un padre específico
    function cargarSubmenus(padreId, idRol = '') {
        const container = $(`#submenus-${padreId}`);
        if (!container.length) return;
        
        container.html('<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
        
        $.post('administrarMenu', {
            opcion: 'Listar',
            id_padre: padreId,
            id_rol: idRol,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function(res) {
            if (res.respuesta === 'ok') {
                renderizarMenus(container, res.menus, res.permisosVista, false);
                inicializarSortable();
                
                // Actualizar información del filtro
                actualizarInfoFiltro(padreId, idRol, res.menus.length);
            } else {
                container.html('<tr><td colspan="7" class="text-center py-4 text-danger">Error al cargar los submenús</td></tr>');
            }
        }, 'json');
    }
    
    // Renderizar menús en tabla
    function renderizarMenus(container, menus, permisosVista, isMain = false) {
        if (!menus || menus.length === 0) {
            const rolSeleccionado = $('#filtro_permiso option:selected').text();
            let mensaje = 'No hay menús disponibles';
            
            if (rolFiltroActual) {
                mensaje = `No hay menús con permiso para el rol: ${rolSeleccionado}`;
            }
            
            container.html(`
                <tr>
                    <td colspan="${isMain ? '8' : '7'}" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5>${mensaje}</h5>
                        ${rolFiltroActual ? `
                        <p class="text-muted">
                            Este rol no tiene acceso a ${isMain ? 'menús principales' : 'submenús'} en esta categoría
                        </p>` : `
                        <p class="text-muted">${isMain ? 'Crea un nuevo menú principal para comenzar' : 'Agrega submenús a este menú principal'}</p>`}
                    </td>
                </tr>
            `);
            return;
        }
        
        let html = '';
        menus.forEach((menu, index) => {
            const orden = index + 1;
            const icono = menu.icono || 'bi-question-circle';
            
            html += `
                <tr data-menu-id="${menu.id}">
                    <td class="text-center">
                        <span class="orden-badge">${orden}</span>
                    </td>
                    <td class="text-center">
                        <i class="bi bi-grip-vertical grip-handle"></i>
                    </td>
                    <td class="text-center fw-bold">${menu.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${isMain ? `<i class="${icono} menu-icon-preview me-2"></i>` : ''}
                            <span class="fw-semibold">${menu.nombre}</span>
                        </div>
                    </td>
                    ${isMain ? `<td>${icono}</td>` : ''}
                    <td><small class="text-muted">${menu.url || '#'}</small></td>
                    <td class="text-center orden-cell">${orden}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            ${permisosVista?.configurar ? `
                            <button class="btn btn-primary btn-action btn-action-sm btn-permisos" 
                                    data-id="${menu.id}" title="Permisos">
                                <i class="bi bi-gear"></i>
                            </button>` : ''}
                            
                            ${permisosVista?.editar ? `
                            <button class="btn btn-warning btn-action btn-action-sm btn-editar" 
                                    data-id="${menu.id}" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>` : ''}
                            
                            ${permisosVista?.configurar ? `
                            <button class="btn btn-info btn-action btn-action-sm btn-url" 
                                    data-id="${menu.id}" title="URL">
                                <i class="bi bi-link"></i>
                            </button>` : ''}
                            
                            ${permisosVista?.eliminar ? `
                            <button class="btn btn-danger btn-action btn-action-sm btn-eliminar" 
                                    data-id="${menu.id}" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>` : ''}
                            
                            ${isMain && permisosVista?.configurar ? `
                            <button class="btn btn-success btn-action btn-action-sm btn-ver-submenus" 
                                    data-id="${menu.id}" title="Ver Submenús">
                                <i class="bi bi-folder2-open"></i>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });
        
        container.html(html);
        bindMenuActions(permisosVista);
    }
    
    // Vincular eventos a los botones de acción
    function bindMenuActions(permisosVista) {
        // Botón de permisos
        $('.btn-permisos').off('click').on('click', function() {
            const idMenu = $(this).data('id');
            const menuNombre = $(this).closest('tr').find('.fw-semibold').text();
            abrirModalPermisos(idMenu, menuNombre);
        });
        
        // Botón de editar
        $('.btn-editar').off('click').on('click', function() {
            const idMenu = $(this).data('id');
            editarMenu(idMenu);
        });
        
        // Botón de URL
        $('.btn-url').off('click').on('click', function() {
            const idMenu = $(this).data('id');
            actualizarURL(idMenu);
        });
        
        // Botón de eliminar
        $('.btn-eliminar').off('click').on('click', function() {
            const idMenu = $(this).data('id');
            eliminarMenu(idMenu);
        });
        
        // Botón para ver submenús (solo en menús principales)
        $('.btn-ver-submenus').off('click').on('click', function() {
            const idMenu = $(this).data('id');
            // Activar la pestaña correspondiente
            $(`#tab-${idMenu}`).tab('show');
        });
    }
    
    // Inicializar pestañas
    function inicializarTabs() {
        $('#menuTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).data('bs-target');
            const menuId = $(e.target).data('menu-id');
            
            if (target === '#main-tab-pane') {
                cargarMenusPrincipales(rolFiltroActual);
            } else if (menuId) {
                cargarSubmenus(menuId, rolFiltroActual);
            }
        });
    }
    
    // Botón guardar orden
    $('#btnGuardarOrden').on('click', function() {
        const activePane = $('.tab-pane.active');
        const activePaneId = activePane.attr('id');
        
        if (activePaneId === 'main-tab-pane') {
            guardarOrden(0);
        } else {
            const padreId = activePaneId.replace('pane-', '');
            guardarOrden(padreId);
        }
    });
    
    // Botón buscar
    $('#btnAplicarFiltro').on('click', function() {
        const idRol = $('#filtro_permiso').val();
        rolFiltroActual = idRol;
        
        // Mostrar loading
        $(this).html('<span class="spinner-border spinner-border-sm me-2"></span>Aplicando...').prop('disabled', true);
        
        // Aplicar filtro a todas las pestañas
        aplicarFiltroATodasLasPestanas(idRol);
        
        setTimeout(() => {
            $(this).html('<i class="bi bi-funnel me-1"></i>Aplicar').prop('disabled', false);
        }, 500);
    });

    function aplicarFiltroATodasLasPestanas(idRol) {
        // Primero, aplicar a la pestaña activa
        const activePane = $('.tab-pane.active');
        const activePaneId = activePane.attr('id');
        
        if (activePaneId === 'main-tab-pane') {
            cargarMenusPrincipales(idRol);
        } else {
            const menuId = activePaneId.replace('pane-', '');
            cargarSubmenus(menuId, idRol);
        }
    }
    
    $('#btnNuevo').on('click', function() {
        Swal.fire({
            title: 'Crear Menú',
            text: '¿Qué deseas crear?',
            showDenyButton: true,
            showCancelButton: true, 
            allowOutsideClick: false, 
            allowEscapeKey: false,         
            confirmButtonText: 'Padre',
            denyButtonText: 'Hijo',
            cancelButtonText: 'Cerrar'       
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarModalPadre({}, (data) => {
                    $.post('administrarMenu', { opcion: 'Crear', tipo: 'padre', ...data, _token: $('meta[name="csrf-token"]').attr('content') }, function (resp) {
                        if (resp.respuesta === 'ok') {
                            Swal.fire('Éxito', resp.mensaje, 'success').then(() => {
                                // Recargar con el filtro actual
                                const activePane = $('.tab-pane.active');
                                const activePaneId = activePane.attr('id');
                                if (activePaneId === 'main-tab-pane') {
                                    cargarMenusPrincipales(rolFiltroActual);
                                }
                            });
                        } else Swal.fire('Error', resp.mensaje, 'error');
                    }, 'json');
                });

            } else if (result.isDenied) {
                // Crear Hijo
                let opcionesPadres = '';
                window.padres.forEach(p => {
                    opcionesPadres += `<option value="${p.id}">${p.nombre}</option>`;
                });

                Swal.fire({
                    title: 'Nuevo Hijo',
                    html: `
                        <select id="padreHijo" class="swal2-input">${opcionesPadres}</select>
                        <input id="nombreHijo" class="swal2-input" placeholder="Nombre del hijo">
                    `,
                    showCancelButton: true, 
                    allowOutsideClick: false, 
                    allowEscapeKey: false,         
                    cancelButtonText: 'Cerrar',      
                    preConfirm: () => {
                        return {
                            tipo: 'hijo',
                            padre_id: $('#padreHijo').val(),
                            nombre: $('#nombreHijo').val()
                        }
                    }
                }).then((res) => {
                    if (res.value) {
                        $.post('administrarMenu', {
                            opcion: 'Crear',
                            ...res.value,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        }, function (data) {
                            if (data.respuesta === 'ok') {
                                Swal.fire({
                                    title: 'Éxito',
                                    text: data.mensaje,
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    // Recargar la pestaña del padre con el filtro actual
                                    const padreId = res.value.padre_id;
                                    $(`#tab-${padreId}`).tab('show');
                                });
                            } else {
                                Swal.fire('Error', data.mensaje, 'error');
                            }
                        }, 'json');
                    }
                });
            }
        });
    });
    
    function abrirModalPermisos(idMenu, menuNombre = '') {
    $.post('administrarMenu', {
        opcion: 'Permisos',
        id_menu: idMenu,
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function (res) {
        if (res.respuesta === 'ok') {
            // Configurar nombre del menú
            if (menuNombre) {
                $('#menuNombrePermisos').text(menuNombre);
            }
            
            // Lista de permisos con íconos y colores
            const permisosConfig = [
                { key: 'ver', icon: 'bi-eye', color: 'white', label: 'Ver' },
                { key: 'crear', icon: 'bi-plus-circle', color: 'white', label: 'Crear' },
                { key: 'editar', icon: 'bi-pencil', color: 'white', label: 'Editar' },
                { key: 'eliminar', icon: 'bi-trash', color: 'white', label: 'Eliminar' },
                { key: 'configurar', icon: 'bi-gear', color: 'white', label: 'Config.' },
                { key: 'roles', icon: 'bi-person-badge', color: 'white', label: 'Roles' },
                { key: 'subcategoria', icon: 'bi-diagram-3', color: 'white', label: 'Subcat.' }
            ];

            // Generar cabeceras con estilo moderno - DESKTOP
            let ths = `
                <th class="head-rol-modern">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-badge me-2 text-white"></i>
                        <span class="fw-semibold">Rol</span>
                    </div>
                </th>
            `;
            
            permisosConfig.forEach(p => {
                ths += `
                <th class="text-center">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi ${p.icon} fs-5 text-${p.color} mb-1"></i>
                        <span class="fw-semibold small d-none d-lg-inline">${p.label}</span>
                        <span class="fw-semibold small d-inline d-lg-none">${p.label.substring(0, 3)}</span>
                    </div>
                </th>`;
            });

            $('#tablaPermisos thead tr').html(ths);

            // Generar filas desktop
            let filas = res.roles.map(rol => {
                let permiso = res.permisos[rol.id] || {};
                
                let celdas = permisosConfig.map(p => {
                    const estaActivo = permiso[p.key] ? true : false;
                    return `
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input type="checkbox" 
                                   class="form-check-input chkPermiso" 
                                   role="switch"
                                   data-campo="${p.key}" 
                                   data-rol="${rol.id}" 
                                   id="permiso_${rol.id}_${p.key}"
                                   ${estaActivo ? 'checked' : ''}
                                   style="width: 2.5em; height: 1.3em; cursor: pointer;">
                            <label class="form-check-label d-none" for="permiso_${rol.id}_${p.key}">
                                ${p.label}
                            </label>
                        </div>
                    </td>`;
                }).join('');

                return `<tr data-rol="${rol.id}" class="rol-row">
                    <td class="sticky-left bg-white">
                        <div class="d-flex align-items-center">
                            <div class="avatar-rol bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2 me-md-3" 
                                 style="width: 35px; height: 35px; font-weight: bold; font-size: 0.9rem;">
                                ${rol.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="text-truncate">
                                <div class="fw-semibold text-truncate">${rol.name}</div>
                            </div>
                        </div>
                    </td>
                    ${celdas}
                </tr>`;
            }).join('');

            $('#tablaPermisos tbody').html(filas);
            
            // Generar vista móvil (tarjetas)
            let mobileCards = res.roles.map(rol => {
                let permiso = res.permisos[rol.id] || {};
                
                let permisosHtml = permisosConfig.map(p => {
                    const estaActivo = permiso[p.key] ? true : false;
                    return `
                    <div class="col-3 col-sm-2">
                        <div class="d-flex flex-column align-items-center mb-2">
                            <div class="form-check form-switch mb-1">
                                <input type="checkbox" 
                                       class="form-check-input chkPermiso-mobile" 
                                       role="switch"
                                       data-campo="${p.key}" 
                                       data-rol="${rol.id}" 
                                       id="mobile_permiso_${rol.id}_${p.key}"
                                       ${estaActivo ? 'checked' : ''}
                                       style="width: 2.2em; height: 1.1em;">
                            </div>
                            <div class="text-center">
                                <i class="bi ${p.icon} text-${p.color} d-block fs-6"></i>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">${p.label}</small>
                            </div>
                        </div>
                    </div>`;
                }).join('');
                
                return `
                <div class="card mb-3 rol-card" data-rol="${rol.id}">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-rol bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 40px; height: 40px; font-weight: bold;">
                                ${rol.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-0 fw-bold">${rol.name}</h6>
                            </div>
                        </div>
                        
                        <div class="row g-1 g-sm-2">
                            ${permisosHtml}
                        </div>
                        
                        <div class="text-center mt-3 pt-2 border-top">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                <span class="permisos-activos-count">${Object.values(permiso).filter(v => v).length}</span> de ${permisosConfig.length} permisos activos
                            </small>
                        </div>
                    </div>
                </div>`;
            }).join('');
            
            $('#mobilePermisosView').html(mobileCards);
            
            // Actualizar estadísticas
            actualizarEstadisticas();
            
            // Mostrar modal con animación
            const modal = new bootstrap.Modal('#modalPermisos');
            modal.show();
            
            // Guardar ID del menú
            $('#modalPermisos').data('idMenu', idMenu);
            
            // Inicializar eventos
            inicializarEventosPermisos();
        }
    }, 'json');
}

function inicializarEventosPermisos() {
    // Buscar roles
    $('#buscarRol').on('keyup', function() {
        const search = $(this).val().toLowerCase();
        $('.rol-row, .rol-card').each(function() {
            const rolName = $(this).find('.fw-semibold, .card-title').text().toLowerCase();
            $(this).toggle(rolName.includes(search));
        });
        actualizarEstadisticas();
    });

    // Seleccionar todos
    $('#btnSeleccionarTodos').on('click', function() {
        $('.chkPermiso, .chkPermiso-mobile').prop('checked', true).trigger('change');
        actualizarEstadisticas();
        mostrarNotificacion('Todos los permisos seleccionados', 'info');
    });

    // Deseleccionar todos
    $('#btnDeseleccionarTodos').on('click', function() {
        $('.chkPermiso, .chkPermiso-mobile').prop('checked', false).trigger('change');
        actualizarEstadisticas();
        mostrarNotificacion('Permisos deseleccionados', 'warning');
    });

    // Cambiar permisos individuales - Desktop
    $(document).off('change', '.chkPermiso').on('change', '.chkPermiso', function() {
        cambiarPermiso($(this));
    });
    
    // Cambiar permisos individuales - Mobile
    $(document).off('change', '.chkPermiso-mobile').on('change', '.chkPermiso-mobile', function() {
        cambiarPermiso($(this));
    });
}

function cambiarPermiso(checkboxElement) {
    const idMenu = $('#modalPermisos').data('idMenu');
    const idRol = checkboxElement.data('rol');
    const campo = checkboxElement.data('campo');
    const valor = checkboxElement.is(':checked') ? 1 : 0;
    
    // Sincronizar ambos views
    const isMobile = checkboxElement.hasClass('chkPermiso-mobile');
    const desktopSelector = `#permiso_${idRol}_${campo}`;
    const mobileSelector = `#mobile_permiso_${idRol}_${campo}`;
    
    if (isMobile) {
        $(desktopSelector).prop('checked', valor);
    } else {
        $(mobileSelector).prop('checked', valor);
    }
    
    // Actualizar contador en tarjeta móvil
    if (isMobile) {
        const card = checkboxElement.closest('.rol-card');
        const activeCount = card.find('.chkPermiso-mobile:checked').length;
        card.find('.permisos-activos-count').text(activeCount);
    }
    
    // Mostrar estado de carga
    checkboxElement.prop('disabled', true);
    const originalState = checkboxElement.is(':checked');
    
    $.post('administrarMenu', {
        opcion: 'ActualizarPermiso',
        id_menu: idMenu,
        id_rol: idRol,
        campo: campo,
        valor: valor,
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function (res) {
        checkboxElement.prop('disabled', false);
        
        if (res.respuesta === 'ok') {
            // Actualizar estadísticas
            actualizarEstadisticas();
            
            // Efecto visual de confirmación
            const switchElement = checkboxElement;
            switchElement.parent().addClass('animate__animated animate__pulse');
            setTimeout(() => {
                switchElement.parent().removeClass('animate__animated animate__pulse');
            }, 300);
            
            // Mostrar notificación sutil
            const permisoLabel = getPermisoLabel(campo);
            const estado = valor ? 'activado' : 'desactivado';
            mostrarNotificacion(`Permiso ${permisoLabel} ${estado}`, 'success');
        } else {
            // Revertir si hay error
            checkboxElement.prop('checked', !originalState);
            if (isMobile) {
                $(desktopSelector).prop('checked', !originalState);
            } else {
                $(mobileSelector).prop('checked', !originalState);
            }
            Swal.fire('Error', res.mensaje, 'error');
        }
    }, 'json').fail(function() {
        checkboxElement.prop('disabled', false);
        checkboxElement.prop('checked', !originalState);
        if (isMobile) {
            $(desktopSelector).prop('checked', !originalState);
        } else {
            $(mobileSelector).prop('checked', !originalState);
        }
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}
function actualizarEstadisticas() {
    const totalPermisos = $('.chkPermiso').length;
    const permisosActivos = $('.chkPermiso:checked').length;
    const rolesConAcceso = $('.rol-row').filter(function() {
        return $(this).find('.chkPermiso:checked').length > 0;
    }).length;
    const totalRoles = $('.rol-row:visible').length;
    
    // Actualizar contadores con animación
    animateCounter('contadorActivos', permisosActivos);
    animateCounter('contadorRoles', rolesConAcceso);
    animateCounter('contadorTotal', totalPermisos);
    
    // Actualizar información
    const porcentaje = Math.round((permisosActivos / totalPermisos) * 100);
    $('#infoPermisos').html(`
        <i class="bi ${porcentaje > 50 ? 'bi-check-circle text-success' : 'bi-exclamation-circle text-warning'} me-1"></i>
        ${porcentaje}% de los permisos están activos (${permisosActivos}/${totalPermisos})
    `);
}

function animateCounter(elementId, targetValue) {
    const element = document.getElementById(elementId);
    const current = parseInt(element.textContent) || 0;
    const increment = targetValue > current ? 1 : -1;
    let currentValue = current;
    
    const timer = setInterval(() => {
        currentValue += increment;
        element.textContent = currentValue;
        
        if ((increment > 0 && currentValue >= targetValue) || 
            (increment < 0 && currentValue <= targetValue)) {
            element.textContent = targetValue;
            clearInterval(timer);
        }
    }, 20);
}

function getPermisoLabel(campo) {
    const labels = {
        'ver': 'Ver',
        'crear': 'Crear',
        'editar': 'Editar',
        'eliminar': 'Eliminar',
        'configurar': 'Configurar',
        'roles': 'Roles',
        'subcategoria': 'Subcategoría'
    };
    return labels[campo] || campo;
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear notificación toast
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${tipo} border-0 position-fixed"
             style="bottom: 20px; right: 20px; z-index: 1055;" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${tipo === 'success' ? 'bi-check-circle' : tipo === 'warning' ? 'bi-exclamation-triangle' : 'bi-info-circle'} me-2"></i>
                    ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    $('body').append(toastHtml);
    const toastElement = new bootstrap.Toast(document.getElementById(toastId));
    toastElement.show();
    
    // Remover después de 3 segundos
    setTimeout(() => {
        $(`#${toastId}`).remove();
    }, 3000);
}
    
    function editarMenu(idMenu) {
        $.post('administrarMenu', {
            opcion: 'Editar',
            id_menu: idMenu,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function (res) {
            if (res.respuesta === 'ok') {
                let menu = res.menu;

                if (menu.tipo === 'padre') {
                    mostrarModalPadre({
                        titulo: 'Editar Padre',
                        nombre: menu.nombre,
                        url: menu.url,
                        icono: menu.icono
                    }, (data) => {
                        // Actualizamos el padre
                        $.post('administrarMenu', {
                            opcion: 'Actualizar',
                            id: menu.id,
                            tipo: 'padre',
                            ...data,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        }, function (resp) {
                            if (resp.respuesta === 'ok') {
                                Swal.fire('Éxito', resp.mensaje, 'success').then(() => window.location.reload());
                            } else Swal.fire('Error', resp.mensaje, 'error');
                        }, 'json');
                    });
                } else {
                    // Hijo
                    let opcionesPadres = window.padres.map(p => 
                        `<option value="${p.id}" ${menu.padre_id == p.id ? 'selected' : ''}>${p.nombre}</option>`
                    ).join('');

                    Swal.fire({
                        title: 'Editar Hijo',
                        html: `
                            <select id="padreHijo" class="swal2-input">${opcionesPadres}</select>
                            <input id="nombreHijo" class="swal2-input" value="${menu.nombre}" placeholder="Nombre del hijo">
                        `,
                        showCancelButton: true,
                        allowOutsideClick: false, 
                        allowEscapeKey: false,
                        confirmButtonText: 'Actualizar',
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => ({
                            id: menu.id,
                            tipo: 'hijo',
                            padre_id: $('#padreHijo').val(),
                            nombre: $('#nombreHijo').val()
                        })
                    }).then((res) => {
                        if (res.value) {
                            $.post('administrarMenu', {
                                opcion: 'Actualizar',
                                ...res.value,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            }, function (resp) {
                                if (resp.respuesta === 'ok') {
                                    Swal.fire('Éxito', resp.mensaje, 'success').then(() => window.location.reload());
                                } else Swal.fire('Error', resp.mensaje, 'error');
                            }, 'json');
                        }
                    });
                }
            }
        }, 'json');
    }
    function mostrarModalPadre({ titulo = "Nuevo Padre", nombre = "", url = "", icono = "" }, callback) {
        // Extraer solo el nombre del icono para el input hidden
        let iconoNombre = '';
        if (icono) {
            if (icono.includes('bi-')) {
                iconoNombre = icono.split('bi-')[1];
            } else if (icono.includes(' ')) {
                const parts = icono.split(' ');
                iconoNombre = parts.length > 1 ? parts[1].replace('bi-', '') : icono;
            } else {
                iconoNombre = icono;
            }
        }
        
        const iconGrid = generarGridIconos(icono, ''); // Pasamos el icono completo

        Swal.fire({
            title: titulo,
            html: `
                <input id="nombrePadre" class="swal2-input" value="${nombre}" placeholder="Nombre">
                <input id="urlPadre" class="swal2-input" value="${url}" placeholder="URL">
                <div style="margin-bottom:10px;">
                    <input id="buscadorIconos" class="swal2-input" placeholder="Buscar icono..."
                        style="border:2px solid #007bff; border-radius:10px; box-shadow: 0 2px 5px rgba(0,0,0,0.15); padding:8px; transition: all 0.3s;">
                </div>
                <div id="iconPicker" style="display:flex; flex-wrap:wrap; gap:5px; max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:5px;">
                    ${iconGrid}
                </div>
                <input type="hidden" id="iconoPadre" value="${iconoNombre}">
                <style>
                    .icon-btn { cursor:pointer; border:1px solid #ccc; background:#fff; padding:5px; border-radius:5px; transition: all 0.2s; }
                    .icon-btn:hover { transform: scale(1.1); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
                    .icon-btn.selected { border-color:#007bff; background:#e7f1ff; }
                </style>
            `,
            showCancelButton: true,
            cancelButtonText: 'Cerrar',
            preConfirm: () => ({
                nombre: document.getElementById('nombrePadre').value,
                url: document.getElementById('urlPadre').value,
                icono: 'bi bi-' + document.getElementById('iconoPadre').value
            }),
            didOpen: () => {
                const modal = Swal.getHtmlContainer();
                const buscador = modal.querySelector('#buscadorIconos');
                const iconPicker = modal.querySelector('#iconPicker');
                const inputHidden = modal.querySelector('#iconoPadre');

                bindBotones(iconPicker, inputHidden);

                buscador.addEventListener('input', () => {
                    iconPicker.innerHTML = generarGridIconos('bi bi-' + inputHidden.value, buscador.value);
                    bindBotones(iconPicker, inputHidden);
                });
            }
        }).then(res => {
            if (res.isConfirmed && callback) callback(res.value);
        });
    }
    
    function actualizarURL(idMenu) {
        Swal.fire({
            title: 'Actualizar URL',
            input: 'text',
            inputLabel: 'Nueva URL',
            inputPlaceholder: 'Ej: dashboard/reportes',
            showCancelButton: true,
            allowOutsideClick: false, 
            allowEscapeKey: false,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar',
            preConfirm: (url) => {
                if (!url) {
                    Swal.showValidationMessage('La URL no puede estar vacía');
                }
                return url;
            }
        }).then((res) => {
            if (res.value) {
                $.post('administrarMenu', {
                    opcion: 'ActualizarURL',
                    id_menu: idMenu,
                    url: res.value,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, function (data) {
                    if (data.respuesta === 'ok') {
                        Swal.fire('Éxito', data.mensaje, 'success')
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire('Error', data.mensaje, 'error');
                    }
                }, 'json');
            }
        });
    }
    
    function eliminarMenu(idMenu) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Este menú será eliminado.",
            icon: 'warning',
            showCancelButton: true,
            allowOutsideClick: false, 
            allowEscapeKey: false,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('administrarMenu', {
                    opcion: 'Eliminar',
                    id_menu: idMenu,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, function (res) {
                    if (res.respuesta === 'ok') {
                        Swal.fire({
                            title: 'Éxito',
                            text: res.mensaje,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.mensaje, 'error');
                    }
                }, 'json');
            }
        });
    }
    
    // Inicializar
    function inicializar() {
        cargarMenusPrincipales();
        inicializarTabs();
        inicializarSortable();
        
        if (window.padres && window.padres.length > 0) {
            cargarSubmenus(window.padres[0].id);
        }
    }
    
    inicializar();
});