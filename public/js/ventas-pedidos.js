const CSRF = document.querySelector('meta[name="csrf-token"]').content;

document.addEventListener('click', function(e) {
	const imgTrigger = e.target.closest('.img-evidencia-zoom');

	if (imgTrigger) {
		e.preventDefault();

		const imgSrc = imgTrigger.getAttribute('data-img-src');
		const modalImg = document.getElementById('imgZoomEvidencia');

		if (modalImg && imgSrc) {
			modalImg.classList.remove('zoomed');
			modalImg.src = imgSrc;
			const modalEl = document.getElementById('modalZoomEvidencia');
			const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
			modal.show();
		}
	}
});

document.addEventListener('DOMContentLoaded', () => {
	establecerFechasPorDefecto();

	cargarPedidos();

	document.getElementById('btnFiltrar').addEventListener('click', cargarPedidos);

	document.addEventListener('show.bs.modal', function(event) {
		const modalQueSeAbre = event.target;
		const modalDetalleEl = document.getElementById('modalDetalle');

		if (modalQueSeAbre.id !== 'modalDetalle' && modalDetalleEl && modalDetalleEl.classList.contains('show')) {
			document.body.dataset.reabrirDetalle = '1';
			bootstrap.Modal.getInstance(modalDetalleEl).hide();
		}
	});

	document.addEventListener('hidden.bs.modal', function(event) {
		const modalQueSeCerro = event.target;

		if (modalQueSeCerro.id !== 'modalDetalle' && document.body.dataset.reabrirDetalle === '1') {
			document.body.dataset.reabrirDetalle = '0';

			const modalDetalleEl = document.getElementById('modalDetalle');
			if (modalDetalleEl) {
				bootstrap.Modal.getOrCreateInstance(modalDetalleEl).show();
			}
		}
	});

	let timeoutId;
	document.getElementById('filtroBuscar').addEventListener('input', () => {
		clearTimeout(timeoutId);
		timeoutId = setTimeout(cargarPedidos, 400);
	});

	document.getElementById('filtroEstado').addEventListener('change', cargarPedidos);
	document.getElementById('btnConfirmarReembolso').addEventListener('click', async function() {
		const pedidoId = document.getElementById('reembolsoPedidoId').value;
		const motivo = document.getElementById('reembolsoMotivo').value.trim();

		if (!motivo) {
			Swal.fire('Atención', 'Debes ingresar un motivo para el reembolso.', 'warning');
			return;
		}

		const itemsData = [];
		document.querySelectorAll('#reembolsoItemsList .reembolso-item').forEach(el => {
			const itemId = parseInt(el.dataset.itemId);
			const maxMonto = parseFloat(el.dataset.max);
			let cantidad = parseInt(el.querySelector('.reembolso-cantidad').value) || 0;
			let monto = parseFloat(el.querySelector('.reembolso-monto').value) || 0;

			if (cantidad <= 0) return;
			if (monto > maxMonto) monto = maxMonto;
			if (monto < 0) monto = 0;

			if (monto > 0) {
				itemsData.push({
					item_id: itemId,
					cantidad,
					monto
				});
			}
		});

		if (itemsData.length === 0) {
			Swal.fire('Atención', 'Selecciona al menos un item para reembolsar (cantidad mayor a 0).', 'warning');
			return;
		}

		let montoEnvio = 0;
		const envioCheck = document.getElementById('reembolsoIncluirEnvio');
		if (envioCheck && envioCheck.checked) {
			const envioInput = document.getElementById('reembolsoMontoEnvio');
			montoEnvio = parseFloat(envioInput.value) || 0;
			const maxEnvio = parseFloat(envioInput.max) || 0;
			if (montoEnvio > maxEnvio) montoEnvio = maxEnvio;
		}

		const totalReembolso = itemsData.reduce((s, i) => s + i.monto, 0) + montoEnvio;

		const confirm = await Swal.fire({
			title: '¿Confirmar reembolso?',
			html: `Total a reembolsar: <strong>S/ ${totalReembolso.toFixed(2)}</strong>`,
			icon: 'question',
			showCancelButton: true,
			confirmButtonColor: '#dc3545',
			confirmButtonText: 'Sí, reembolsar',
			cancelButtonText: 'Cancelar'
		});

		if (!confirm.isConfirmed) return;

		const btn = this;
		btn.disabled = true;
		btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';
		const statusMessages = showPreloader('Procesando Reembolso...', 'registrar');

		try {
			const res = await fetch('/Ventas/pedidos', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': CSRF
				},
				body: JSON.stringify({
					opcion: 'Reembolsar',
					pedido_id: pedidoId,
					items: itemsData,
					monto_envio: montoEnvio,
					motivo,
				})
			});
			const data = await res.json();
			hidePreloader(statusMessages);

			if (data.respuesta === 'success') {
				bootstrap.Modal.getInstance(document.getElementById('modalReembolso')).hide();
				Swal.fire({
					icon: 'success',
					title: 'Reembolso registrado',
					text: data.mensaje,
					timer: 2000,
					showConfirmButton: false
				});
				verDetalle(pedidoId);
				cargarPedidos();
			} else {
				Swal.fire('Error', data.mensaje, 'error');
			}
		} catch (err) {
			hidePreloader(statusMessages);
			Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
		} finally {
			btn.disabled = false;
			btn.innerHTML = '<i class="bi bi-cash-coin"></i> Confirmar Reembolso';
		}
	});
});

async function cargarPedidos(page = 1) {
	const statusMessages = showPreloader('Cargando Pedidos...', 'cargar');

	const filtros = {
		opcion: 'Listar',
		page: page,
		estado: document.getElementById('filtroEstado').value,
		buscar: document.getElementById('filtroBuscar').value,
		fecha_desde: document.getElementById('fechaDesde').value,
		fecha_hasta: document.getElementById('fechaHasta').value,
	};

	try {
		const res = await fetch('/Ventas/pedidos', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF
			},
			body: JSON.stringify(filtros)
		});
		const data = await res.json();

		const tbody = document.getElementById('tbodyPedidos');
		tbody.innerHTML = '';

		document.getElementById('badgeTotal').textContent = `${data.pagination.total} pedidos`;

		if (data.data.length === 0) {
			tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No se encontraron pedidos
                    </td>
                </tr>
            `;
			renderizarPaginacion(0, 0, 0, 0);
			return;
		}

		const coloresEstado = {
			pendiente: 'warning',
			pago_en_revision: 'info',
			pagado: 'success',
			en_preparacion: 'info',
			enviado: 'primary',
			entregado: 'success',
			cancelado: 'danger',
			reembolsado: 'secondary',
			rechazado: 'danger',
		};

		data.data.forEach(p => {
			const estadoClass = coloresEstado[p.estado] || 'secondary';

			let botonWhatsapp = '';
			if (p.cliente_celular) {
				let numeroLimpio = p.cliente_celular.replace(/\D/g, '');

				if (numeroLimpio.length === 9) {
					numeroLimpio = '51' + numeroLimpio;
				}

				if (numeroLimpio.length > 0) {
					botonWhatsapp = `
                        <a href="https://wa.me/${numeroLimpio}" target="_blank" class="btn btn-sm btn-success ms-1" title="Enviar WhatsApp a ${p.cliente_nombres}">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    `;
				}
			}

			tbody.innerHTML += `
                <tr>
                    <td><strong>${p.codigo}</strong></td>
                    <td>${p.cliente_nombres} ${p.cliente_apellidos}</td>
                    <td>S/ ${parseFloat(p.total).toFixed(2)}</td>
                    <td>${p.metodo_pago_nombre}</td>
                    <td><span class="badge bg-${estadoClass}">${p.estado.replace(/_/g, ' ')}</span></td>
                    <td>${new Date(p.created_at).toLocaleDateString('es-ES')}</td>
                    <td class="text-nowrap">
                        ${botonWhatsapp}
                        <button class="btn btn-sm btn-primary ms-1" onclick="verDetalle(${p.id})" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
		});

		renderizarPaginacion(
			data.pagination.current_page,
			data.pagination.last_page,
			data.pagination.total,
			data.pagination.per_page
		);

	} catch (error) {
		hidePreloader(statusMessages);
		Swal.fire('Error', 'No se pudieron cargar los pedidos', 'error');
	} finally {
		hidePreloader(statusMessages);
	}
}

function renderizarPaginacion(currentPage, lastPage, total, perPage) {
	const controles = document.getElementById('paginacionControles');
	const info = document.getElementById('paginacionInfo');

	if (!controles || !info) return;

	controles.innerHTML = '';

	if (total === 0) {
		info.textContent = 'Mostrando 0 de 0 pedidos';
		return;
	}

	const start = (currentPage - 1) * perPage + 1;
	const end = Math.min(currentPage * perPage, total);
	info.textContent = `Mostrando ${start} - ${end} de ${total} pedidos`;

	const prevLi = document.createElement('li');
	prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
	prevLi.innerHTML = `<button class="page-link" onclick="cargarPedidos(${currentPage - 1})">&laquo;</button>`;
	controles.appendChild(prevLi);

	let startPage = Math.max(1, currentPage - 2);
	let endPage = Math.min(lastPage, currentPage + 2);

	if (startPage > 1) {
		const firstLi = document.createElement('li');
		firstLi.className = 'page-item';
		firstLi.innerHTML = `<button class="page-link" onclick="cargarPedidos(1)">1</button>`;
		controles.appendChild(firstLi);
		if (startPage > 2) {
			const dotsLi = document.createElement('li');
			dotsLi.className = 'page-item disabled';
			dotsLi.innerHTML = `<span class="page-link">...</span>`;
			controles.appendChild(dotsLi);
		}
	}

	for (let i = startPage; i <= endPage; i++) {
		const li = document.createElement('li');
		li.className = `page-item ${i === currentPage ? 'active' : ''}`;
		li.innerHTML = `<button class="page-link" onclick="cargarPedidos(${i})">${i}</button>`;
		controles.appendChild(li);
	}

	if (endPage < lastPage) {
		if (endPage < lastPage - 1) {
			const dotsLi = document.createElement('li');
			dotsLi.className = 'page-item disabled';
			dotsLi.innerHTML = `<span class="page-link">...</span>`;
			controles.appendChild(dotsLi);
		}
		const lastLi = document.createElement('li');
		lastLi.className = 'page-item';
		lastLi.innerHTML = `<button class="page-link" onclick="cargarPedidos(${lastPage})">${lastPage}</button>`;
		controles.appendChild(lastLi);
	}

	const nextLi = document.createElement('li');
	nextLi.className = `page-item ${currentPage === lastPage ? 'disabled' : ''}`;
	nextLi.innerHTML = `<button class="page-link" onclick="cargarPedidos(${currentPage + 1})">&raquo;</button>`;
	controles.appendChild(nextLi);
}

async function verDetalle(id) {
	const statusMessages = showPreloader('Cargando Detalle...', 'cargar');
	try {
		const res = await fetch('/Ventas/pedidos', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF
			},
			body: JSON.stringify({
				opcion: 'Detalle',
				id
			})
		});
		const data = await res.json();

		if (data.respuesta === 'ok') {
			window._pedidoActual = data.pedido;
			renderDetallePedido(data.pedido, data.permisos);
			new bootstrap.Modal(document.getElementById('modalDetalle')).show();
		} else {
			Swal.fire('Error', data.mensaje || 'No se pudo cargar el detalle', 'error');
		}
	} catch (error) {
		Swal.fire('Error', 'Error de conexión', 'error');
	} finally {
		hidePreloader(statusMessages);
	}
}

function renderDetallePedido(pedido, permisos) {
	const estados = [{
		value: 'pendiente',
		label: 'Pendiente',
		color: 'warning',
		icon: 'bi-clock'
	}, {
		value: 'pago_en_revision',
		label: 'Pago en revisión',
		color: 'info',
		icon: 'bi-hourglass-split'
	}, {
		value: 'pagado',
		label: 'Pagado',
		color: 'success',
		icon: 'bi-check-circle'
	}, {
		value: 'en_preparacion',
		label: 'En preparación',
		color: 'info',
		icon: 'bi-box-seam'
	}, {
		value: 'enviado',
		label: 'Enviado',
		color: 'primary',
		icon: 'bi-truck'
	}, {
		value: 'entregado',
		label: 'Entregado',
		color: 'success',
		icon: 'bi-check-circle-fill'
	}, {
		value: 'cancelado',
		label: 'Cancelado',
		color: 'danger',
		icon: 'bi-x-circle'
	}, {
		value: 'reembolsado',
		label: 'Reembolsado',
		color: 'secondary',
		icon: 'bi-arrow-counterclockwise'
	}, {
		value: 'rechazado',
		label: 'Rechazado',
		color: 'danger',
		icon: 'bi-x-octagon'
	}, ];

	const estadoActual = estados.find(e => e.value === pedido.estado) || estados[0];

	const itemsHtml = pedido.items.map(item => {
				const imgSrc = item.imagen_path ?
					`/${item.imagen_path}` :
					'/img/default-product.png';

				const atributosHtml = item.atributos && item.atributos.length > 0 ?
					`<div class="item-atributos">${item.atributos.map(a => `<span class="chip-atributo"><strong>${a.atributo_nombre}:</strong> ${a.termino_nombre}</span>`).join('')}</div>`
            : '';

        const precioRegular = parseFloat(item.precio_unitario_regular || 0);
        const precioFinal = parseFloat(item.precio_unitario_final || 0);
        const tieneDescuento = precioRegular > precioFinal;

        const descuentoHtml = tieneDescuento ? `
            <div class="item-descuento" style="font-size: 0.75rem; color: #28a745; margin-top: 4px;">
                <i class="bi bi-tag-fill"></i> Dcto: S/ ${((precioRegular - precioFinal) * item.cantidad).toFixed(2)} (${Math.round(item.descuento_porcentaje)}%)
            </div>
        ` : '';

        const precioRegularHtml = tieneDescuento ? `
            <div class="unit" style="text-decoration: line-through; color: #999; font-size: 0.75rem;">
                S/ ${precioRegular.toFixed(2)} c/u
            </div>
        ` : '';

        let cantidadReembolsada = 0;
        if (pedido.reembolsos && pedido.reembolsos.length > 0) {
            pedido.reembolsos.forEach(reembolso => {
                let itemsArray = reembolso.items_reembolsados;
                if (typeof itemsArray === 'string') {
                    try { itemsArray = JSON.parse(itemsArray); } catch (e) {}
                }
                
                if (Array.isArray(itemsArray)) {
                    itemsArray.forEach(ri => {
                        if (parseInt(ri.item_id) === parseInt(item.id)) {
                            cantidadReembolsada += parseInt(ri.cantidad || 0);
                        }
                    });
                }
            });
        }

        const esReembolsoTotal = (cantidadReembolsada >= item.cantidad);
        const esReembolsoParcial = (cantidadReembolsada > 0 && cantidadReembolsada < item.cantidad);

        let badgeReembolsoHtml = '';
        if (esReembolsoTotal) {
            badgeReembolsoHtml = `<span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">Reembolsado</span>`;
        } else if (esReembolsoParcial) {
            badgeReembolsoHtml = `<span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem;">Reembolso Parcial (${cantidadReembolsada} de ${item.cantidad})</span>`;
        }

        return `
            <div class="product-line ${esReembolsoTotal ? 'reembolsado' : ''}">
                <div class="product-img">
                    <img src="${imgSrc}" alt="${item.nombre_producto}" onerror="this.src='/img/default-product.png'">
                </div>
                <div class="product-info">
                    <div class="product-name" style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                        ${item.nombre_producto}
                        ${badgeReembolsoHtml}
                    </div>
                    ${atributosHtml}
                    <div class="product-meta">
                        <span class="sku">SKU: ${item.sku || '—'}</span>
                    </div>
                    ${descuentoHtml}
                </div>
                <div class="product-qty">x${item.cantidad}</div>
                <div class="product-price">
                    ${precioRegularHtml}
                    <div class="unit">S/ ${precioFinal.toFixed(2)} c/u</div>
                    <div class="sub">S/ ${parseFloat(item.subtotal_linea).toFixed(2)}</div>
                </div>
            </div>
        `;
    }).join('');

    const evidenciasHtml = (pedido.evidencias && pedido.evidencias.length > 0)
        ? pedido.evidencias.map(ev => `
            <div class="evidencia-card">
                <div class="evidencia-img">
                    ${ev.mime_type && ev.mime_type.startsWith('image/')
                        ? `<img src="/${ev.archivo_path}" 
                                alt="${ev.tipo_evidencia}"
                                class="img-evidencia-zoom" 
                                data-img-src="/${ev.archivo_path}"
                                style="cursor: zoom-in;">`
                        : `<div class="file-icon"><i class="bi bi-file-earmark"></i></div>`
                    }
                </div>
                <div class="evidencia-info">
                    <div class="evidencia-header">
                        <span class="evidencia-tipo">${ev.tipo_evidencia.replace(/_/g, ' ')}</span>
                        ${ev.visible_cliente 
                            ? '<span class="badge-visibilidad visible"><i class="bi bi-eye"></i> Visible cliente</span>' 
                            : '<span class="badge-visibilidad hidden"><i class="bi bi-eye-slash"></i> Interna</span>'
                        }
                    </div>
                    ${ev.descripcion ? `<div class="evidencia-desc">${ev.descripcion}</div>` : ''}
                    <div class="evidencia-footer">
                        <small><i class="bi bi-person"></i> ${ev.subido_por?.nombres || 'Admin'}</small>
                        <small><i class="bi bi-clock"></i> ${new Date(ev.created_at).toLocaleDateString('es-ES')}</small>
                        <button class="btn-icon-danger" onclick="eliminarEvidencia(${ev.id}, ${pedido.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('')
        : `<div class="empty-state-sm"><i class="bi bi-images"></i><p>Sin evidencias</p></div>`;

    let timelineItems = [];
    if (pedido.items && pedido.items.length > 0) {
        pedido.items.forEach(item => {
            if (item.producto && item.producto.nota_interna) {
                let fechaProducto = new Date();
                if (item.producto.created_at) {
                    fechaProducto = new Date(item.producto.created_at);
                }

                timelineItems.push({
                    esNotaProducto: true,
                    fecha: fechaProducto, 
                    titulo: 'Nota del Producto',
                    motivo: item.producto.nota_interna,
                    producto_nombre: item.nombre_producto,
                    icono: 'bi-exclamation-triangle-fill',
                    colorClass: 'nota-producto'
                });
            }
        });
    }

    if (pedido.historial && pedido.historial.length > 0) {
        pedido.historial.forEach(h => {
            const esNota = h.tipo_cambio === 'nota' || h.estado_nuevo === 'nota';
            let iconClass = esNota ? 'bi-chat-left-text' : 'bi-arrow-repeat';
            let colorClass = esNota ? 'note' : 'state';
            let titulo = esNota ? 'Nota interna' : h.estado_nuevo;

            if (h.tipo_cambio === 'estado_pago') {
                iconClass = 'bi-credit-card';
                colorClass = 'payment';
                titulo = 'Estado de Pago';
            } else if (h.tipo_cambio === 'estado_envio') {
                iconClass = 'bi-truck';
                colorClass = 'shipping';
                titulo = 'Estado de Envío';
            } else if (h.tipo_cambio === 'evidencia') {
                iconClass = 'bi-file-earmark-image';
                colorClass = 'evidence';
                titulo = 'Evidencia';
            } else if (h.tipo_cambio === 'reembolso') {
                iconClass = 'bi-arrow-counterclockwise';
                colorClass = 'refund';
                titulo = 'Reembolso';
            }

            timelineItems.push({
                esNotaProducto: false,
                fecha: new Date(h.created_at),
                titulo: titulo,
                motivo: h.motivo || '',
                estado_nuevo: h.estado_nuevo,
                user: h.user,
                icono: iconClass,
                colorClass: colorClass
            });
        });
    }

    timelineItems.sort((a, b) => {
        if (a.esNotaProducto && !b.esNotaProducto) return 1;
        if (!a.esNotaProducto && b.esNotaProducto) return -1;
        return b.fecha - a.fecha;
    });

    const historialHtml = timelineItems.length > 0
        ? timelineItems.map(item => {
            if (item.esNotaProducto) {
                return `
                    <div class="timeline-item nota-producto" style="background: #fff9ec; border-left: 3px solid #F4AB28; padding: 0.75rem; border-radius: 8px; margin-bottom: 0.5rem;">
                        <div class="timeline-icon" style="background: #F4AB28; color: white;">
                            <i class="bi ${item.icono}"></i>
                        </div>
                        <div class="timeline-content" style="margin-left: 0.5rem;">
                            <div class="timeline-header">
                                <span class="badge bg-warning text-dark">${item.titulo}</span>
                                <small class="text-muted">${item.fecha.toLocaleString('es-ES')}</small>
                            </div>
                            <div class="timeline-motivo" style="font-weight: 600; color: #d35400;">
                                ${item.motivo}
                            </div>
                            <div class="timeline-user text-muted" style="font-size: 0.75rem; margin-top: 4px;">
                                <i class="bi bi-box-seam"></i> Producto: ${item.producto_nombre}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                return `
                    <div class="timeline-item ${item.colorClass}">
                        <div class="timeline-icon">
                            <i class="bi ${item.icono}"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                ${item.titulo === 'Nota interna' 
                                    ? '<span class="badge bg-info">Nota interna</span>'
                                    : `<span class="badge bg-warning text-dark">${item.titulo}: ${item.estado_nuevo}</span>`
                                }
                                <small>${item.fecha.toLocaleString('es-ES')}</small>
                            </div>
                            <div class="timeline-motivo">${item.motivo}</div>
                            ${item.user ? `<div class="timeline-user"><i class="bi bi-person-circle"></i> ${item.user.nombres}</div>` : ''}
                        </div>
                    </div>
                `;
            }
        }).join('')
        : `<div class="empty-state-sm"><i class="bi bi-clock-history"></i><p>Sin historial</p></div>`;

    const reembolsosHtml = (pedido.reembolsos && pedido.reembolsos.length > 0)
    ? pedido.reembolsos.map(r => `
        <div class="reembolso-card">
            <div class="reembolso-header">
                <span class="badge bg-${r.tipo === 'total' ? 'danger' : 'warning'}">
                    ${r.tipo === 'total' ? 'Reembolso Total' : 'Reembolso Parcial'}
                </span>
                <strong>S/ ${parseFloat(r.monto_total).toFixed(2)}</strong>
            </div>
            <div class="reembolso-detalle">
                Productos: S/ ${parseFloat(r.monto_productos).toFixed(2)} |
                Envío: S/ ${parseFloat(r.monto_envio).toFixed(2)}
            </div>
            <div class="reembolso-motivo">${r.motivo || ''}</div>
            <div class="reembolso-fecha">
                <i class="bi bi-person"></i> ${r.user?.nombres || 'Admin'}
                <i class="bi bi-clock ms-2"></i> ${new Date(r.created_at).toLocaleString('es-ES')}
            </div>
        </div>
    `).join('')
    : `<div class="empty-state-sm"><i class="bi bi-cash-coin"></i><p>Sin reembolsos</p></div>`;

    const optionsEstado = estados.map(e => 
        `<option value="${e.value}" ${e.value === pedido.estado ? 'selected' : ''}>${e.label}</option>`
    ).join('');

    const referenciaHtml = (pedido.referencia_entrega && pedido.referencia_entrega.trim() !== '') 
        ? `<div class="address-ref" style="font-size: 0.85rem; color: #666; margin-top: 4px;">
               <i class="bi bi-info-circle"></i> Ref: ${pedido.referencia_entrega}
           </div>` 
        : '';

    const direccionHtml = pedido.tipo_entrega === 'delivery'
        ? `<div class="address-block">
               <div class="address-icon"><i class="bi bi-geo-alt-fill"></i></div>
               <div>
                   <div class="address-line">${pedido.direccion_entrega || 'Sin dirección'}</div>
                   <div class="address-sub">${pedido.distrito_nombre || ''} · ${pedido.provincia_nombre || ''} · ${pedido.departamento_nombre || ''}</div>
               </div>
           </div>`
        : `<div class="address-block">
               <div class="address-icon"><i class="bi bi-shop"></i></div>
               <div>
                   <div class="address-line">${pedido.punto_retiro || 'Recojo en tienda'}</div>
                   <div class="address-sub">Recojo presencial</div>
               </div>
           </div>`;

    const totalReembolsado = (pedido.reembolsos && pedido.reembolsos.length > 0)
        ? pedido.reembolsos.reduce((sum, r) => sum + parseFloat(r.monto_total || 0), 0)
        : 0;

    const desgloseHtml = `
        <div class="monto-line"><span>Subtotal</span><span>S/ ${parseFloat(pedido.subtotal).toFixed(2)}</span></div>
        ${pedido.costo_envio > 0 ? `<div class="monto-line"><span>Envío</span><span>S/ ${parseFloat(pedido.costo_envio).toFixed(2)}</span></div>` : ''}
        ${pedido.cargo_metodo_pago > 0 ? `<div class="monto-line"><span>Cargo por pago</span><span>S/ ${parseFloat(pedido.cargo_metodo_pago).toFixed(2)}</span></div>` : ''}
        ${pedido.impuesto_cargo > 0 ? `<div class="monto-line"><span>Impuesto cargo</span><span>S/ ${parseFloat(pedido.impuesto_cargo).toFixed(2)}</span></div>` : ''}
        ${pedido.descuento_total > 0 ? `<div class="monto-line descuento"><span>Descuento</span><span>- S/ ${parseFloat(pedido.descuento_total).toFixed(2)}</span></div>` : ''}
        ${totalReembolsado > 0 ? `
            <div class="monto-line reembolso" style="color: #0d6efd; font-weight: 600;">
                <span><i class="bi bi-arrow-counterclockwise"></i> Reembolso</span>
                <span>- S/ ${totalReembolsado.toFixed(2)}</span>
            </div>
        ` : ''}

        <div class="monto-total"><span>Total</span><span>S/ ${parseFloat(pedido.total).toFixed(2)}</span></div>
    `;

    document.getElementById('modalDetalleBody').innerHTML = `
        <div class="woo-detail-wrapper">
            <div class="woo-header">
                <div class="woo-header-left">
                    <h4>Pedido #${pedido.codigo}</h4>
                    <small><i class="bi bi-calendar"></i> ${new Date(pedido.created_at).toLocaleString('es-ES')}</small>
                </div>
                <div class="woo-header-right">
                    <span class="badge-estado badge-${estadoActual.color}">
                        <i class="bi ${estadoActual.icon}"></i> ${estadoActual.label}
                    </span>
                    ${permisos.editar && pedido.estado !== 'reembolsado' ? `
                        <button class="btn-reembolso-general" onclick="abrirModalReembolso(${pedido.id})">
                            <i class="bi bi-cash-coin"></i> Reembolsar
                        </button>
                    ` : ''}
                </div>
            </div>

            <div class="woo-grid">
                <div class="woo-main">
                    <div class="woo-card">
                        <div class="woo-card-header">
                            <h5><i class="bi bi-person-circle"></i> Datos del cliente</h5>
                            ${permisos.editar ? `
                                <button class="btn-edit-icon" onclick="abrirEdicionCliente(${pedido.id})" title="Editar cliente">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            ` : ''}
                        </div>
                        <div class="cliente-grid">
                            <div class="cliente-field">
                                <small>Nombre</small>
                                <strong>${pedido.cliente_nombres} ${pedido.cliente_apellidos}</strong>
                            </div>
                            <div class="cliente-field">
                                <small>Email</small>
                                <strong>${pedido.cliente_email}</strong>
                            </div>
                            <div class="cliente-field">
                                <small>Teléfono</small>
                                <strong>${pedido.cliente_celular || '—'}</strong>
                            </div>
                            <div class="cliente-field">
                                <small>Documento</small>
                                <strong>${pedido.cliente_documento_tipo || '—'}: ${pedido.cliente_documento_numero || '—'}</strong>
                            </div>
                        </div>
                        <hr>
                        <h6 class="mt-3 mb-2"><i class="bi bi-geo-alt"></i> Dirección</h6>
                        ${direccionHtml}
                        ${referenciaHtml}
                    </div>
                    <div class="woo-card">
                        <div class="woo-card-header">
                            <h5><i class="bi bi-box-seam"></i> Productos</h5>
                            <span class="badge-count">${pedido.items.length} items</span>
                        </div>
                        <div class="product-list">${itemsHtml}</div>
                    </div>
                    <div class="woo-card">
                        <div class="woo-card-header">
                            <h5><i class="bi bi-clock-history"></i> Historial y Notas</h5>
                        </div>
                        <div class="timeline-list">${historialHtml}</div>

                        ${permisos.editar ? `
                        <div class="nota-form">
                            <textarea id="nuevaNota" class="form-control mb-2" rows="2" placeholder="Escribe una nota interna..."></textarea>
                            <button class="btn btn-info w-100" onclick="agregarNota(${pedido.id})">
                                <i class="bi bi-plus-circle"></i> Agregar Nota
                            </button>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <div class="woo-side">

                    <div class="woo-card">
                        <div class="woo-card-header">
                            <h5><i class="bi bi-arrow-repeat"></i> Estado y Evidencias</h5>
                        </div>

                        ${permisos.editar ? `
                        <div class="estado-selector">
                            <label>Cambiar estado</label>
                            <select id="nuevoEstado" class="form-select mb-2">${optionsEstado}</select>
                            <textarea id="motivoEstado" class="form-control mb-2" rows="2" placeholder="Motivo (opcional)"></textarea>
                            <button class="btn btn-warning w-100" onclick="cambiarEstado(${pedido.id})">
                                <i class="bi bi-check-circle"></i> Actualizar Estado
                            </button>
                        </div>
                        ` : ''}

                        <hr>

                        <div class="evidencias-header">
                            <label><i class="bi bi-images"></i> Evidencias</label>
                            <button class="btn btn-sm btn-info" onclick="abrirModalEvidencia(${pedido.id})">
                                <i class="bi bi-plus-circle"></i> Subir
                            </button>
                        </div>
                        <div class="evidencias-list">${evidenciasHtml}</div>
                    </div>

                    <div class="woo-card">
                        <div class="woo-card-header">
                            <h5><i class="bi bi-receipt"></i> Resumen</h5>
                        </div>
                        <div class="desglose">${desgloseHtml}</div>
                        ${pedido.voucher_path ? `
                            <a href="/vouchers/${pedido.voucher_path}" target="_blank" class="btn-ver-voucher-full mt-2">
                                <i class="bi bi-receipt"></i> Ver comprobante
                            </a>
                        ` : ''}
                    </div>
                    <div class="woo-card">
                        <div class="woo-card-header">
                            <h5><i class="bi bi-cash-coin"></i> Reembolsos</h5>
                            <span class="badge-count">${(pedido.reembolsos || []).length}</span>
                        </div>
                        <div class="reembolsos-list">${reembolsosHtml}</div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

async function cambiarEstado(pedidoId) {
    const estado = document.getElementById('nuevoEstado').value;
    const motivo = document.getElementById('motivoEstado').value;

    if (estado === 'cancelado' && !motivo.trim()) {
        Swal.fire('Atención', 'Debes ingresar un motivo de cancelación.', 'warning');
        return;
    }

    const confirm = await Swal.fire({
        title: '¿Cambiar estado?',
        text: `El pedido pasará a estado: ${estado}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirm.isConfirmed) return;
    const statusMessages = showPreloader('Actualizando Estado...', 'editar');

    try {
        const res = await fetch('/Ventas/pedidos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ opcion: 'CambiarEstado', id: pedidoId, estado, motivo })
        });
        const data = await res.json();
        hidePreloader(statusMessages);

        if (data.respuesta === 'success') {
            const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modalDetalle'));
            if (modalDetalle) modalDetalle.hide();
            setTimeout(() => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';

                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: data.mensaje,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    verDetalle(pedidoId);
                });
            }, 400); 
            cargarPedidos();
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        hidePreloader(statusMessages);
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
}

async function actualizarDatos(pedidoId) {
    const payload = {
        opcion: 'ActualizarDatos',
        id: pedidoId,
        cliente_nombres: document.getElementById('edit_nombres').value.trim(),
        cliente_apellidos: document.getElementById('edit_apellidos').value.trim(),
        cliente_email: document.getElementById('edit_email').value.trim(),
        cliente_celular: document.getElementById('edit_celular').value.trim(),
        direccion_entrega: document.getElementById('edit_direccion').value.trim(),
    };

    if (!payload.cliente_nombres || !payload.cliente_apellidos || !payload.cliente_email) {
        Swal.fire('Atención', 'Nombres, apellidos y email son obligatorios.', 'warning');
        return;
    }

    const confirm = await Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizarán los datos del pedido.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirm.isConfirmed) return;

    try {
        const res = await fetch('/Ventas/pedidos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.respuesta === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: data.mensaje,
                timer: 1500,
                showConfirmButton: false
            });
            verDetalle(pedidoId);
            cargarPedidos();
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
}

async function agregarNota(pedidoId) {
    const nota = document.getElementById('nuevaNota').value.trim();
    if (!nota) {
        Swal.fire('Atención', 'Escribe una nota antes de guardar.', 'warning');
        return;
    }

    try {
        const res = await fetch('/Ventas/pedidos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ opcion: 'AgregarNota', pedido_id: pedidoId, nota })
        });
        const data = await res.json();

        if (data.respuesta === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Nota agregada',
                timer: 1200,
                showConfirmButton: false
            });
            verDetalle(pedidoId);
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
}

function abrirModalEvidencia(pedidoId) {
    if (!document.getElementById('modalEvidencia')) {
        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="modalEvidencia" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title"><i class="bi bi-upload"></i> Subir Evidencia</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="evPedidoId">
                            <label class="form-label">Tipo de Evidencia</label>
                            <select id="evTipo" class="form-select mb-2">
                                <option value="empaquetado">Empaquetado</option>
                                <option value="guia_envio">Guía de envío</option>
                                <option value="en_transito">En tránsito</option>
                                <option value="entrega_cliente">Entrega al cliente</option>
                                <option value="dni_receptor">DNI del receptor</option>
                                <option value="firma_receptor">Firma del receptor</option>
                                <option value="incidencia">Incidencia</option>
                                <option value="devolucion">Devolución</option>
                                <option value="otro">Otro</option>
                            </select>
                            <label class="form-label">Archivo</label>
                            <input type="file" id="evArchivo" class="form-control mb-2" accept="image/*,application/pdf">
                            <label class="form-label">Descripción (opcional)</label>
                            <textarea id="evDescripcion" class="form-control mb-2" rows="2" placeholder="Ej: Paquete embalado con cinta roja"></textarea>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="evVisible" checked>
                                <label class="form-check-label" for="evVisible">Visible para el cliente</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button class="btn btn-info" onclick="enviarEvidencia()">
                                <i class="bi bi-upload"></i> Subir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    document.getElementById('evPedidoId').value = pedidoId;
    document.getElementById('evTipo').value = 'empaquetado';
    document.getElementById('evArchivo').value = '';
    document.getElementById('evDescripcion').value = '';
    document.getElementById('evVisible').checked = true;

    new bootstrap.Modal(document.getElementById('modalEvidencia')).show();
}

async function enviarEvidencia() {
    const pedidoId = document.getElementById('evPedidoId').value;
    const tipo = document.getElementById('evTipo').value;
    const archivo = document.getElementById('evArchivo').files[0];
    const descripcion = document.getElementById('evDescripcion').value;
    const visibleCliente = document.getElementById('evVisible').checked;

    if (!archivo) {
        Swal.fire('Atención', 'Selecciona un archivo antes de subir.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('opcion', 'SubirEvidencia');
    formData.append('pedido_id', pedidoId);
    formData.append('tipo_evidencia', tipo);
    formData.append('archivo', archivo);
    formData.append('descripcion', descripcion);
    formData.append('visible_cliente', visibleCliente ? 1 : 0);

    try {
        const res = await fetch('/Ventas/pedidos', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            body: formData
        });
        const data = await res.json();

        if (data.respuesta === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('modalEvidencia')).hide();
            Swal.fire({
                icon: 'success',
                title: 'Evidencia subida',
                timer: 1500,
                showConfirmButton: false
            });
            verDetalle(pedidoId);
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
}

async function eliminarEvidencia(evidenciaId, pedidoId) {
    const confirm = await Swal.fire({
        title: '¿Eliminar evidencia?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    });

    if (!confirm.isConfirmed) return;

    const statusMessages = showPreloader('Eliminando evidencia...', 'eliminar');

    try {
        const res = await fetch('/Ventas/pedidos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ opcion: 'EliminarEvidencia', id: evidenciaId })
        });
        const data = await res.json();
        
        hidePreloader(statusMessages);

        if (data.respuesta === 'success') {
            
            const modalEv = bootstrap.Modal.getInstance(document.getElementById('modalEvidencia'));
            if (modalEv) modalEv.hide();
            const modalDet = bootstrap.Modal.getInstance(document.getElementById('modalDetalle'));
            if (modalDet) modalDet.hide();

            setTimeout(() => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';

                Swal.fire({
                    icon: 'success',
                    title: 'Evidencia eliminada',
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => {
                    verDetalle(pedidoId);
                });

            }, 400); 

        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        hidePreloader(statusMessages);
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
}

let costoEnvioOriginal = 0;
let nuevoCostoEnvio = 0;

async function abrirEdicionCliente(pedidoId) {
    const pedido = window._pedidoActual;
    costoEnvioOriginal = parseFloat(pedido.costo_envio || 0);
    nuevoCostoEnvio = costoEnvioOriginal;
    document.getElementById('edit_pedido_id').value = pedidoId;
    document.getElementById('edit_nombres').value = pedido.cliente_nombres || '';
    document.getElementById('edit_apellidos').value = pedido.cliente_apellidos || '';
    document.getElementById('edit_email').value = pedido.cliente_email || '';
    document.getElementById('edit_celular').value = pedido.cliente_celular || '';
    document.getElementById('edit_documento_tipo').value = pedido.cliente_documento_tipo || '';
    document.getElementById('edit_documento_numero').value = pedido.cliente_documento_numero || '';
    document.getElementById('edit_direccion').value = pedido.direccion_entrega || '';
    document.getElementById('edit_referencia').value = pedido.referencia_entrega || '';
    document.getElementById('aviso_costo_envio').style.display = 'none';

    const selectDepto = document.getElementById('edit_departamento_id');
    selectDepto.innerHTML = '<option value="">Seleccione</option>';
    
    try {
        const res = await fetch('/get-departamentos');
        const deptos = await res.json();
        deptos.forEach(d => {
            selectDepto.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
        });
        
        if (pedido.departamento_id) {
            selectDepto.value = pedido.departamento_id;
            await cargarProvinciasEdicion(pedido.departamento_id, pedido.provincia_id, pedido.distrito_id);
        }
    } catch (error) {
    }

    new bootstrap.Modal(document.getElementById('modalEditarCliente')).show();
}

async function cargarProvinciasEdicion(deptoId, provSelect = null, distSelect = null) {
    const selectProv = document.getElementById('edit_provincia_id');
    const selectDist = document.getElementById('edit_distrito_id');
    
    selectProv.innerHTML = '<option value="">Seleccione</option>';
    selectDist.innerHTML = '<option value="">Seleccione</option>';
    selectDist.disabled = true;

    if (!deptoId) {
        selectProv.disabled = true;
        return;
    }

    selectProv.disabled = false;

    try {
        const res = await fetch(`/get-provincias/${deptoId}`);
        const provincias = await res.json();
        provincias.forEach(p => {
            selectProv.innerHTML += `<option value="${p.id}">${p.nombre}</option>`;
        });

        if (provSelect) {
            selectProv.value = provSelect;
            await cargarDistritosEdicion(provSelect, distSelect);
        }
    } catch (error) {
    }
}

async function cargarDistritosEdicion(provId, distSelect = null) {
    const selectDist = document.getElementById('edit_distrito_id');
    selectDist.innerHTML = '<option value="">Seleccione</option>';

    if (!provId) {
        selectDist.disabled = true;
        return;
    }

    selectDist.disabled = false;

    try {
        const res = await fetch(`/get-distritos/${provId}`);
        const distritos = await res.json();
        
        distritos.forEach(d => {
            selectDist.innerHTML += `<option value="${d.id}" data-costo="${d.costo_envio}">${d.nombre}</option>`;
        });

        if (distSelect) {
            selectDist.value = distSelect;
            evaluarCostoEnvio();
        }
    } catch (error) {
    }
}

function evaluarCostoEnvio() {
    const selectDist = document.getElementById('edit_distrito_id');
    const selectedOption = selectDist.options[selectDist.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        nuevoCostoEnvio = 0;
        document.getElementById('aviso_costo_envio').style.display = 'none';
        return;
    }

    nuevoCostoEnvio = parseFloat(selectedOption.dataset.costo || 0);
    const aviso = document.getElementById('aviso_costo_envio');

    if (nuevoCostoEnvio !== costoEnvioOriginal) {
        document.getElementById('nuevo_costo_envio').textContent = `S/ ${nuevoCostoEnvio.toFixed(2)}`;
        document.getElementById('costo_anterior').textContent = `S/ ${costoEnvioOriginal.toFixed(2)}`;
        aviso.style.display = 'block';
    } else {
        aviso.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('edit_departamento_id')?.addEventListener('change', function() {
        cargarProvinciasEdicion(this.value);
    });
    
    document.getElementById('edit_provincia_id')?.addEventListener('change', function() {
        cargarDistritosEdicion(this.value);
    });
    
    document.getElementById('edit_distrito_id')?.addEventListener('change', function() {
        evaluarCostoEnvio();
    });

    document.getElementById('btnGuardarCliente')?.addEventListener('click', async function() {
        const btn = this;
        const pedidoId = document.getElementById('edit_pedido_id').value;
        
        const payload = {
            opcion: 'ActualizarDatos',
            id: pedidoId,
            cliente_nombres: document.getElementById('edit_nombres').value.trim(),
            cliente_apellidos: document.getElementById('edit_apellidos').value.trim(),
            cliente_email: document.getElementById('edit_email').value.trim(),
            cliente_celular: document.getElementById('edit_celular').value.trim(),
            cliente_documento_tipo: document.getElementById('edit_documento_tipo').value,
            cliente_documento_numero: document.getElementById('edit_documento_numero').value.trim(),
            departamento_id: document.getElementById('edit_departamento_id').value,
            provincia_id: document.getElementById('edit_provincia_id').value,
            distrito_id: document.getElementById('edit_distrito_id').value,
            direccion_entrega: document.getElementById('edit_direccion').value.trim(),
            referencia_entrega: document.getElementById('edit_referencia').value.trim(),
            nuevo_costo_envio: nuevoCostoEnvio 
        };

        if (!payload.cliente_nombres || !payload.cliente_apellidos || !payload.cliente_email) {
            Swal.fire('Atención', 'Nombres, apellidos y email son obligatorios.', 'warning');
            return;
        }

        const confirm = await Swal.fire({
            title: '¿Guardar cambios?',
            text: 'Se actualizarán los datos del pedido.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirm.isConfirmed) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';
        const statusMessages = showPreloader('Guardando Datos...', 'editar');

        try {
            const res = await fetch('/Ventas/pedidos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            hidePreloader(statusMessages);

            if (data.respuesta === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('modalEditarCliente')).hide();
                Swal.fire({ icon: 'success', title: 'Guardado', text: data.mensaje, timer: 1500, showConfirmButton: false });
                verDetalle(pedidoId);
                cargarPedidos();
            } else {
                Swal.fire('Error', data.mensaje, 'error');
            }
        } catch (error) {
            hidePreloader(statusMessages);
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Guardar Cambios';
        }
    });
});

function abrirModalReembolso(pedidoId) {
    const pedido = window._pedidoActual;
    const items = pedido.items.filter(i => i.estado_item !== 'reembolsado');

    if (items.length === 0) {
        Swal.fire('Atención', 'No hay items pendientes por reembolsar.', 'warning');
        return;
    }

    const esDelivery = pedido.tipo_entrega === 'delivery' && pedido.costo_envio > 0;

    document.getElementById('reembolsoPedidoId').value = pedidoId;

    const list = document.getElementById('reembolsoItemsList');
    list.innerHTML = '';

    items.forEach(item => {
        const precioUnit = parseFloat(item.precio_unitario_final);
        const maxMonto = parseFloat(item.subtotal_linea);
        const maxCant = item.cantidad;

        const div = document.createElement('div');
        div.className = 'reembolso-item';
        div.dataset.itemId = item.id;
        div.dataset.max = maxMonto;
        div.dataset.precioUnit = precioUnit;
        div.dataset.cantidadMax = maxCant;

        div.innerHTML = `
            <div class="reembolso-item-info">
                <strong>${item.nombre_producto}</strong>
                <small>${item.sku || ''} | x${maxCant} | S/ ${precioUnit.toFixed(2)} c/u</small>
            </div>
            <div class="reembolso-item-controls">
                <label>Cant.</label>
                <input type="number" class="form-control form-control-sm reembolso-cantidad"
                       value="0" min="0" max="${maxCant}" data-item-id="${item.id}">
                <label>Monto</label>
                <input type="number" step="0.01" class="form-control form-control-sm reembolso-monto"
                       value="0.00" min="0" max="${maxMonto.toFixed(2)}" data-item-id="${item.id}">
            </div>
        `;

        list.appendChild(div);
    });

    const envioSection = document.getElementById('reembolsoEnvioSection');
    if (esDelivery) {
        envioSection.style.display = 'block';
        document.getElementById('reembolsoIncluirEnvio').checked = false;
        document.getElementById('reembolsoMontoEnvio').value = '0.00';
        document.getElementById('reembolsoMontoEnvio').max = parseFloat(pedido.costo_envio).toFixed(2);
    } else {
        envioSection.style.display = 'none';
        document.getElementById('reembolsoIncluirEnvio').checked = false;
        document.getElementById('reembolsoMontoEnvio').value = '0.00';
    }

    document.getElementById('reembolsoMotivo').value = '';
    recalcularTotalReembolso();

    if (!list.dataset.listenersAdded) {
        list.addEventListener('input', e => {
            if (e.target.classList.contains('reembolso-cantidad')) {
                const itemEl = e.target.closest('.reembolso-item');
                const maxCant = parseInt(itemEl.dataset.cantidadMax);
                const precioUnit = parseFloat(itemEl.dataset.precioUnit);
                const maxMonto = parseFloat(itemEl.dataset.max);
                const montoInput = itemEl.querySelector('.reembolso-monto');

                let cantidad = parseInt(e.target.value) || 0;
                if (cantidad > maxCant) cantidad = maxCant;
                if (cantidad < 0) cantidad = 0;
                e.target.value = cantidad;

                if (cantidad > 0) {
                    let montoSugerido = cantidad * precioUnit;
                    if (montoSugerido > maxMonto) montoSugerido = maxMonto;
                    montoInput.value = montoSugerido.toFixed(2);
                } else {
                    montoInput.value = '0.00';
                }
                recalcularTotalReembolso();
            }
        });

        list.addEventListener('blur', e => {
            if (e.target.classList.contains('reembolso-monto')) {
                const itemEl = e.target.closest('.reembolso-item');
                const maxMonto = parseFloat(itemEl.dataset.max);
                let monto = parseFloat(e.target.value) || 0;
                if (monto > maxMonto) monto = maxMonto;
                if (monto < 0) monto = 0;
                e.target.value = monto.toFixed(2);
                recalcularTotalReembolso();
            }
        }, true);

        list.dataset.listenersAdded = '1';
    }

    const checkEnvio = document.getElementById('reembolsoIncluirEnvio');
    const inputEnvio = document.getElementById('reembolsoMontoEnvio');
    if (!checkEnvio.dataset.listener) {
        checkEnvio.addEventListener('change', function () {
            if (this.checked) {
                const maxEnvio = parseFloat(inputEnvio.max) || 0;
                inputEnvio.value = maxEnvio.toFixed(2);
            } else {
                inputEnvio.value = '0.00';
            }
            recalcularTotalReembolso();
        });

        inputEnvio.addEventListener('input', function () {
            const maxEnvio = parseFloat(this.max) || 0;
            let v = parseFloat(this.value) || 0;
            if (v > maxEnvio) v = maxEnvio;
            if (v < 0) v = 0;
            recalcularTotalReembolso();
        });

        inputEnvio.addEventListener('blur', function () {
            const maxEnvio = parseFloat(this.max) || 0;
            let v = parseFloat(this.value) || 0;
            if (v > maxEnvio) v = maxEnvio;
            if (v < 0) v = 0;
            this.value = v.toFixed(2);
            recalcularTotalReembolso();
        });

        checkEnvio.dataset.listener = '1';
    }

    new bootstrap.Modal(document.getElementById('modalReembolso')).show();
}

function recalcularTotalReembolso() {
    let total = 0;

    document.querySelectorAll('#reembolsoItemsList .reembolso-item').forEach(el => {
        const cantidad = parseInt(el.querySelector('.reembolso-cantidad').value) || 0;
        if (cantidad <= 0) return;

        const monto = parseFloat(el.querySelector('.reembolso-monto').value) || 0;
        total += monto;
    });

    const envioCheck = document.getElementById('reembolsoIncluirEnvio');
    const envioInput = document.getElementById('reembolsoMontoEnvio');
    if (envioCheck && envioCheck.checked && envioInput) {
        const maxEnvio = parseFloat(envioInput.max) || 0;
        let envio = parseFloat(envioInput.value) || 0;
        if (envio > maxEnvio) envio = maxEnvio;
        total += envio;
    }

    document.getElementById('reembolsoTotal').textContent = `S/ ${total.toFixed(2)}`;
}

function establecerFechasPorDefecto() {
    const hoy = new Date();
    
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
    const formatFecha = (fecha) => {
        const yyyy = fecha.getFullYear();
        const mm = String(fecha.getMonth() + 1).padStart(2, '0');
        const dd = String(fecha.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    };

    document.getElementById('fechaDesde').value = formatFecha(primerDia);
    document.getElementById('fechaHasta').value = formatFecha(ultimoDia);
}