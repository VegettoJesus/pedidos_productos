document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const urlBase = 'contactoMensajes';
    async function post(opcion, extra = {}, action = 'default') {
        const loader = showPreloader(
            opcion === 'Eliminar' ? 'Eliminando mensaje...' :
            opcion === 'MarcarRespondido' ? 'Actualizando estado...' :
            opcion === 'MarcarLeido' ? 'Marcando como leído...' :
            opcion === 'Detalle' ? 'Cargando detalle...' :
            opcion === 'Stats' ? 'Actualizando...' :
            'Procesando...',
            action
        );

        try {
            const body = new FormData();
            body.append('opcion', opcion);
            Object.entries(extra).forEach(([k, v]) => body.append(k, v));

            const res = await fetch(urlBase, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body
            });
            return await res.json();
        } finally {
            hidePreloader(loader);
        }
    }

    async function refrescarStats() {
        const r = await post('Stats', {}, 'default');
        if (r.respuesta !== 'ok' || !r.stats) return;

        const map = {
            nuevos:      '.cm-pill-nuevo .cm-stat-value',
            pendientes:  '.cm-pill-pendiente .cm-stat-value',
            respondidos: '.cm-pill-respondido .cm-stat-value',
            total:       '.cm-pill-total .cm-stat-value',
        };

        Object.entries(map).forEach(([key, sel]) => {
            const el = document.querySelector(sel);
            if (el) el.textContent = r.stats[key] ?? 0;
        });
    }

    function actualizarBadgeFila(fila, estado, label) {
        if (!fila) return;
        fila.classList.remove('cm-row-nuevo');
        const badge = fila.querySelector('.cm-badge-estado');
        if (badge) {
            badge.className = 'cm-badge-estado cm-badge-' + estado;
            badge.innerHTML = `<i class="bi bi-circle-fill"></i> ${label}`;
        }
    }

    function actualizarModalSiAplica(id, estado, label, fechaRespondido = null) {
        const btnResp = document.getElementById('cmDetalleBtnRespondido');
        if (!btnResp || btnResp.dataset.id !== String(id)) return;

        const badge = document.getElementById('cmDetalleEstado');
        if (badge) {
            badge.className = 'cm-badge-estado cm-badge-' + estado;
            badge.innerHTML = `<i class="bi bi-circle-fill"></i> ${label}`;
        }

        if (estado === 'respondido') {
            btnResp.classList.add('d-none');
        }

        if (fechaRespondido) {
            const fechaResp = document.getElementById('cmDetalleRespondidoEn');
            if (fechaResp) fechaResp.textContent = fechaRespondido;
        }
    }

    function eliminarFilaConAnimacion(fila) {
        if (!fila) return;
        fila.style.transition = 'opacity 0.3s, transform 0.3s';
        fila.style.opacity = '0';
        fila.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            fila.remove();
            const tbody = document.querySelector('.cm-tabla tbody');
            if (tbody && !tbody.querySelector('tr')) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="cm-empty-state">
                                <div class="cm-empty-icon"><i class="bi bi-inbox"></i></div>
                                <h5 class="cm-empty-title">No hay mensajes</h5>
                                <p class="cm-empty-desc mb-0">
                                    Cuando alguien envíe el formulario de contacto, aparecerá aquí.
                                </p>
                            </div>
                        </td>
                    </tr>`;
            }
        }, 300);
    }

    const modalEl = document.getElementById('modalDetalleMensaje');
    const modalDetalle = modalEl ? new bootstrap.Modal(modalEl) : null;

    document.querySelectorAll('.btn-ver-detalle').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const loading = document.getElementById('cmDetalleLoading');
            const contenido = document.getElementById('cmDetalleContenido');

            loading.classList.remove('d-none');
            contenido.classList.add('d-none');
            modalDetalle.show();

            const r = await post('Detalle', { id }, 'cargar');

            if (r.respuesta !== 'ok') {
                loading.innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                        <p class="mt-2 mb-0">${r.mensaje || 'Error al cargar el detalle'}</p>
                    </div>`;
                return;
            }

            const m = r.contacto;

            document.getElementById('cmDetalleNombre').textContent     = m.nombre || '—';
            document.getElementById('cmDetalleEmail').textContent      = m.email || '—';
            document.getElementById('cmDetalleEmail').href             = 'mailto:' + m.email;
            document.getElementById('cmDetalleTelefono').textContent   = m.telefono || '—';
            document.getElementById('cmDetalleFecha').textContent      = m.created_at || '—';
            document.getElementById('cmDetalleAsunto').textContent     = m.asunto || '(Sin asunto)';
            document.getElementById('cmDetalleMensaje').textContent    = m.mensaje || '—';
            document.getElementById('cmDetalleRespondidoEn').textContent = m.respondido_en || 'Aún no';

            const badge = document.getElementById('cmDetalleEstado');
            badge.className = 'cm-badge-estado cm-badge-' + m.estado;
            badge.innerHTML = `<i class="bi bi-circle-fill"></i> ${m.estado_badge.label}`;
            const mailto = `mailto:${m.email}?subject=${encodeURIComponent('Re: ' + (m.asunto || ''))}`;
            const btnMailModal = document.getElementById('cmDetalleBtnResponderEmail');
            btnMailModal.href = '#';
            btnMailModal.dataset.id = m.id;
            btnMailModal.dataset.mailto = mailto;

            const btnResp = document.getElementById('cmDetalleBtnRespondido');
            btnResp.dataset.id = m.id;
            btnResp.classList.toggle('d-none', m.estado === 'respondido');

            loading.classList.add('d-none');
            contenido.classList.remove('d-none');
            const fila = document.querySelector(`tr[data-id="${m.id}"]`);
            actualizarBadgeFila(fila, m.estado, m.estado_badge.label);
            if (r.cambio_estado) {
                refrescarStats();
                Swal.fire({
                    icon: 'info',
                    title: 'Marcado como leído',
                    text: 'El mensaje cambió a estado "Leído".',
                    timer: 1200,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        });
    });

    document.getElementById('cmDetalleBtnRespondido')?.addEventListener('click', async function () {
        const id = this.dataset.id;
        const r = await post('MarcarRespondido', { id }, 'editar');

        if (r.respuesta !== 'ok') {
            Swal.fire('Error', r.mensaje || 'Error al actualizar', 'error');
            return;
        }

        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        const fechaFmt =
            `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} ` +
            `${pad(now.getHours())}:${pad(now.getMinutes())}`;

        actualizarModalSiAplica(id, 'respondido', 'Respondido', fechaFmt);
        const fila = document.querySelector(`tr[data-id="${id}"]`);
        actualizarBadgeFila(fila, 'respondido', 'Respondido');
        refrescarStats();

        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: r.mensaje || 'Mensaje marcado como respondido',
            timer: 1200,
            showConfirmButton: false
        });
    });

    document.querySelectorAll('.btn-responder-email').forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();

            const id     = this.dataset.id;
            const mailto = this.dataset.mailto;

            if (!mailto || !mailto.startsWith('mailto:')) {
                const href = this.getAttribute('href');
                if (href && href.startsWith('mailto:')) {
                    window.location.href = href;
                }
                return;
            }

            const confirmacion = await Swal.fire({
                title: '¿Responder por email?',
                html: 'Se abrirá tu cliente de correo.<br><small class="text-muted">¿Marcar como respondido?</small>',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="bi bi-check2-circle me-1"></i> Sí, marcar y abrir',
                denyButtonText: '<i class="bi bi-envelope me-1"></i> Solo abrir correo',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754',
                denyButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false
            });

            if (confirmacion.isDismissed) return;
            if (confirmacion.isDenied) {
                window.location.href = mailto;
                return;
            }

            const r = await post('MarcarRespondido', { id }, 'editar');

            if (r.respuesta !== 'ok') {
                Swal.fire('Error', r.mensaje || 'Error al actualizar', 'error');
                return;
            }

            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const fechaFmt =
                `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} ` +
                `${pad(now.getHours())}:${pad(now.getMinutes())}`;

            const fila = document.querySelector(`tr[data-id="${id}"]`);
            actualizarBadgeFila(fila, 'respondido', 'Respondido');
            actualizarModalSiAplica(id, 'respondido', 'Respondido', fechaFmt);
            refrescarStats();

            Swal.fire({
                icon: 'success',
                title: 'Marcado como respondido',
                text: 'Abriendo tu cliente de correo...',
                timer: 900,
                showConfirmButton: false
            }).then(() => {
                window.location.href = mailto;
            });
        });
    });

    document.querySelectorAll('.btn-marcar-respondido').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const r = await post('MarcarRespondido', { id }, 'editar');

            if (r.respuesta !== 'ok') {
                Swal.fire('Error', r.mensaje || 'Error al actualizar', 'error');
                return;
            }

            const fila = document.querySelector(`tr[data-id="${id}"]`);
            actualizarBadgeFila(fila, 'respondido', 'Respondido');
            actualizarModalSiAplica(id, 'respondido', 'Respondido');
            refrescarStats();

            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: r.mensaje || 'Mensaje marcado como respondido',
                timer: 1200,
                showConfirmButton: false
            });
        });
    });

    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', async function () {
            const confirmacion = await Swal.fire({
                title: '¿Eliminar este mensaje?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });

            if (!confirmacion.isConfirmed) return;

            const id = this.dataset.id;
            const r = await post('Eliminar', { id }, 'eliminar');

            if (r.respuesta !== 'ok') {
                Swal.fire('Error', r.mensaje || 'Error al eliminar', 'error');
                return;
            }

            const fila = document.querySelector(`tr[data-id="${id}"]`);
            eliminarFilaConAnimacion(fila);
            refrescarStats();

            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: r.mensaje || 'Mensaje eliminado',
                timer: 1200,
                showConfirmButton: false
            });
        });
    });

    document.querySelectorAll('.btn-marcar-leido').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const r = await post('MarcarLeido', { id }, 'editar');

            if (r.respuesta !== 'ok') {
                Swal.fire('Error', r.mensaje || 'Error', 'error');
                return;
            }

            const fila = document.querySelector(`tr[data-id="${id}"]`);
            actualizarBadgeFila(fila, 'leido', 'Leído');
            refrescarStats();
        });
    });
});