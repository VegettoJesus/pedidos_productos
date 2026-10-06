'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const SUBTOTAL = parseFloat(document.getElementById('subtotalDisplay')?.dataset.subtotal || 0);
    const COSTO_ENVIO_ACTUAL = { valor: 0 };
    const METODOS_PAGO = JSON.parse(document.getElementById('metodosPagoData')?.textContent || '[]');

    // ==========================================
    // TOGGLE DELIVERY / RETIRO
    // ==========================================
    const radiosTipoEntrega = document.querySelectorAll('[name="tipo_entrega"]');
    radiosTipoEntrega.forEach(r => {
        r.addEventListener('change', () => {
            const esDelivery = document.querySelector('[name="tipo_entrega"]:checked').value === 'delivery';
            document.getElementById('deliveryFields').style.display = esDelivery ? 'block' : 'none';
            document.getElementById('retiroFields').style.display = esDelivery ? 'none' : 'block';

            recalcularTodo();   // 🔥 en vez de actualizarResumen(0)
        });
    });

    // ==========================================
    // PRE-CARGAR UBICACIÓN DEL CLIENTE
    // ==========================================
    const ubicacionValida = document.getElementById('ubicacionValida')?.value === '1';

    if (ubicacionValida) {
        const deptoId = document.getElementById('clienteDepartamento')?.value;
        const provId = document.getElementById('clienteProvincia')?.value;
        const distId = document.getElementById('clienteDistrito')?.value;
        const direccionCliente = document.getElementById('clienteDireccion')?.value;

        // Disparar carga de provincias del depto pre-seleccionado
        if (deptoId) {
            cargarProvincias(deptoId).then(() => {
                const provSelect = document.getElementById('provinciaSelect');
                if (provSelect && provId) {
                    provSelect.value = provId;
                    provSelect.dispatchEvent(new Event('change'));

                    // Esperar un tick para que se carguen distritos
                    setTimeout(() => {
                        const distSelect = document.getElementById('distritoSelect');
                        if (distSelect && distId) {
                            distSelect.value = distId;
                            distSelect.dispatchEvent(new Event('change'));
                        }
                    }, 300);
                }
            });
        }

        // Pre-llenar dirección
        const dirInput = document.getElementById('direccionEntrega');
        if (dirInput && direccionCliente) {
            dirInput.value = direccionCliente;
        }
    }

    // ==========================================
    // CARGAR PROVINCIAS
    // ==========================================
    async function cargarProvincias(deptoId) {
        const provincia = document.getElementById('provinciaSelect');
        const distrito = document.getElementById('distritoSelect');

        provincia.innerHTML = '<option value="">Seleccione</option>';
        distrito.innerHTML = '<option value="">Seleccione</option>';
        distrito.disabled = true;

        if (!deptoId) {
            provincia.disabled = true;
            return;
        }

        provincia.disabled = false;
        const res = await fetch(`/get-provincias/${deptoId}`);
        const data = await res.json();

        data.forEach(p => {
            provincia.innerHTML += `<option value="${p.id}">${p.nombre}</option>`;
        });
    }

    // ==========================================
    // CARGAR DISTRITOS
    // ==========================================
    async function cargarDistritos(provId) {
        const distrito = document.getElementById('distritoSelect');
        distrito.innerHTML = '<option value="">Seleccione</option>';

        if (!provId) {
            distrito.disabled = true;
            return;
        }

        distrito.disabled = false;
        const res = await fetch(`/get-distritos/${provId}`);
        const data = await res.json();

        data.forEach(d => {
            distrito.innerHTML += `<option value="${d.id}" data-costo="${d.costo_envio}">${d.nombre}</option>`;
        });
    }

    // ==========================================
    // EVENTOS DE LOS SELECTS
    // ==========================================
    document.getElementById('departamentoSelect')?.addEventListener('change', function () {
        cargarProvincias(this.value);
        recalcularTodo();
    });

    document.getElementById('provinciaSelect')?.addEventListener('change', function () {
        cargarDistritos(this.value);
        recalcularTodo();
    });

    document.getElementById('distritoSelect')?.addEventListener('change', function () {
        recalcularTodo();
    });

    // ==========================================
    // 🔥 RECALCULAR ENVÍO SEGÚN REGLAS
    // ==========================================
    function recalcularTodo() {
        const tipoEntrega = document.querySelector('[name="tipo_entrega"]:checked')?.value;
        const metodoRadio = document.querySelector('[name="tipo_pago"]:checked');

        // 1. ENVÍO (solo delivery)
        let costoEnvio = 0;
        if (tipoEntrega === 'delivery') {
            const distSelect = document.getElementById('distritoSelect');
            const distOpt = distSelect?.options[distSelect.selectedIndex];
            costoEnvio = parseFloat(distOpt?.dataset.costo || 0);
        }

        // 2. CARGO del método de pago (base + impuesto)
        let cargoBase = 0;
        let impuestoCargo = 0;
        let totalCargo = 0;
        let tasaImpuestoCargo = 0;

        if (metodoRadio) {
            const label = metodoRadio.closest('.metodo-pago-label');
            cargoBase = parseFloat(label?.dataset.cargoBase || 0);
            impuestoCargo = parseFloat(label?.dataset.impuestoCargo || 0);
            totalCargo = parseFloat(label?.dataset.totalCargo || 0);

            // Obtener la tasa de impuesto del JSON
            const metodoData = METODOS_PAGO.find(m => m.slug === metodoRadio.value);
            const config = metodoData?.configuracion || {};
            const cargo = config.cargo_adicional || {};
            if (cargo.impuesto_activo) {
                tasaImpuestoCargo = parseFloat(cargo.impuesto_porcentaje || 0);
            }
        }

        // 3. ACTUALIZAR UI
        document.getElementById('envioDisplay').textContent = `S/ ${costoEnvio.toFixed(2)}`;

        // Cargo base
        const cargoLinea = document.getElementById('cargoLinea');
        if (cargoBase > 0) {
            cargoLinea.style.display = 'flex';
            document.getElementById('cargoDisplay').textContent = `S/ ${cargoBase.toFixed(2)}`;
        } else {
            cargoLinea.style.display = 'none';
        }

        // Impuesto del cargo
        const impuestoLinea = document.getElementById('impuestoCargoLinea');
        if (impuestoCargo > 0) {
            impuestoLinea.style.display = 'flex';
            document.getElementById('impuestoCargoDisplay').textContent = `S/ ${impuestoCargo.toFixed(2)}`;
            document.getElementById('tasaImpuestoCargo').textContent = `(${tasaImpuestoCargo}%)`;
        } else {
            impuestoLinea.style.display = 'none';
        }

        // Total
        const total = SUBTOTAL + costoEnvio + totalCargo;
        document.getElementById('totalDisplay').textContent = `S/ ${total.toFixed(2)}`;
    }

    // ==========================================
    // ACTUALIZAR RESUMEN (envío + total)
    // ==========================================
    function actualizarResumen(costoEnvio) {
        const envioEl = document.getElementById('envioDisplay');
        const totalEl = document.getElementById('totalDisplay');

        envioEl.textContent = `S/ ${costoEnvio.toFixed(2)}`;
        const total = SUBTOTAL + costoEnvio;
        totalEl.textContent = `S/ ${total.toFixed(2)}`;
    }

    // ==========================================
    // 🔥 MOSTRAR INFO DEL MÉTODO DE PAGO SELECCIONADO
    // ==========================================
    function mostrarInfoMetodoPago(slug) {
        // Ocultar todos los paneles
        document.querySelectorAll('.metodo-pago-info').forEach(el => {
            el.style.display = 'none';
            el.innerHTML = '';
        });

        // Quitar borde activo
        document.querySelectorAll('.metodo-pago-label').forEach(el => {
            el.style.borderColor = '#eee';
            el.style.background = 'transparent';
        });

        const label = document.querySelector(`.metodo-pago-label[data-slug="${slug}"]`);
        if (label) {
            label.style.borderColor = '#f4ab27';
            label.style.background = 'rgba(244,171,39,0.03)';
        }

        const metodo = METODOS_PAGO.find(m => m.slug === slug);
        if (!metodo) return;

        const infoDiv = document.querySelector(`.metodo-pago-info[data-slug="${slug}"]`);
        if (!infoDiv) return;

        const config = metodo.configuracion || {};
        let html = '';

        // CONTRA ENTREGA
        if (slug === 'contra_entrega') {
            if (config.descripcion) {
                html += `<p style="margin:0 0 0.5rem;color:#666;font-size:0.88rem;">${escapeHtml(config.descripcion)}</p>`;
            }
            if (config.instrucciones) {
                html += `<div style="background:#f8f9fa;padding:0.7rem;border-radius:8px;font-size:0.85rem;margin-top:0.5rem;">
                    <i class="bi bi-info-circle"></i> ${escapeHtml(config.instrucciones)}
                </div>`;
            }
        }

        // TRANSFERENCIA
        if (slug === 'transferencia') {
            if (config.descripcion) {
                html += `<p style="margin:0 0 0.5rem;color:#666;font-size:0.88rem;">${escapeHtml(config.descripcion)}</p>`;
            }
            if (config.instrucciones) {
                html += `<div style="background:#f8f9fa;padding:0.7rem;border-radius:8px;font-size:0.85rem;margin-bottom:0.5rem;">
                    ${escapeHtml(config.instrucciones)}
                </div>`;
            }
            const cuentas = Array.isArray(config.cuentas) ? config.cuentas : [];
            if (cuentas.length > 0) {
                html += '<div style="font-size:0.85rem;">';
                cuentas.forEach(c => {
                    html += `<div style="background:#fff;border:1px solid #eee;border-radius:8px;padding:0.7rem;margin-bottom:0.5rem;">
                        <div><strong>Banco:</strong> ${escapeHtml(c.nombre_banco || '—')}</div>
                        <div><strong>Cuenta:</strong> ${escapeHtml(c.numero_cuenta || '—')}</div>
                        <div><strong>Titular:</strong> ${escapeHtml(c.nombre_cuenta || '—')}</div>
                        ${c.cci ? `<div><strong>CCI:</strong> ${escapeHtml(c.cci)}</div>` : ''}
                    </div>`;
                });
                html += '</div>';
            }
        }

        // QR
        if (slug === 'qr') {
            // 🔥 Título dinámico
            if (config.titulo) {
                html += `<div style="font-weight:700;margin-bottom:0.5rem;">${escapeHtml(config.titulo)}</div>`;
            }
            if (config.descripcion) {
                html += `<p style="margin:0 0 0.5rem;color:#666;font-size:0.88rem;">${escapeHtml(config.descripcion)}</p>`;
            }
            if (config.mensaje_emergente) {
                html += `<div style="background:#fff7e6;padding:0.7rem;border-radius:8px;font-size:0.85rem;margin-bottom:0.5rem;">
                    <i class="bi bi-exclamation-circle"></i> ${escapeHtml(config.mensaje_emergente)}
                </div>`;
            }
            if (config.imagen_qr) {
                html += `<div style="text-align:center;margin:0.5rem 0;">
                    <img src="/metodos_pago/${config.imagen_qr}" 
                        alt="QR" 
                        class="qr-zoomable"
                        data-qr-url="/metodos_pago/${config.imagen_qr}"
                        style="max-width:180px;border-radius:12px;border:1px solid #eee;cursor:zoom-in;transition:transform 0.2s;">
                    <div style="font-size:0.75rem;color:#999;margin-top:0.4rem;">
                        <i class="bi bi-zoom-in"></i> Toca la imagen para ampliar
                    </div>
                </div>`;
            }
            if (config.telefono_afiliado) {
                html += `<div style="font-size:0.85rem;"><strong>Teléfono afiliado:</strong> ${escapeHtml(config.telefono_afiliado)}</div>`;
            }
            if (config.importe_limite) {
                html += `<div style="font-size:0.82rem;color:#999;margin-top:0.3rem;">
                    <i class="bi bi-info-circle"></i> Importe máximo: S/ ${parseFloat(config.importe_limite).toFixed(2)}
                </div>`;
            }
        }

        if (html) {
            infoDiv.innerHTML = html;
            infoDiv.style.display = 'block';
        }
    }

    // ==========================================
    // EVENTOS DE LOS MÉTODOS DE PAGO
    // ==========================================
    document.querySelectorAll('[name="tipo_pago"]').forEach(r => {
        r.addEventListener('change', function () {
            if (this.disabled) return;
            mostrarInfoMetodoPago(this.value);
            recalcularTodo();
        });
    });

    // Cuando cambia tipo entrega o distrito
    document.querySelectorAll('[name="tipo_entrega"]').forEach(r => {
        r.addEventListener('change', recalcularTodo);
    });
    document.getElementById('distritoSelect')?.addEventListener('change', recalcularTodo);

    // Mostrar info del primero al cargar
    const primerMetodo = document.querySelector('[name="tipo_pago"]:checked');
    if (primerMetodo) {
        mostrarInfoMetodoPago(primerMetodo.value);
    }

    // ==========================================
    // UTILIDADES
    // ==========================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ==========================================
    // SUBMIT
    // ==========================================
    document.getElementById('checkoutForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnConfirmar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';

        const formData = new FormData(e.target);

        try {
            const res = await fetch('/checkout/confirmar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await res.json();

            if (data.ok) {
                window.actualizarContadorCarrito();
                Swal.fire({
                    icon: 'success',
                    title: '¡Pedido creado!',
                    text: data.mensaje,
                    confirmButtonText: 'Ver pedido',
                }).then(() => {
                    window.location.href = data.redirect;
                });
            } else {
                Swal.fire('Error', data.mensaje || 'No se pudo procesar', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle"></i> Confirmar pedido';
            }
        } catch (err) {
            Swal.fire('Error', 'Error de conexión', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Confirmar pedido';
        }
    });

    // Recalcular al cargar (por si hay valores por defecto)
    recalcularTodo();
});

document.addEventListener('click', (e) => {
    const img = e.target.closest('.qr-zoomable');
    if (!img) return;

    e.preventDefault();
    e.stopPropagation();

    const url = img.dataset.qrUrl;
    if (!url) return;

    const modalEl = document.getElementById('qrZoomModal');
    const modalImg = document.getElementById('qrZoomImage');
    if (!modalEl || !modalImg) return;

    modalImg.src = url;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modalEl = document.getElementById('qrZoomModal');
        if (modalEl && modalEl.classList.contains('show')) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
    }
});
