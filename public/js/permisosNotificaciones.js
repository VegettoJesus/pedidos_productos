(function() {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    const BASE_URL = 'permisosNotificaciones'; 
    let usuarioActual = null;
    let timeoutBusqueda = null;

    // ============================================
    // 1. TOGGLE DE PERMISOS POR ROL
    // ============================================
    document.querySelectorAll('.chk-permiso-rol').forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            const rolId = this.dataset.rolId;
            const tipoId = this.dataset.tipoId;
            const rolNombre = this.dataset.rolNombre;
            const tipoNombre = this.dataset.tipoNombre;
            const puedeVer = this.checked;

            // Bloquear mientras se procesa
            this.disabled = true;

            try {
                const res = await fetch(BASE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        opcion: 'ActualizarPermisoRol',
                        rol_id: rolId,
                        tipo_notificacion_id: tipoId,
                        puede_ver: puedeVer,
                    })
                });

                const data = await res.json();

                if (data.respuesta !== 'ok') {
                    // Revertir
                    this.checked = !puedeVer;
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.mensaje || 'No se pudo actualizar',
                        confirmButtonColor: '#F4AB28',
                    });
                } else {
                    // Feedback visual sutil
                    const td = this.closest('td');
                    td.classList.add('bg-success-subtle');
                    setTimeout(() => td.classList.remove('bg-success-subtle'), 800);
                }
            } catch (err) {
                this.checked = !puedeVer;
                Swal.fire('Error', 'Error de conexión', 'error');
            } finally {
                this.disabled = false;
            }
        });
    });

    // ============================================
    // 2. BÚSQUEDA DE USUARIOS
    // ============================================
    const inputBuscar = document.getElementById('buscarUsuarioInput');
    const resultados = document.getElementById('resultadosBusqueda');

    inputBuscar?.addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        const query = this.value.trim();

        if (query.length < 2) {
            resultados.style.display = 'none';
            return;
        }

        timeoutBusqueda = setTimeout(async () => {
            try {
                const res = await fetch(BASE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        opcion: 'BuscarUsuarios',
                        query: query,
                    })
                });

                const data = await res.json();

                if (data.respuesta === 'ok' && data.usuarios.length > 0) {
                    renderResultadosBusqueda(data.usuarios);
                } else {
                    resultados.innerHTML = `
                        <div class="list-group-item text-muted">
                            <i class="bi bi-search me-1"></i> No se encontraron usuarios
                        </div>
                    `;
                    resultados.style.display = 'block';
                }
            } catch (err) {
                console.error('Error búsqueda:', err);
            }
        }, 300);
    });

    function renderResultadosBusqueda(usuarios) {
        resultados.innerHTML = '';
        usuarios.forEach(u => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${u.nombres} ${u.apellidos}</strong>
                        <br>
                        <small class="text-muted">${u.email}</small>
                    </div>
                    <span class="badge bg-secondary">${u.rol ? u.rol.name : 'Sin rol'}</span>
                </div>
            `;
            item.addEventListener('click', () => seleccionarUsuario(u));
            resultados.appendChild(item);
        });
        resultados.style.display = 'block';
    }

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!inputBuscar?.contains(e.target) && !resultados?.contains(e.target)) {
            resultados.style.display = 'none';
        }
    });

    // ============================================
    // 3. SELECCIONAR USUARIO
    // ============================================
    async function seleccionarUsuario(usuario) {
        usuarioActual = usuario;
        inputBuscar.value = `${usuario.nombres} ${usuario.apellidos}`;
        resultados.style.display = 'none';

        document.getElementById('usuarioSeleccionadoNombre').textContent = 
            `${usuario.nombres} ${usuario.apellidos}`;
        document.getElementById('usuarioSeleccionadoRol').textContent = 
            usuario.rol ? usuario.rol.name : 'Sin rol';
        document.getElementById('panelExcepcionesUsuario').style.display = 'block';

        await cargarMatrizExcepciones(usuario.id);
    }

    document.getElementById('btnCerrarPanelUsuario')?.addEventListener('click', () => {
        document.getElementById('panelExcepcionesUsuario').style.display = 'none';
        usuarioActual = null;
        inputBuscar.value = '';
    });

    // ============================================
    // 4. CARGAR MATRIZ DE EXCEPCIONES
    // ============================================
    async function cargarMatrizExcepciones(usuarioId) {
        const contenedor = document.getElementById('matrizExcepcionesUsuario');
        contenedor.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Cargando permisos...</p>
            </div>
        `;

        try {
            const res = await fetch(BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    opcion: 'ObtenerPermisosEfectivosUsuario',
                    usuario_id: usuarioId,
                })
            });

            const data = await res.json();

            if (data.respuesta !== 'ok') {
                contenedor.innerHTML = `
                    <div class="alert alert-danger">${data.mensaje}</div>
                `;
                return;
            }

            renderMatrizExcepciones(data.permisos);
        } catch (err) {
            contenedor.innerHTML = `
                <div class="alert alert-danger">Error al cargar permisos</div>
            `;
        }
    }

    function renderMatrizExcepciones(permisos) {
        const contenedor = document.getElementById('matrizExcepcionesUsuario');
        
        let html = `
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tipo de Notificación</th>
                        <th class="text-center" style="width: 100px;">Permiso del Rol</th>
                        <th class="text-center" style="width: 200px;">Excepción</th>
                        <th class="text-center" style="width: 120px;">Permiso Final</th>
                    </tr>
                </thead>
                <tbody>
        `;

        permisos.forEach(p => {
            const permisoRolBadge = p.permiso_rol 
                ? '<span class="badge bg-success"><i class="bi bi-check-lg"></i></span>' 
                : '<span class="badge bg-secondary"><i class="bi bi-x-lg"></i></span>';

            const permisoFinalBadge = p.permiso_final
                ? '<span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> Permitido</span>'
                : '<span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i> Denegado</span>';

            // Estado de la excepción actual
            let excepcionHtml = '<span class="text-muted small">Sin excepción</span>';
            if (p.excepcion) {
                if (p.excepcion.tipo_excepcion === 'permitir') {
                    excepcionHtml = `
                        <span class="badge bg-success">
                            <i class="bi bi-check-circle me-1"></i> Permitir
                        </span>
                        ${p.excepcion.motivo ? `<br><small class="text-muted">${p.excepcion.motivo}</small>` : ''}
                    `;
                } else {
                    excepcionHtml = `
                        <span class="badge bg-danger">
                            <i class="bi bi-x-circle me-1"></i> Denegar
                        </span>
                        ${p.excepcion.motivo ? `<br><small class="text-muted">${p.excepcion.motivo}</small>` : ''}
                    `;
                }
            }

            html += `
                <tr data-tipo-id="${p.tipo_id}">
                    <td>
                        <i class="${p.tipo_icono}" style="color: ${p.tipo_color}; font-size: 1.1rem;"></i>
                        <strong class="ms-1">${p.tipo_nombre}</strong>
                    </td>
                    <td class="text-center">${permisoRolBadge}</td>
                    <td class="text-center">
                        ${excepcionHtml}
                        <div class="btn-group btn-group-sm mt-1" role="group">
                            <button type="button" 
                                    class="btn btn-outline-success btn-set-excepcion"
                                    data-tipo-id="${p.tipo_id}"
                                    data-tipo-excepcion="permitir"
                                    title="Permitir individualmente">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button type="button" 
                                    class="btn btn-outline-danger btn-set-excepcion"
                                    data-tipo-id="${p.tipo_id}"
                                    data-tipo-excepcion="denegar"
                                    title="Denegar individualmente">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            ${p.excepcion ? `
                                <button type="button" 
                                        class="btn btn-outline-secondary btn-remove-excepcion"
                                        data-excepcion-id="${p.excepcion.id}"
                                        title="Quitar excepción (volver al rol)">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                    <td class="text-center">${permisoFinalBadge}</td>
                </tr>
            `;
        });

        html += `</tbody></table>`;
        contenedor.innerHTML = html;

        // Bind eventos
        contenedor.querySelectorAll('.btn-set-excepcion').forEach(btn => {
            btn.addEventListener('click', async function() {
                const tipoId = this.dataset.tipoId;
                const tipoExcepcion = this.dataset.tipoExcepcion;

                // Pedir motivo con SweetAlert
                const { value: motivo, isConfirmed } = await Swal.fire({
                    title: tipoExcepcion === 'permitir' 
                        ? '¿Permitir esta notificación?' 
                        : '¿Denegar esta notificación?',
                    input: 'textarea',
                    inputLabel: 'Motivo (opcional)',
                    inputPlaceholder: 'Ej: Este usuario requiere acceso especial...',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: tipoExcepcion === 'permitir' ? '#28a745' : '#dc3545',
                });

                if (!isConfirmed) return;

                await guardarExcepcion(usuarioActual.id, tipoId, tipoExcepcion, motivo);
            });
        });

        contenedor.querySelectorAll('.btn-remove-excepcion').forEach(btn => {
            btn.addEventListener('click', async function() {
                const excepcionId = this.dataset.excepcionId;
                await eliminarExcepcion(excepcionId);
            });
        });
    }

    // ============================================
    // 5. GUARDAR EXCEPCIÓN
    // ============================================
    async function guardarExcepcion(usuarioId, tipoId, tipoExcepcion, motivo) {
        try {
            const res = await fetch(BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    opcion: 'GuardarExcepcionUsuario',
                    usuario_id: usuarioId,
                    tipo_notificacion_id: tipoId,
                    tipo_excepcion: tipoExcepcion,
                    motivo: motivo,
                })
            });

            const data = await res.json();

            if (data.respuesta === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Excepción guardada',
                    timer: 1200,
                    showConfirmButton: false,
                });
                await cargarMatrizExcepciones(usuarioId);
                await recargarExcepciones();
            } else {
                Swal.fire('Error', data.mensaje, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }

    // ============================================
    // 6. ELIMINAR EXCEPCIÓN
    // ============================================
    async function eliminarExcepcion(excepcionId) {
        const confirm = await Swal.fire({
            title: '¿Eliminar excepción?',
            text: 'El usuario volverá al permiso por defecto de su rol.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
        });

        if (!confirm.isConfirmed) return;

        try {
            const res = await fetch(BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    opcion: 'EliminarExcepcionUsuario',
                    excepcion_id: excepcionId,
                })
            });

            const data = await res.json();

            if (data.respuesta === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Excepción eliminada',
                    timer: 1200,
                    showConfirmButton: false,
                });
                
                if (usuarioActual) {
                    await cargarMatrizExcepciones(usuarioActual.id);
                }
                await recargarExcepciones();
            } else {
                Swal.fire('Error', data.mensaje, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }

    // Bind botones de eliminar de la tabla de excepciones existentes
    document.querySelectorAll('.btn-eliminar-excepcion').forEach(btn => {
        btn.addEventListener('click', function() {
            eliminarExcepcion(this.dataset.excepcionId);
        });
    });

    // ============================================
    // 7. RECARGAR TABLA DE EXCEPCIONES
    // ============================================
    async function recargarExcepciones() {
        try {
            const res = await fetch(BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ opcion: 'ListarExcepciones' })
            });

            const data = await res.json();

            if (data.respuesta === 'ok') {
                renderTablaExcepciones(data.excepciones);
                document.getElementById('totalExcepciones').textContent = data.excepciones.length;
            }
        } catch (err) {
            console.error('Error recargando excepciones:', err);
        }
    }

    function renderTablaExcepciones(excepciones) {
        const tbody = document.getElementById('tbodyExcepciones');
        
        if (excepciones.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        No hay excepciones registradas
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = excepciones.map(e => `
            <tr data-excepcion-id="${e.id}">
                <td>
                    <i class="bi bi-person-fill me-1"></i>
                    <strong>${e.usuario_nombre}</strong>
                    <br>
                    <small class="text-muted">${e.usuario_email}</small>
                </td>
                <td>
                    <i class="${e.tipo_icono}" style="color: ${e.tipo_color};"></i>
                    ${e.tipo_nombre}
                </td>
                <td class="text-center">
                    ${e.tipo_excepcion === 'permitir' 
                        ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Permitir</span>'
                        : '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Denegar</span>'}
                </td>
                <td><small>${e.motivo || '—'}</small></td>
                <td class="text-center"><small>${e.created_at}</small></td>
                <td class="text-center">
                    <button type="button" 
                            class="btn btn-sm btn-danger btn-eliminar-excepcion"
                            data-excepcion-id="${e.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        // Re-bind eventos
        tbody.querySelectorAll('.btn-eliminar-excepcion').forEach(btn => {
            btn.addEventListener('click', function() {
                eliminarExcepcion(this.dataset.excepcionId);
            });
        });
    }

})();