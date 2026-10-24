'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const SUBTOTAL = parseFloat(document.getElementById('subtotalDisplay')?.dataset.subtotal || 0);
    const METODOS_PAGO = JSON.parse(document.getElementById('metodosPagoData')?.textContent || '[]');
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getMetodoPagoSeleccionado() {
        return document.querySelector('[name="tipo_pago"]:checked');
    }

    function getTipoEntrega() {
        return document.querySelector('[name="tipo_entrega"]:checked')?.value;
    }

    document.querySelectorAll('[name="tipo_entrega"]').forEach(r => {
        r.addEventListener('change', () => {
            const esDelivery = getTipoEntrega() === 'delivery';
            document.getElementById('deliveryFields').style.display = esDelivery ? 'block' : 'none';
            document.getElementById('retiroFields').style.display = esDelivery ? 'none' : 'block';
            recalcularTodo();
        });
    });

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

        try {
            const res = await fetch(`/get-provincias/${deptoId}`);
            const data = await res.json();
            data.forEach(p => {
                provincia.innerHTML += `<option value="${p.id}">${p.nombre}</option>`;
            });
        } catch (e) {
            console.error('Error cargando provincias:', e);
        }
    }

    async function cargarDistritos(provId, distritoSeleccionar = null) {
        const distrito = document.getElementById('distritoSelect');
        distrito.innerHTML = '<option value="">Seleccione</option>';

        if (!provId) {
            distrito.disabled = true;
            return;
        }

        distrito.disabled = false;

        try {
            const res = await fetch(`/get-distritos/${provId}`);
            const data = await res.json();

            data.forEach(d => {
                distrito.innerHTML += `<option value="${d.id}" data-costo="${d.costo_envio}">${d.nombre}</option>`;
            });

            if (distritoSeleccionar) {
                distrito.value = String(distritoSeleccionar);
            }

            recalcularTodo();

        } catch (e) {
            console.error('Error cargando distritos:', e);
        }
    }

    (async function precargarUbicacion() {
        const ubicacionValida = document.getElementById('ubicacionValida')?.value === '1';
        if (!ubicacionValida) return;

        const deptoId  = document.getElementById('clienteDepartamento')?.value;
        const provId   = document.getElementById('clienteProvincia')?.value;
        const distId   = document.getElementById('clienteDistrito')?.value;
        const direccionCliente = document.getElementById('clienteDireccion')?.value;

        if (!deptoId) return;

        await cargarProvincias(deptoId);

        const provSelect = document.getElementById('provinciaSelect');
        if (provSelect && provId) {
            provSelect.value = String(provId);
            await cargarDistritos(provId, distId); 
        }

        const dirInput = document.getElementById('direccionEntrega');
        if (dirInput && direccionCliente) {
            dirInput.value = direccionCliente;
        }
    })();

    document.getElementById('departamentoSelect')?.addEventListener('change', async function () {
        await cargarProvincias(this.value);
        const distrito = document.getElementById('distritoSelect');
        distrito.innerHTML = '<option value="">Seleccione</option>';
        distrito.disabled = true;
        recalcularTodo();
    });

    document.getElementById('provinciaSelect')?.addEventListener('change', async function () {
        await cargarDistritos(this.value); 
    });

    document.getElementById('distritoSelect')?.addEventListener('change', () => {
        recalcularTodo();
    });

    function recalcularTodo() {
        const tipoEntrega = getTipoEntrega();
        const metodoRadio = getMetodoPagoSeleccionado();

        let costoEnvio = 0;
        if (tipoEntrega === 'delivery') {
            const distSelect = document.getElementById('distritoSelect');
            const distOpt = distSelect?.options[distSelect.selectedIndex];
            costoEnvio = parseFloat(distOpt?.dataset.costo || 0);
        }

        let cargoBase = 0;
        let impuestoCargo = 0;
        let totalCargo = 0;
        let tasaImpuestoCargo = 0;

        if (metodoRadio) {
            const metodoData = METODOS_PAGO.find(m => m.slug === metodoRadio.value);
            if (metodoData && metodoData.disponible) {
                const cargo = metodoData.configuracion?.cargo_adicional || {};
                let baseCargo = SUBTOTAL; 
                let incluyeImpuestos = cargo.incluir_en_total?.impuestos === true;
                let incluyeEnvio = cargo.incluir_en_total?.envio === true;

                if (incluyeImpuestos) {
                    baseCargo += 0; 
                }
                if (incluyeEnvio) {
                    baseCargo += costoEnvio; 
                }

                const tipo = cargo.tipo || 'porcentaje';
                const valor = parseFloat(cargo.valor || 0);

                if (valor > 0) {
                    if (tipo === 'fijo') {
                        cargoBase = valor;
                    } else {
                        cargoBase = baseCargo * (valor / 100);
                    }
                }

                if (cargo.impuesto_activo && cargoBase > 0) {
                    tasaImpuestoCargo = parseFloat(cargo.impuesto_porcentaje || 0);
                    impuestoCargo = cargoBase * (tasaImpuestoCargo / 100);
                }

                totalCargo = cargoBase + impuestoCargo;
            }
        }

        document.getElementById('envioDisplay').textContent = `S/ ${costoEnvio.toFixed(2)}`;

        const cargoLinea = document.getElementById('cargoLinea');
        if (cargoBase > 0) {
            cargoLinea.style.display = 'flex';
            document.getElementById('cargoDisplay').textContent = `S/ ${cargoBase.toFixed(2)}`;
        } else {
            cargoLinea.style.display = 'none';
        }

        const impuestoLinea = document.getElementById('impuestoCargoLinea');
        if (impuestoCargo > 0) {
            impuestoLinea.style.display = 'flex';
            document.getElementById('impuestoCargoDisplay').textContent = `S/ ${impuestoCargo.toFixed(2)}`;
            document.getElementById('tasaImpuestoCargo').textContent = `(${tasaImpuestoCargo}%)`;
        } else {
            impuestoLinea.style.display = 'none';
        }

        const total = SUBTOTAL + costoEnvio + totalCargo;
        document.getElementById('totalDisplay').textContent = `S/ ${total.toFixed(2)}`;
        document.querySelectorAll('.badge-cargo').forEach(b => b.style.display = 'none');

        if (metodoRadio && totalCargo > 0) {
            const labelSeleccionado = metodoRadio.closest('.metodo-pago-label');
            let badge = labelSeleccionado.querySelector('.badge-cargo');
            
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge-cargo';
                badge.style = 'margin-left:auto;background:#f4ab27;color:#fff;font-size:0.75rem;padding:0.2rem 0.5rem;border-radius:20px;';
                labelSeleccionado.querySelector('.metodo-pago-header').appendChild(badge);
            }
            
            badge.textContent = `+ S/ ${totalCargo.toFixed(2)}`;
            badge.style.display = 'inline-block';
        }
    }
    
    function mostrarInfoMetodoPago(slug) {
        document.querySelectorAll('.metodo-pago-info').forEach(el => {
            el.style.display = 'none';
            el.innerHTML = '';
        });

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

        if (slug === 'qr') {
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

    document.querySelectorAll('[name="tipo_pago"]').forEach(r => {
        r.addEventListener('change', function () {
            if (this.disabled) return;
            mostrarInfoMetodoPago(this.value);
            recalcularTodo(); 
        });
    });

    const primerMetodo = getMetodoPagoSeleccionado();
    if (primerMetodo) {
        mostrarInfoMetodoPago(primerMetodo.value);
    }

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
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modalEl = document.getElementById('qrZoomModal');
        if (modalEl && modalEl.classList.contains('show')) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
    }
});