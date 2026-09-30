
(function() {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    const API_BASE = '/notificaciones/panel';

    let isLoading = false;
    const btnCargarMas = document.getElementById('btnCargarMas');
    let currentPage = btnCargarMas ? parseInt(btnCargarMas.dataset.currentPage || '1') : 1;
    let hasMorePages = btnCargarMas ? btnCargarMas.dataset.hasMore === '1' : false;
    if (btnCargarMas) {
        btnCargarMas.style.display = hasMorePages ? 'inline-block' : 'none';
        function renderNotificacion(notif) {
            const prioridadColors = {
                critica: 'danger',
                alta: 'warning',
                media: 'info',
                baja: 'secondary'
            };
            const prioridadLabels = {
                critica: 'Crítica',
                alta: 'Alta',
                media: 'Media',
                baja: 'Baja'
            };
            
            const tipoColor = notif.tipo?.color || '#6c757d';
            const tipoIcono = notif.tipo?.icono || 'bi bi-bell';
            const tipoNombre = notif.tipo?.nombre || 'Sin tipo';
            const prioridadColor = prioridadColors[notif.prioridad] || 'secondary';
            const prioridadLabel = prioridadLabels[notif.prioridad] || notif.prioridad;
            
            // Calcular "hace X tiempo" simple
            const fecha = new Date(notif.created_at);
            const ahora = new Date();
            const diffMs = ahora - fecha;
            const diffMin = Math.floor(diffMs / 60000);
            const diffHoras = Math.floor(diffMin / 60);
            const diffDias = Math.floor(diffHoras / 24);
            let hace;
            if (diffMin < 1) hace = 'hace unos segundos';
            else if (diffMin < 60) hace = `hace ${diffMin} min`;
            else if (diffHoras < 24) hace = `hace ${diffHoras} h`;
            else hace = `hace ${diffDias} d`;
            
            const leida = notif.leida;
            
            return `
                <div class="notificacion-item ${leida ? 'leida' : 'no-leida'}"
                    data-id="${notif.id}"
                    data-tipo="${notif.tipo_notificacion_id}"
                    data-prioridad="${notif.prioridad}"
                    data-leida="${leida ? '1' : '0'}">
                    
                    <div class="notificacion-prioridad prioridad-${notif.prioridad}"></div>
                    
                    <div class="notificacion-icono">
                        <span class="badge-tipo"
                            style="background: ${tipoColor}20; color: ${tipoColor};">
                            <i class="${tipoIcono}"></i>
                        </span>
                    </div>
                    
                    <div class="notificacion-contenido">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="mb-0 fw-bold ${!leida ? 'text-dark' : 'text-muted'}">
                                ${notif.titulo}
                                ${!leida ? '<span class="badge bg-danger ms-2" style="font-size: 0.6rem;">NUEVA</span>' : ''}
                            </h6>
                            <small class="text-muted flex-shrink-0 ms-2">
                                <i class="bi bi-clock"></i> ${hace}
                            </small>
                        </div>
                        
                        <p class="mb-2 text-muted small">${notif.mensaje}</p>
                        
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="badge" style="background: ${tipoColor}; font-size: 0.7rem;">
                                    ${tipoNombre}
                                </span>
                                <span class="badge bg-${prioridadColor}" style="font-size: 0.7rem;">
                                    ${prioridadLabel}
                                </span>
                            </div>
                            
                            <div class="d-flex gap-1">
                                ${notif.url ? `
                                    <a href="${notif.url}" 
                                    class="btn btn-sm btn-primary"
                                    data-marcar-leida="${notif.id}">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>
                                        ${notif.boton_texto || 'Ver más'}
                                    </a>
                                ` : ''}
                                
                                ${!leida ? `
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success btn-marcar-leida"
                                            data-id="${notif.id}"
                                            title="Marcar como leída">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                ` : `
                                    <span class="badge bg-light text-success align-self-center">
                                        <i class="bi bi-check2-all"></i> Leída
                                    </span>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        btnCargarMas.addEventListener('click', async function() {
            if (isLoading || !hasMorePages) return;
            isLoading = true;
            
            const originalHTML = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-arrow-clockwise spin me-1"></i> Cargando...';
            
            try {
                currentPage++;
                const perPage = btnCargarMas.dataset.perPage || '15';
                const res = await fetch(`${API_BASE}?page=${currentPage}&limit=${perPage}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(notif => {
                        const html = renderNotificacion(notif);
                        document.getElementById('contenedorNotificaciones')
                            .insertAdjacentHTML('beforeend', html);
                    });
                    
                    hasMorePages = data.pagination.has_more;
                    this.style.display = hasMorePages ? 'inline-block' : 'none';
                } else {
                    this.style.display = 'none';
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'No se pudieron cargar más notificaciones', 'error');
            } finally {
                isLoading = false;
                this.disabled = false;
                this.innerHTML = originalHTML;
            }
        });
    }

    // ============================================
    // 1. MARCAR COMO LEÍDA (individual)
    // ============================================
    document.querySelectorAll('.btn-marcar-leida').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            await marcarComoLeida(id);
        });
    });

    document.querySelectorAll('[data-marcar-leida]').forEach(link => {
        link.addEventListener('click', function() {
            const id = this.dataset.marcarLeida;
            fetch(`${API_BASE}/${id}/leer`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                }
            });
        });
    });

    async function marcarComoLeida(id) {
        const statusMessages = showPreloader('Marcando como leída...', 'editar');

        try {
            const res = await fetch(`${API_BASE}/${id}/leer`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                }
            });

            const data = await res.json();
            hidePreloader(statusMessages);

            if (data.success) {
                const item = document.querySelector(`.notificacion-item[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('no-leida');
                    item.classList.add('leida');
                    item.dataset.leida = '1';

                    const btn = item.querySelector('.btn-marcar-leida');
                    if (btn) {
                        btn.outerHTML = `
                            <span class="badge bg-light text-success align-self-center">
                                <i class="bi bi-check2-all"></i> Leída
                            </span>
                        `;
                    }

                    const badgeNueva = item.querySelector('.badge.bg-danger');
                    if (badgeNueva) badgeNueva.remove();
                }

                await actualizarContador();

                Swal.fire({
                    icon: 'success',
                    title: 'Listo',
                    text: 'Notificación marcada como leída',
                    timer: 1200,
                    showConfirmButton: false,
                });
            } else {
                Swal.fire('Error', data.message || 'No se pudo marcar', 'error');
            }
        } catch (err) {
            console.error('Error:', err);
            hidePreloader(statusMessages);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }

    document.addEventListener('click', function(e) {
        const btnMarcar = e.target.closest('.btn-marcar-leida');
        if (btnMarcar) {
            e.preventDefault();
            marcarComoLeida(btnMarcar.dataset.id);
            return;
        }
        
        const linkMarcar = e.target.closest('[data-marcar-leida]');
        if (linkMarcar) {
            const id = linkMarcar.dataset.marcarLeida;
            fetch(`${API_BASE}/${id}/leer`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                }
            });
        }
    });

    // ============================================
    // 2. MARCAR TODAS COMO LEÍDAS
    // ============================================
    document.getElementById('btnMarcarTodasLeidas')?.addEventListener('click', async function() {
        const confirm = await Swal.fire({
            title: '¿Marcar todas como leídas?',
            text: 'Todas las notificaciones pasarán a estado leído.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar todas',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#F4AB28',
        });

        if (!confirm.isConfirmed) return;

        const statusMessages = showPreloader('Marcando todas como leídas...', 'editar');

        try {
            const res = await fetch(`${API_BASE}/marcar-todas-leidas`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                }
            });

            const data = await res.json();
            hidePreloader(statusMessages);

            if (data.success) {
                document.querySelectorAll('.notificacion-item').forEach(item => {
                    item.classList.remove('no-leida');
                    item.classList.add('leida');
                    item.dataset.leida = '1';

                    const btn = item.querySelector('.btn-marcar-leida');
                    if (btn) {
                        btn.outerHTML = `
                            <span class="badge bg-light text-success align-self-center">
                                <i class="bi bi-check2-all"></i> Leída
                            </span>
                        `;
                    }

                    const badgeNueva = item.querySelector('.badge.bg-danger');
                    if (badgeNueva) badgeNueva.remove();
                });

                await actualizarContador();

                Swal.fire({
                    icon: 'success',
                    title: 'Listo',
                    text: 'Todas las notificaciones marcadas como leídas',
                    timer: 1500,
                    showConfirmButton: false,
                });
            } else {
                Swal.fire('Error', data.message || 'No se pudo completar', 'error');
            }
        } catch (err) {
            hidePreloader(statusMessages);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });

    // ============================================
    // 3. FILTROS
    // ============================================
    const filtroBusqueda = document.getElementById('filtroBusqueda');
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroPrioridad = document.getElementById('filtroPrioridad');

    function aplicarFiltros() {
        const texto = filtroBusqueda?.value.toLowerCase() || '';
        const tipoId = filtroTipo?.value || '';
        const estado = filtroEstado?.value || '';
        const prioridad = filtroPrioridad?.value || '';

        document.querySelectorAll('.notificacion-item').forEach(item => {
            let mostrar = true;

            if (texto) {
                const titulo = item.querySelector('h6')?.textContent.toLowerCase() || '';
                const mensaje = item.querySelector('p')?.textContent.toLowerCase() || '';
                if (!titulo.includes(texto) && !mensaje.includes(texto)) {
                    mostrar = false;
                }
            }

            if (tipoId && item.dataset.tipo !== tipoId) {
                mostrar = false;
            }

            if (estado === 'no_leidas' && item.dataset.leida === '1') {
                mostrar = false;
            }
            if (estado === 'leidas' && item.dataset.leida === '0') {
                mostrar = false;
            }

            if (prioridad && item.dataset.prioridad !== prioridad) {
                mostrar = false;
            }

            item.style.display = mostrar ? 'flex' : 'none';
        });
    }

    filtroBusqueda?.addEventListener('input', aplicarFiltros);
    filtroTipo?.addEventListener('change', aplicarFiltros);
    filtroEstado?.addEventListener('change', aplicarFiltros);
    filtroPrioridad?.addEventListener('change', aplicarFiltros);

    document.getElementById('btnRefrescarNotificaciones')?.addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise spin me-1"></i> Refrescando...';
        showPreloader('Actualizando notificaciones...', 'cargar');

        setTimeout(() => {
            location.reload();
        }, 1200);
    });

    actualizarContador();

})();