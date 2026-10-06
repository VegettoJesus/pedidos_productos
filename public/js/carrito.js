'use strict';

const CarritoAPI = {
    async _fetch(url, options = {}) {
        const res = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
        });
        const data = await res.json().catch(() => ({}));
        return { status: res.status, data };
    },

    async index() {
        return this._fetch('/carrito/data');
    },
    async agregar(payload) {
        return this._fetch('/carrito/agregar', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
    },
    async actualizar(itemId, cantidad) {
        return this._fetch(`/carrito/item/${itemId}`, {
            method: 'PUT',
            body: JSON.stringify({ cantidad }),
        });
    },
    async eliminar(itemId) {
        return this._fetch(`/carrito/item/${itemId}`, { method: 'DELETE' });
    },
    async vaciar() {
        return this._fetch('/carrito/vaciar', { method: 'DELETE' });
    },
    async count() {
        const res = await fetch('/carrito/count', { headers: { 'Accept': 'application/json' } });
        return res.json();
    },
};

function formatPrecio(n) {
    return 'S/. ' + Number(n).toFixed(2);
}

function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
}

function imagenProducto(item) {
    const v = item.variacion;
    const p = item.producto;

    let path = null;
    if (v && Array.isArray(v.imagenes) && v.imagenes.length) {
        path = v.imagenes[0].imagen_path;
    }
    if (!path && p) {
        path = p.imagen_miniatura;
    }
    if (!path && p && Array.isArray(p.imagenes) && p.imagenes.length) {
        path = p.imagenes[0].imagen_path;
    }
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/')) return path;
    return '/' + path;
}

async function actualizarContadorCarrito() {
    try {
        const data = await CarritoAPI.count();
        const badge = document.getElementById('cartBadge');
        if (!badge) return;

        if (data.logueado && data.count > 0) {
            badge.textContent = data.count > 99 ? '99+' : data.count;
            badge.style.display = 'flex';
            badge.classList.add('has-items');
        } else {
            badge.style.display = 'none';
            badge.classList.remove('has-items');
        }
    } catch (e) {
    }
}

let cartDropdownOpen = false;

async function cargarMiniCarrito() {
    const body = document.getElementById('cartDropdownBody');
    const footer = document.getElementById('cartDropdownFooter');
    const totalEl = document.getElementById('cartDropdownTotal');
    if (!body) return;

    const { status, data } = await CarritoAPI.index();

    if (status === 401) {
        body.innerHTML = `
            <div class="cart-empty">
                <i class="bi bi-lock"></i>
                <p>Inicia sesión para ver tu carrito</p>
                <button class="btn-ir-checkout" onclick="openAuthModal('login')" style="max-width:180px;margin:1rem auto 0;">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                </button>
            </div>`;
        footer.style.display = 'none';
        return;
    }

    if (!data.ok || !data.items || data.items.length === 0) {
        body.innerHTML = `
            <div class="cart-empty">
                <i class="bi bi-cart-x"></i>
                <p>Tu carrito está vacío</p>
            </div>`;
        footer.style.display = 'none';
        return;
    }

    body.innerHTML = data.items.map(item => {
        const img = imagenProducto(item);
        const titulo = item.producto?.nombre || 'Producto';
        const sku = item.variacion?.sku || item.producto?.sku;
        const subtotal = item.cantidad * item.precio_unitario;

        return `
            <div class="cart-item-mini" data-item-id="${item.id}">
                <div class="cart-item-mini-img">
                    ${img ? `<img src="${escapeHtml(img)}" alt="">` : `<i class="bi bi-box"></i>`}
                </div>
                <div class="cart-item-mini-info">
                    <div class="cart-item-mini-title">${escapeHtml(titulo)}</div>
                    ${sku ? `<div class="cart-item-mini-meta"><span class="sku">SKU: ${escapeHtml(sku)}</span></div>` : ''}
                    <div class="cart-item-mini-bottom">
                        <span class="cart-item-mini-qty">x${item.cantidad}</span>
                        <span class="cart-item-mini-price">${formatPrecio(subtotal)}</span>
                    </div>
                </div>
                <button class="cart-item-mini-remove js-mini-remove" data-item-id="${item.id}" title="Eliminar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;
    }).join('');

    body.querySelectorAll('.js-mini-remove').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const itemId = btn.dataset.itemId;
            const res = await CarritoAPI.eliminar(itemId);

            if (res.data.ok) {
                if (res.data.item_eliminado) {
                    const { producto_id, variacion_id, cantidad } = res.data.item_eliminado;
                    devolverStockEnDOM(producto_id, variacion_id, cantidad);
                }

                await cargarMiniCarrito();
                actualizarContadorCarrito();

                const dropdown = document.getElementById('cartDropdown');
                if (dropdown && cartDropdownOpen) {
                    dropdown.classList.add('show');
                }
            }
        }, true);
    });

    totalEl.textContent = formatPrecio(data.total);
    footer.style.display = 'block';
}

function toggleCartDropdown(force = null) {
    const dropdown = document.getElementById('cartDropdown');
    if (!dropdown) return;

    cartDropdownOpen = force !== null ? force : !cartDropdownOpen;

    if (cartDropdownOpen) {
        cargarMiniCarrito();
        dropdown.classList.add('show');
    } else {
        dropdown.classList.remove('show');
    }
}

async function agregarAlCarrito(productoId, variacionId = null, cantidad = 1, padreId = null) {
    const { status, data } = await CarritoAPI.agregar({
        producto_id: productoId,
        variacion_id: variacionId,
        producto_padre_id: padreId,
        cantidad: cantidad,
    });

    if (status === 401 && data.requiere_login) {
        if (typeof openAuthModal === 'function') {
            window._pendingCartAction = { productoId, variacionId, cantidad, padreId };
            openAuthModal('login');
        } else {
            window.location.href = '/';
        }
        return { ok: false, requiere_login: true };
    }

    if (data.ok) {
        Swal.fire({
            icon: 'success',
            title: 'Añadido al carrito',
            text: data.mensaje,
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
        });
        actualizarContadorCarrito();
        if (cartDropdownOpen) cargarMiniCarrito();
    } else {
        Swal.fire('Error', data.mensaje || 'No se pudo agregar', 'error');
    }

    return data;
}

function actualizarStockEnDOM(productoId, variacionId, cantidadAgregada) {
    const container = document.querySelector('.product-detail-container');
    if (!container) return;

    const esAgrupado = container.querySelector('.grouped-products-section') !== null;
    const esVariable = container.querySelector('.product-variations') !== null
                    || container.querySelector('#variacionesData') !== null;

    if (esAgrupado) {
        actualizarStockHijoAgrupado(productoId, cantidadAgregada);
        return;
    }

    if (esVariable && variacionId) {
        actualizarStockVariacion(variacionId, cantidadAgregada);
        return;
    }

    actualizarStockProductoSimple(cantidadAgregada);
}
function devolverStockEnDOM(productoId, variacionId, cantidad) {
    const container = document.querySelector('.product-detail-container');
    if (!container) return; 

    const productoEnVista = parseInt(container.dataset.productoId);
    if (!productoEnVista) return;

    if (productoEnVista === productoId) {
        const esAgrupado = container.querySelector('.grouped-products-section') !== null;
        const esVariable = container.querySelector('.product-variations') !== null
                        || container.querySelector('#variacionesData') !== null;

        if (esAgrupado) return; 
        if (esVariable && variacionId) {
            devolverStockVariacion(variacionId, cantidad);
        } else if (!esVariable) {
            devolverStockProductoSimple(cantidad);
        }
        return;
    }

    const esAgrupado = container.querySelector('.grouped-products-section') !== null;
    if (esAgrupado) {
        const hijoItem = document.querySelector(
            `.grouped-product-item[data-product-id="${productoId}"]`
        );
        if (hijoItem) {
            devolverStockHijoAgrupado(productoId, cantidad);
        }
    }
}
function devolverStockProductoSimple(cantidad) {
    const stockDiv = document.querySelector('.product-stock');
    const qtyInput = document.getElementById('qtyInput')
                  || document.querySelector('.cart-actions .qty-input:not(.grouped-qty-input)');

    if (!stockDiv) return;

    let stockActual = null;
    const matchInStock = stockDiv.textContent.match(/En stock \((\d+)\s*unidades?\)/i);
    if (matchInStock) {
        stockActual = parseInt(matchInStock[1]);
    } else {
        if (qtyInput) {
            const maxActual = parseInt(qtyInput.dataset.max || qtyInput.max || 0);
            stockActual = maxActual;
        }
    }

    if (stockActual === null) return;

    const nuevoStock = stockActual + cantidad;

    stockDiv.innerHTML = `<span class="in-stock">En stock (${nuevoStock} unidades)</span>`;

    if (qtyInput) {
        const maxActual = parseInt(qtyInput.dataset.max || qtyInput.max || 0);
        const nuevoMax = maxActual + cantidad;

        qtyInput.dataset.max = nuevoMax;
        qtyInput.max = nuevoMax;
        qtyInput.disabled = false;

        if (parseInt(qtyInput.value) === 0) {
            qtyInput.value = Math.min(1, nuevoMax);
        }

        const minusBtn = document.querySelector('.cart-actions .qty-btn.minus:not(.grouped-minus)');
        const plusBtn  = document.querySelector('.cart-actions .qty-btn.plus:not(.grouped-plus)');
        if (minusBtn && plusBtn) {
            const val = parseInt(qtyInput.value) || 1;
            minusBtn.disabled = val <= 1;
            plusBtn.disabled  = val >= nuevoMax || nuevoMax === 0;
        }
    }

    const cartActions = document.querySelector('.cart-actions');
    if (cartActions && cartActions.style.display === 'none') {
        cartActions.style.display = 'flex';
    }
}

function devolverStockVariacion(variacionId, cantidad) {
    if (Array.isArray(variacionesDataGlobal)) {
        const variacion = variacionesDataGlobal.find(v => v.id === variacionId);
        if (variacion) {
            variacion.stock = (parseInt(variacion.stock) || 0) + cantidad;
        }
    }

    if (variacionActual && variacionActual.variacion_id === variacionId) {
        variacionActual.stock = (parseInt(variacionActual.stock) || 0) + cantidad;
    }

    const option = document.querySelector(`.variation-option[data-variacion-id="${variacionId}"]`);
    if (option) {
        const stockAttr = parseInt(option.dataset.stock || 0);
        option.dataset.stock = stockAttr + cantidad;
    }

    const stockDiv = document.querySelector('.product-stock');
    if (stockDiv) {
        const match = stockDiv.textContent.match(/En stock \((\d+)\s*unidades?\)/i);
        if (match) {
            const stockActual = parseInt(match[1]);
            stockDiv.innerHTML = `<span class="in-stock">En stock (${stockActual + cantidad} unidades)</span>`;
        } else {
            if (variacionActual) {
                const nuevoStock = parseInt(variacionActual.stock) || 0;
                if (nuevoStock > 0) {
                    stockDiv.innerHTML = `<span class="in-stock">En stock (${nuevoStock} unidades)</span>`;
                }
            }
        }
    }

    const cartActions = document.querySelector('.cart-actions');
    if (cartActions && cartActions.style.display === 'none') {
        cartActions.style.display = 'flex';
    }

    if (variacionActual) {
        actualizarSelectorCantidad(variacionActual);
    }
}

function devolverStockHijoAgrupado(productoId, cantidad) {
    const item = document.querySelector(`.grouped-product-item[data-product-id="${productoId}"]`);
    if (!item) return;

    const qtyInput = item.querySelector('.grouped-qty-input');
    const stockSpan = item.querySelector('.grouped-product-stock');
    if (!qtyInput) return;

    let maxActual = parseInt(qtyInput.dataset.max || qtyInput.max || 0);
    const nuevoMax = maxActual + cantidad;

    qtyInput.dataset.max = nuevoMax;
    qtyInput.max = nuevoMax;
    qtyInput.disabled = false;

    if (stockSpan) {
        if (nuevoMax > 0) {
            stockSpan.innerHTML = `<span class="in-stock-small">En stock (${nuevoMax})</span>`;
        }
    }

    const minusBtn = item.querySelector('.grouped-minus');
    const plusBtn  = item.querySelector('.grouped-plus');
    if (minusBtn && plusBtn) {
        const val = parseInt(qtyInput.value) || 0;
        minusBtn.disabled = val <= 0;
        plusBtn.disabled  = val >= nuevoMax || nuevoMax <= 0;
    }
}

function actualizarStockHijoAgrupado(productoId, cantidad) {
    const item = document.querySelector(`.grouped-product-item[data-product-id="${productoId}"]`);
    if (!item) return;

    const qtyInput = item.querySelector('.grouped-qty-input');
    const stockSpan = item.querySelector('.grouped-product-stock');
    if (!qtyInput) return;

    const gestionaInventario = qtyInput.dataset.gestionInventario === '1'
                            || qtyInput.dataset.gestionInventario === 'true';

    const permiteBackorders = qtyInput.dataset.backorders === '1'
                           || qtyInput.dataset.backorders === 'true';

    qtyInput.value = 0;

    if (!gestionaInventario) {
        const minusBtn = item.querySelector('.grouped-minus');
        const plusBtn  = item.querySelector('.grouped-plus');
        if (minusBtn && plusBtn) {
            minusBtn.disabled = true;   
            plusBtn.disabled  = false; 
        }
        return;
    }

    let maxActual = parseInt(qtyInput.dataset.max, 10);
    if (isNaN(maxActual)) {
        maxActual = parseInt(qtyInput.getAttribute('max'), 10) || 0;
    }

    const nuevoMax = Math.max(0, maxActual - cantidad);
    qtyInput.dataset.max = String(nuevoMax);
    qtyInput.setAttribute('max', String(nuevoMax));

    if (stockSpan) {
        if (nuevoMax > 0) {
            stockSpan.innerHTML = `<span class="in-stock-small">En stock (${nuevoMax})</span>`;
        } else if (permiteBackorders) {
            stockSpan.innerHTML = `<span class="on-backorder-small">Por pedido</span>`;
        } else {
            stockSpan.innerHTML = `<span class="out-of-stock-small">Agotado</span>`;
        }
    }

    const minusBtn = item.querySelector('.grouped-minus');
    const plusBtn  = item.querySelector('.grouped-plus');
    if (minusBtn && plusBtn) {
        minusBtn.disabled = true;             
        plusBtn.disabled  = nuevoMax <= 0;    
    }

    if (nuevoMax <= 0) {
        qtyInput.disabled = true;
    }
}

function actualizarStockProductoSimple(cantidad) {
    const stockDiv = document.querySelector('.product-stock');
    const qtyInput = document.getElementById('qtyInput')
                  || document.querySelector('.cart-actions .qty-input:not(.grouped-qty-input)');

    let stockActual = null;
    if (stockDiv) {
        const match = stockDiv.textContent.match(/En stock \((\d+)\s*unidades?\)/i);
        if (match) {
            stockActual = parseInt(match[1]);
        }
    }

    if (stockActual === null && qtyInput) {
        stockActual = parseInt(qtyInput.dataset.max || qtyInput.max || 0);
        if (stockActual === 99) stockActual = null; 
    }

    if (stockActual === null) return;

    const nuevoStock = Math.max(0, stockActual - cantidad);

    if (stockDiv) {
        if (nuevoStock > 0) {
            stockDiv.innerHTML = `<span class="in-stock">En stock (${nuevoStock} unidades)</span>`;
        } else {
            const permiteBackorders = document.querySelector('.add-to-cart-btn.reserve-btn') !== null;
            if (permiteBackorders) {
                stockDiv.innerHTML = `<span class="on-backorder">Disponible por pedido</span>`;
            } else {
                stockDiv.innerHTML = `<span class="out-of-stock">Agotado</span>`;
                const cartActions = document.querySelector('.cart-actions');
                if (cartActions) cartActions.style.display = 'none';
            }
        }
    }

    if (qtyInput) {
        const maxActual = parseInt(qtyInput.dataset.max || qtyInput.max || 99);
        const nuevoMax = Math.max(0, maxActual - cantidad);

        qtyInput.dataset.max = nuevoMax;
        qtyInput.max = nuevoMax;

        if (nuevoMax === 0) {
            qtyInput.value = 0;
            qtyInput.disabled = true;
        } else {
            qtyInput.value = Math.min(1, nuevoMax);
            qtyInput.disabled = false;
        }

        const minusBtn = document.querySelector('.cart-actions .qty-btn.minus:not(.grouped-minus)');
        const plusBtn  = document.querySelector('.cart-actions .qty-btn.plus:not(.grouped-plus)');
        if (minusBtn && plusBtn) {
            const val = parseInt(qtyInput.value) || 0;
            minusBtn.disabled = val <= 1 || val === 0;
            plusBtn.disabled  = val >= nuevoMax || nuevoMax === 0;
        }
    }
}

function actualizarStockVariacion(variacionId, cantidad) {
    const stockDiv = document.querySelector('.product-stock');
    if (!stockDiv) return;

    const option = document.querySelector(`.variation-option[data-variacion-id="${variacionId}"]`);
    if (option) {
        const stockAttr = option.dataset.stock;
        if (stockAttr !== undefined) {
            const nuevoStock = Math.max(0, (parseInt(stockAttr) || 0) - cantidad);
            option.dataset.stock = nuevoStock;
        }
    }

    if (Array.isArray(variacionesDataGlobal)) {
        const variacion = variacionesDataGlobal.find(v => v.id === variacionId);
        if (variacion) {
            variacion.stock = Math.max(0, (parseInt(variacion.stock) || 0) - cantidad);
        }
    }

    if (variacionActual && variacionActual.variacion_id === variacionId) {
        const nuevoStock = Math.max(0, (parseInt(variacionActual.stock) || 0) - cantidad);
        variacionActual.stock = nuevoStock;
    }

    const stockActualTexto = stockDiv.textContent;
    const match = stockActualTexto.match(/En stock \((\d+)\s*unidades?\)/i);

    if (match) {
        const stockActual = parseInt(match[1]);
        const nuevoStock = Math.max(0, stockActual - cantidad);

        if (nuevoStock > 0) {
            stockDiv.innerHTML = `<span class="in-stock">En stock (${nuevoStock} unidades)</span>`;
        } else {
            const esReserva = variacionActual?.backorders === true
                           || variacionActual?.backorders === 1
                           || variacionActual?.estado_inventario === 'reservar'
                           || variacionActual?.estado_actual === 'por_pedido';

            if (esReserva) {
                stockDiv.innerHTML = `<span class="on-backorder">Disponible por pedido</span>`;
            } else {
                stockDiv.innerHTML = `<span class="out-of-stock">Agotado</span>`;

                const cartActions = document.querySelector('.cart-actions');
                if (cartActions) cartActions.style.display = 'none';
            }
        }
    }

    if (variacionActual) {
        actualizarSelectorCantidad(variacionActual);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    actualizarContadorCarrito();

    const cartBtn = document.getElementById('cartBtn');
    const closeBtn = document.getElementById('closeCartDropdown');

    if (cartBtn) {
        cartBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleCartDropdown();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleCartDropdown(false);
        });
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('.cart-dropdown')) return;
        if (e.target.closest('.js-mini-remove')) return;   
        if (e.target.closest('#cartBtnWrapper')) return;

        const wrapper = document.getElementById('cartBtnWrapper');
        if (!wrapper) return;
        if (!wrapper.contains(e.target)) {
            toggleCartDropdown(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cartDropdownOpen) toggleCartDropdown(false);
    });
});

window.CarritoAPI = CarritoAPI;
window.agregarAlCarrito = agregarAlCarrito;
window.actualizarContadorCarrito = actualizarContadorCarrito;