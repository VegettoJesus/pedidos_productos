(function() {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    const API = '/AdministrarPagos/lista';
    let metodoActual = null;

    let seleccionados = {
        categorias: [],
        productos: []
    };

    const set = (form, name, value) => {
        const el = form.querySelector(`[name="${name}"]`);
        if (el) {
            const radios = form.querySelectorAll(`[name="${name}"]`);
            if (radios.length > 0 && radios[0].type === 'radio') {
                radios.forEach(r => {
                    r.checked = r.value === String(value);
                });
            } else {
                el.value = value ?? '';
            }
        }
    };

    const check = (form, name, value) => {
        const el = form.querySelector(`[name="${name}"]`);
        if (el) el.checked = !!value;
    };

    document.querySelectorAll('.metodo-item-modern').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.closest('.form-check')) return;
            cargarMetodo(this.dataset.id, this.dataset.slug);
        });
    });

    async function cargarMetodo(id, slug) {
        metodoActual = { id, slug };
        seleccionados = { categorias: [], productos: [] };

        document.querySelectorAll('.metodo-item-modern').forEach(el => el.classList.remove('active'));
        document.querySelector(`.metodo-item-modern[data-id="${id}"]`)?.classList.add('active');

        const template = document.getElementById(`template-${slug}`);
        if (!template) return;

        const panel = document.getElementById('panelConfiguracion');
        panel.innerHTML = template.innerHTML;

        const statusMessages = showPreloader('Cargando configuración...', 'cargar');

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ opcion: 'Obtener', id })
            });
            const data = await res.json();
            hidePreloader(statusMessages);

            if (data.respuesta === 'ok') {
                llenarFormulario(data.metodo);
            } else {
                Swal.fire('Error', data.mensaje, 'error');
            }
        } catch (err) {
            hidePreloader(statusMessages);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }
    

    function llenarFormulario(metodo) {
        const form = document.getElementById('formMetodo');
        if (!form) return;

        set(form, 'id', metodo.id);
        check(form, 'activo', metodo.activo);
        set(form, 'titulo', metodo.configuracion?.titulo || '');
        set(form, 'descripcion_cfg', metodo.configuracion?.descripcion || '');

        const config = metodo.configuracion || {};

        if (form.dataset.slug === 'contra_entrega') {
            set(form, 'instrucciones', config.instrucciones || '');
            set(form, 'desactivar_si_importe_mayor', config.desactivar_si_importe_mayor || '');
            set(form, 'restriccion_categoria_modo', config.restriccion_categoria_modo || 'al_menos_uno');
            set(form, 'restriccion_producto_modo', config.restriccion_producto_modo || 'al_menos_uno');
            set(form, 'mensaje_no_disponible', config.mensaje_no_disponible || '');

            const cargo = config.cargo_adicional || {};
            set(form, 'cargo_tipo', cargo.tipo || 'porcentaje');
            set(form, 'cargo_valor', cargo.valor || 0);
            check(form, 'impuesto_activo', cargo.impuesto_activo);
            set(form, 'impuesto_porcentaje', cargo.impuesto_porcentaje || 0);
            set(form, 'cargo_desactivar_si_importe_mayor', cargo.desactivar_si_importe_mayor || '');
            check(form, 'incluir_impuestos', cargo.incluir_en_total?.impuestos);
            check(form, 'incluir_envio', cargo.incluir_en_total?.envio);
            inicializarBuscador(form, 'categoria', config.categorias_desactivar || []);
            inicializarBuscador(form, 'producto', config.productos_desactivar || []);
            setTimeout(() => configurarToggleBuscador(form), 100);
        }

        if (form.dataset.slug === 'transferencia') {
            set(form, 'instrucciones', config.instrucciones || '');
            inicializarCuentas(config.cuentas || []);
        }

        if (form.dataset.slug === 'qr') {
            set(form, 'icono_imagen', config.icono_imagen || '');
            set(form, 'mensaje_emergente', config.mensaje_emergente || '');
            set(form, 'importe_limite', config.importe_limite || '');
            set(form, 'mensaje_limite', config.mensaje_limite || '');
            set(form, 'telefono_afiliado', config.telefono_afiliado || '');
            set(form, 'imagen_qr', config.imagen_qr || '');

            inicializarUpload(form, 'qr');
            inicializarUpload(form, 'icono');
            console.log(config)
            if (config.imagen_qr) {
                const preview = form.querySelector('#previewQR');
                const uploadArea = form.querySelector('#uploadQR');
                if (preview) {
                    preview.innerHTML = `
                        <div class="qr-preview-card">
                            <button type="button" class="qr-preview-remove" title="Eliminar imagen">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <img src="/metodos_pago/${config.imagen_qr}" alt="Código QR">
                            <div class="qr-preview-badge">
                                <i class="bi bi-check-circle-fill"></i> Imagen cargada
                            </div>
                        </div>
                    `;
                }
                if (uploadArea) uploadArea.classList.add('has-file');
            }

            if (config.icono_imagen) {
                const preview = form.querySelector('#previewIcono');
                const uploadArea = form.querySelector('#uploadIcono');
                if (preview) {
                    preview.innerHTML = `
                        <div class="icono-preview">
                            <img src="/metodos_pago/${config.icono_imagen}" alt="Icono">
                            <span class="icono-info">
                                <i class="bi bi-check-circle-fill"></i> Icono cargado
                            </span>
                            <button type="button" class="icono-remove" title="Eliminar icono">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    `;
                }
                if (uploadArea) uploadArea.classList.add('has-file');
            }
        }

        const toggleHeader = form.querySelector('[name="activo"]');
        if (toggleHeader) {
            actualizarBadgeActivo(toggleHeader);
            toggleHeader.addEventListener('change', function() {
                const id = form.querySelector('[name="id"]').value;
                const nuevoEstado = this.checked;
                actualizarBadgeActivo(this);
                sincronizarToggleActivo(id, nuevoEstado);
            });
        }

        sincronizarToggleActivo(metodo.id, metodo.activo);

        form.onsubmit = guardarMetodo;
    }

    function configurarToggleBuscador(form) {
        const modos = [
            {
                name: 'restriccion_categoria_modo',
                wrapper: form.querySelector('.buscador-wrapper[data-tipo="categoria"]'),
                tipoKey: 'categorias'
            },
            {
                name: 'restriccion_producto_modo',
                wrapper: form.querySelector('.buscador-wrapper[data-tipo="producto"]'),
                tipoKey: 'productos'
            }
        ];

        modos.forEach(({ name, wrapper, tipoKey }) => {
            if (!wrapper) return;

            const radios = form.querySelectorAll(`[name="${name}"]`);
            if (!radios.length) return;

            const getValor = () => {
                const checked = form.querySelector(`[name="${name}"]:checked`);
                return checked ? checked.value : 'ninguno';
            };

            const actualizar = () => {
                const modo = getValor();
                const habilitado = modo === 'al_menos_uno';
                const input = wrapper.querySelector('.buscador-input');
                const chips = wrapper.querySelector('.chips-container');
                const hidden = wrapper.querySelector('.hidden-ids');

                if (habilitado) {
                    wrapper.classList.remove('disabled');
                    if (input) input.disabled = false;
                    if (chips) chips.style.opacity = '1';
                    if (hidden) hidden.disabled = false;
                } else {
                    wrapper.classList.add('disabled');
                    if (input) {
                        input.disabled = true;
                        input.value = '';
                    }
                    if (chips) chips.style.opacity = '0.4';
                    seleccionados[tipoKey] = [];
                    if (chips) chips.innerHTML = '';
                    if (hidden) hidden.value = '[]';
                }
            };

            radios.forEach(radio => radio.addEventListener('change', actualizar));
            actualizar(); 
        });
    }

    function inicializarBuscador(form, tipo, idsIniciales) {
        const wrapper = form.querySelector(`.buscador-wrapper[data-tipo="${tipo}"]`);
        if (!wrapper) return;

        const input = wrapper.querySelector('.buscador-input');
        const resultados = wrapper.querySelector('.buscador-resultados');
        const chips = wrapper.querySelector('.chips-container');
        const hiddenIds = wrapper.querySelector('.hidden-ids');

        if (idsIniciales.length > 0) {
            cargarItemsIniciales(tipo, idsIniciales).then(items => {
                seleccionados[tipo === 'categoria' ? 'categorias' : 'productos'] = items;
                renderChips();
            });
        }

        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 2) {
                resultados.classList.remove('show');
                return;
            }

            timeout = setTimeout(() => buscarItems(query), 300);
        });

        input.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                resultados.classList.add('show');
            }
        });

        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                resultados.classList.remove('show');
            }
        });

        function buscarItems(query) {
            const opcion = tipo === 'categoria' ? 'BuscarCategorias' : 'BuscarProductos';

            fetch(API, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ opcion, query })
            })
            .then(r => r.json())
            .then(data => {
                if (data.respuesta !== 'ok') {
                    resultados.innerHTML = '<div class="buscador-empty">Error al buscar</div>';
                    resultados.classList.add('show');
                    return;
                }

                const items = tipo === 'categoria' ? data.categorias : data.productos;
                const seleccionadosIds = (tipo === 'categoria' ? seleccionados.categorias : seleccionados.productos)
                    .map(i => i.id);

                if (items.length === 0) {
                    resultados.innerHTML = `<div class="buscador-empty">No se encontraron ${tipo}s con "${query}"</div>`;
                } else {
                    resultados.innerHTML = items.map(item => {
                        const yaSeleccionado = seleccionadosIds.includes(item.id);
                        const meta = tipo === 'producto' && item.sku 
                            ? `<span class="item-meta">SKU: ${item.sku}</span>` 
                            : '';
                        
                        return `
                            <div class="buscador-item ${yaSeleccionado ? 'ya-seleccionado' : ''}" 
                                 data-id="${item.id}" 
                                 data-nombre="${item.nombre}"
                                 data-sku="${item.sku || ''}">
                                <div>
                                    <div class="item-nombre">${item.nombre}</div>
                                    ${meta}
                                </div>
                                ${yaSeleccionado ? '<i class="bi bi-check-circle-fill item-check"></i>' : '<i class="bi bi-plus-circle item-check"></i>'}
                            </div>
                        `;
                    }).join('');
                }

                resultados.classList.add('show');
                resultados.querySelectorAll('.buscador-item:not(.ya-seleccionado)').forEach(el => {
                    el.addEventListener('click', function() {
                        const item = {
                            id: parseInt(this.dataset.id),
                            nombre: this.dataset.nombre,
                            sku: this.dataset.sku || null
                        };

                        if (tipo === 'categoria') {
                            if (!seleccionados.categorias.find(c => c.id === item.id)) {
                                seleccionados.categorias.push(item);
                            }
                        } else {
                            if (!seleccionados.productos.find(p => p.id === item.id)) {
                                seleccionados.productos.push(item);
                            }
                        }

                        renderChips();
                        input.value = '';
                        resultados.classList.remove('show');
                    });
                });
            })
            .catch(err => {
                resultados.innerHTML = '<div class="buscador-empty">Error de conexión</div>';
                resultados.classList.add('show');
            });
        }

        function renderChips() {
            const lista = tipo === 'categoria' ? seleccionados.categorias : seleccionados.productos;
            
            chips.innerHTML = lista.map(item => `
                <span class="chip" data-id="${item.id}">
                    ${item.nombre}
                    ${item.sku ? `<span class="chip-sku">(${item.sku})</span>` : ''}
                    <button type="button" class="chip-remove" data-id="${item.id}">×</button>
                </span>
            `).join('');

            hiddenIds.value = JSON.stringify(lista.map(i => i.id));
            chips.querySelectorAll('.chip-remove').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const id = parseInt(this.dataset.id);
                    
                    if (tipo === 'categoria') {
                        seleccionados.categorias = seleccionados.categorias.filter(c => c.id !== id);
                    } else {
                        seleccionados.productos = seleccionados.productos.filter(p => p.id !== id);
                    }
                    renderChips();
                });
            });
        }
    }

    async function cargarItemsIniciales(tipo, ids) {
        const opcion = tipo === 'categoria' ? 'ObtenerCategoriasPorIds' : 'ObtenerProductosPorIds';
        
        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ opcion, ids })
            });
            const data = await res.json();
            
            if (data.respuesta === 'ok') {
                return tipo === 'categoria' ? data.categorias : data.productos;
            }
        } catch (err) {
        }
        return [];
    }

    function inicializarCuentas(cuentas) {
        const tbody = document.getElementById('bodyCuentas');
        const empty = document.getElementById('emptyCuentas');
        const wrapperTabla = document.getElementById('wrapperTablaCuentas');
        const btnAgregar = document.getElementById('btnAgregarCuenta');
        const btnAgregarEmpty = document.getElementById('btnAgregarCuentaEmpty');
        const template = document.getElementById('filaCuentaTemplate');

        if (!tbody || !template) return;

        let contador = 0;

        function actualizarVista() {
            const hayFilas = tbody.querySelectorAll('.cuenta-row').length > 0;

            if (hayFilas) {
                if (wrapperTabla) wrapperTabla.style.display = '';
                if (empty) empty.style.display = 'none';
            } else {
                if (wrapperTabla) wrapperTabla.style.display = 'none';
                if (empty) empty.style.display = '';
            }
        }

        function agregarFila(cuenta = {}) {
            const html = template.innerHTML.replace(/__INDEX__/g, contador++);
            const temp = document.createElement('tbody');
            temp.innerHTML = html.trim();
            const fila = temp.firstChild;

            if (cuenta.nombre_cuenta) {
                fila.querySelector('[name*="[nombre_cuenta]"]').value = cuenta.nombre_cuenta;
            }
            if (cuenta.numero_cuenta) {
                fila.querySelector('[name*="[numero_cuenta]"]').value = cuenta.numero_cuenta;
            }
            if (cuenta.nombre_banco) {
                fila.querySelector('[name*="[nombre_banco]"]').value = cuenta.nombre_banco;
            }
            if (cuenta.cci) {
                fila.querySelector('[name*="[cci]"]').value = cuenta.cci;
            }

            const btnCopiar = fila.querySelector('.btn-copiar-cuenta');
            if (btnCopiar) {
                btnCopiar.addEventListener('click', function() {
                    const input = fila.querySelector('[name*="[numero_cuenta]"]');
                    if (!input || !input.value.trim()) {
                        return;
                    }

                    navigator.clipboard.writeText(input.value.trim()).then(() => {
                        mostrarNotificacionCopiado('Número copiado');
                    }).catch(() => {
                        input.select();
                        document.execCommand('copy');
                        mostrarNotificacionCopiado('Número copiado');
                    });
                });
            }

            fila.querySelector('.btn-eliminar-cuenta').addEventListener('click', function() {
                fila.style.opacity = '0';
                fila.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    fila.remove();
                    actualizarVista();
                }, 200);
            });

            tbody.appendChild(fila);
            actualizarVista();
        }

        if (btnAgregar) {
            btnAgregar.addEventListener('click', () => agregarFila());
        }

        if (btnAgregarEmpty) {
            btnAgregarEmpty.addEventListener('click', () => agregarFila());
        }

        if (cuentas && cuentas.length > 0) {
            cuentas.forEach(c => agregarFila(c));
        } else {
            actualizarVista();
        }
    }

    function mostrarNotificacionCopiado(mensaje) {
        let notif = document.getElementById('copyNotification');
        if (!notif) {
            notif = document.createElement('div');
            notif.id = 'copyNotification';
            notif.className = 'copy-notification';
            document.body.appendChild(notif);
        }
        notif.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${mensaje}`;
        notif.classList.add('show');

        setTimeout(() => {
            notif.classList.remove('show');
        }, 2000);
    }

    async function guardarMetodo(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const statusMessages = showPreloader('Guardando cambios...', 'editar');

        ['categorias_desactivar', 'productos_desactivar'].forEach(name => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) {
                try {
                    const ids = JSON.parse(input.value || '[]');
                    formData.delete(name);
                    ids.forEach(id => formData.append(`${name}[]`, id));
                } catch (e) {
                }
            }
        });

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: formData
            });
            const data = await res.json();
            hidePreloader(statusMessages);

            if (data.respuesta === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: data.mensaje,
                    timer: 1500,
                    showConfirmButton: false,
                });
            } else {
                if (data.errores) {
                    const listaErrores = Object.values(data.errores).flat().join('<br>');
                    Swal.fire({
                        icon: 'error',
                        title: 'Errores de validación',
                        html: listaErrores,
                    });
                } else {
                    Swal.fire('Error', data.mensaje || 'Error al guardar', 'error');
                }
            }
        } catch (err) {
            hidePreloader(statusMessages);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }

    document.querySelectorAll('.toggle-activo').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const id = this.dataset.id;

            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ opcion: 'ToggleActivo', id })
                });
                const data = await res.json();

                if (data.respuesta === 'ok') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                    Toast.fire({ icon: 'success', title: data.mensaje });
                    sincronizarToggleActivo(id, data.activo);
                } else {
                    this.checked = !this.checked;
                    Swal.fire('Error', data.mensaje, 'error');
                }
            } catch (err) {
                this.checked = !this.checked;
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    });

    function inicializarUpload(form, tipo) {
        const config = {
            qr: {
                area: form.querySelector('#uploadQR'),
                preview: form.querySelector('#previewQR'),
                hidden: form.querySelector('[name="imagen_qr"]'),
                inputName: 'imagen_qr_file',
                tipo: 'qr',
                maxSize: 2 * 1024 * 1024,
                renderPreview: (url) => `
                    <div class="qr-preview-card">
                        <button type="button" class="qr-preview-remove" title="Eliminar imagen">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <img src="${url}" alt="Código QR">
                        <div class="qr-preview-badge">
                            <i class="bi bi-check-circle-fill"></i> Imagen cargada
                        </div>
                    </div>
                `,
                previewClass: 'qr-preview'
            },
            icono: {
                area: form.querySelector('#uploadIcono'),
                preview: form.querySelector('#previewIcono'),
                hidden: form.querySelector('[name="icono_imagen"]'),
                inputName: 'icono_imagen_file',
                tipo: 'icono',
                maxSize: 1 * 1024 * 1024,
                renderPreview: (url) => `
                    <div class="icono-preview">
                        <img src="${url}" alt="Icono">
                        <span class="icono-info">
                            <i class="bi bi-check-circle-fill"></i> Icono cargado
                        </span>
                        <button type="button" class="icono-remove" title="Eliminar icono">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                `,
                previewClass: 'icono-preview'
            }
        }[tipo];

        if (!config || !config.area) return;

        const inputFile = config.area.querySelector('input[type="file"]');
        if (!inputFile) return;

        config.area.addEventListener('click', (e) => {
            if (e.target.closest('.qr-preview-remove, .icono-remove')) return;
            inputFile.click();
        });

        ['dragenter', 'dragover'].forEach(evt => {
            config.area.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                config.area.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            config.area.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                config.area.classList.remove('dragover');
            });
        });

        config.area.addEventListener('drop', (e) => {
            const file = e.dataTransfer.files?.[0];
            if (file) subirImagen(file);
        });

        inputFile.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (file) subirImagen(file);
        });

        if (config.preview) {
            config.preview.addEventListener('click', (e) => {
                if (e.target.closest('.qr-preview-remove, .icono-remove')) {
                    e.preventDefault();
                    config.hidden.value = '';
                    config.preview.innerHTML = '';
                    config.area.classList.remove('has-file');
                    inputFile.value = '';
                }
            });
        }

        async function subirImagen(file) {
            if (file.size > config.maxSize) {
                const maxMB = (config.maxSize / 1024 / 1024).toFixed(0);
                Swal.fire('Error', `La imagen no debe superar ${maxMB}MB`, 'error');
                return;
            }

            if (!file.type.startsWith('image/')) {
                Swal.fire('Error', 'El archivo debe ser una imagen', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('imagen', file);
            formData.append('tipo', config.tipo);
            formData.append('opcion', 'SubirImagen');
            const form = document.getElementById('formMetodo');
            const metodoId = form?.querySelector('[name="id"]')?.value;
            if (metodoId) {
                formData.append('metodo_id', metodoId);
            }

            const statusMessages = showPreloader('Subiendo imagen...', 'editar');

            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                const data = await res.json();
                hidePreloader(statusMessages);

                if (data.respuesta === 'ok') {
                    config.hidden.value = data.path;
                    config.area.classList.add('has-file');
                    if (config.preview) {
                        config.preview.innerHTML = config.renderPreview(data.url);
                    }

                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                    Toast.fire({ icon: 'success', title: 'Imagen subida correctamente' });
                } else {
                    Swal.fire('Error', data.mensaje || 'Error al subir', 'error');
                }
            } catch (err) {
                hidePreloader(statusMessages);
                Swal.fire('Error', 'Error al subir la imagen', 'error');
            }
        }
    }
    function actualizarContadorActivos() {
        const activos = document.querySelectorAll('.toggle-activo:checked').length;
        const totalEl = document.getElementById('totalActivos');
        if (totalEl) {
            totalEl.style.transform = 'scale(1.3)';
            totalEl.style.color = '#22c55e';
            setTimeout(() => {
                totalEl.textContent = activos;
                totalEl.style.transform = 'scale(1)';
                totalEl.style.color = '';
            }, 150);
        }
    }
    
    function sincronizarToggleActivo(id, activo) {
        const toggleSidebar = document.querySelector(`.toggle-activo[data-id="${id}"]`);
        if (toggleSidebar) {
            toggleSidebar.checked = activo;
        }

        const form = document.getElementById('formMetodo');
        if (form) {
            const formId = form.querySelector('[name="id"]');
            if (formId && parseInt(formId.value) === parseInt(id)) {
                const toggleHeader = form.querySelector('[name="activo"]');
                if (toggleHeader) {
                    toggleHeader.checked = activo;
                }
                const badge = form.querySelector('.estado-badge');
                if (badge) {
                    badge.textContent = activo ? 'Activo' : 'Inactivo';
                    badge.classList.toggle('activo', activo);
                }
            }
        }

        const item = document.querySelector(`.metodo-item-modern[data-id="${id}"]`);
        if (item) {
            const nombre = item.querySelector('.metodo-nombre-modern');
            const dot = nombre.querySelector('.dot-active');
            const icono = item.querySelector('.metodo-icono-modern');

            if (activo) {
                if (!dot) {
                    nombre.insertAdjacentHTML('beforeend', '<span class="dot-active" title="Activo"></span>');
                }
                if (icono) {
                    icono.style.background = 'linear-gradient(135deg, #F4AB28 0%, #f35b08 100%)';
                }
            } else {
                if (dot) dot.remove();
                if (icono) {
                    icono.style.background = 'linear-gradient(135deg, #707275 0%, #abacae 100%)';
                }
            }
        }

        actualizarContadorActivos();
    }

    function actualizarBadgeActivo(toggleHeader) {
        const form = toggleHeader.closest('form');
        const badge = form.querySelector('.estado-badge');
        if (badge) {
            badge.textContent = toggleHeader.checked ? 'Activo' : 'Inactivo';
            badge.classList.toggle('activo', toggleHeader.checked);
        }
    }
})();