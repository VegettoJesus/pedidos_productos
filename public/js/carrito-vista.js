'use strict';

document.addEventListener('DOMContentLoaded', async () => {
    await renderCarrito();
});

async function renderCarrito() {
    const contenedor = document.getElementById('carritoContenedor');
    const subtitulo = document.getElementById('carritoSubtitulo');

    const { status, data } = await window.CarritoAPI.index();

    if (status === 401) {
        window.location.href = '/';
        return;
    }

    if (!data.ok || !data.items || data.items.length === 0) {
        contenedor.innerHTML = `
            <div class="carrito-vacio">
                <i class="bi bi-cart-x"></i>
                <h3>Tu carrito está vacío</h3>
                <p>Explora nuestros productos y añade algo especial</p>
                <a href="${window.location.origin}/todos-productos" class="btn-checkout" style="max-width:280px;margin:0 auto;">
                    <i class="bi bi-shop"></i> Ir a la tienda
                </a>
            </div>`;
        subtitulo.textContent = '';
        return;
    }

    subtitulo.textContent = `${data.total_items} producto${data.total_items !== 1 ? 's' : ''} en tu carrito`;

    const items = [...data.items].sort((a, b) => {
        if (a.producto_padre_id && a.producto_padre_id === b.producto_padre_id) return 0;
        if (a.producto_padre_id) return 1;
        if (b.producto_padre_id) return -1;
        return a.id - b.id;
    });

    const template = document.getElementById('carritoItemTemplate');

    let html = `
        <div class="carrito-grid">
            <div>
                <div class="carrito-items-wrapper">
                    <div class="carrito-items-header">
                        <span>Producto</span>
                        <span style="text-align:center;">Precio</span>
                        <span style="text-align:center;">Cantidad</span>
                        <span style="text-align:center;">Subtotal</span>
                        <span></span>
                    </div>
                    <div id="carritoItemsList"></div>
                </div>

                <div class="carrito-acciones">
                    <a href="${window.location.origin}/todos-productos" class="btn-seguir-comprando">
                        <i class="bi bi-arrow-left"></i> Seguir comprando
                    </a>
                    <button class="btn-vaciar-carrito" id="btnVaciarCarrito">
                        <i class="bi bi-trash3"></i> Vaciar carrito
                    </button>
                </div>
            </div>

            <div class="carrito-resumen">
                <h3><i class="bi bi-receipt"></i> Resumen de compra</h3>
                <div class="resumen-linea">
                    <span>Subtotal (${data.total_items} items)</span>
                    <span>${window.formatPrecio ? window.formatPrecio(data.total) : 'S/. ' + Number(data.total).toFixed(2)}</span>
                </div>
                <div class="resumen-linea">
                    <span>Envío</span>
                    <span style="color:#999;">Calculado al pagar</span>
                </div>
                <div class="resumen-linea total">
                    <span>Total</span>
                    <span>${window.formatPrecio ? window.formatPrecio(data.total) : 'S/. ' + Number(data.total).toFixed(2)}</span>
                </div>

                <a href="${window.location.origin}/checkout" class="btn-checkout">
                    <i class="bi bi-credit-card-2-front"></i> Proceder al pago
                </a>
            </div>
        </div>
    `;

    contenedor.innerHTML = html;
    const lista = document.getElementById('carritoItemsList');

    items.forEach(item => lista.appendChild(crearItem(item, template)));
    await renderCrossSells();

    document.getElementById('btnVaciarCarrito')?.addEventListener('click', async () => {
        const r = await Swal.fire({
            icon: 'warning',
            title: '¿Vaciar carrito?',
            text: 'Se eliminarán todos los productos',
            showCancelButton: true,
            confirmButtonText: 'Sí, vaciar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e74c3c',
        });

        if (!r.isConfirmed) return;

        const res = await window.CarritoAPI.vaciar();
        if (res.data.ok) {
            window.actualizarContadorCarrito();
            renderCarrito();
        }
    });
}

async function renderCrossSells() {
    const section = document.getElementById('crossSellsSection');
    const carousel = document.getElementById('crossSellsCarousel');
    if (!section || !carousel) return;

    try {
        const res = await fetch('/carrito/cross-sells', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        const data = await res.json();

        if (!data.ok || !data.html || data.count === 0) {
            section.style.display = 'none';
            carousel.innerHTML = '';
            return;
        }

        section.style.display = 'block';
        carousel.innerHTML = data.html;

        if (typeof initProductSliders === 'function') initProductSliders();
        if (typeof initCardVariations === 'function') initCardVariations();
        if (typeof initProductRating === 'function') initProductRating();
    } catch (e) {
        console.error('Error cargando cross-sells:', e);
        section.style.display = 'none';
    }
}

function crearItem(item, template) {
    const clone = template.content.cloneNode(true);
    const root = clone.querySelector('.carrito-item');
    root.dataset.itemId = item.id;

    const img = window.imagenProducto ? window.imagenProducto(item) : null;
    const imgEl = root.querySelector('.js-img');
    const placeholder = root.querySelector('.js-placeholder');

    if (img) {
        imgEl.src = img;
        imgEl.style.display = 'block';
        placeholder.style.display = 'none';
    }

    // Título + link
    const titulo = item.producto?.nombre || 'Producto';
    const linkEl = root.querySelector('.js-link');
    linkEl.textContent = titulo;
    linkEl.href = `/producto/${item.producto_id}`;

    // Atributos de variación
    const atributosEl = root.querySelector('.js-atributos');
    if (item.variacion && Array.isArray(item.variacion.atributos) && item.variacion.atributos.length) {
        atributosEl.innerHTML = item.variacion.atributos
            .map(a => `<span class="carrito-item-atributo-chip">${escapeHtml(a.nombre)}</span>`)
            .join('');
    }

    // SKU
    const sku = item.variacion?.sku || item.producto?.sku;
    if (sku) {
        const skuEl = root.querySelector('.js-sku');
        skuEl.innerHTML = `SKU: <strong>${escapeHtml(sku)}</strong>`;
        skuEl.style.display = 'block';
    }

    // Bundle tag
    if (item.producto_padre_id) {
        root.querySelector('.js-bundle').style.display = 'inline-flex';
    }

    // Precio
    root.querySelector('.js-precio').innerHTML =
        `${formatPrecio(item.precio_unitario)}` +
        (item.variacion_id ? '<small>Variación</small>' : '');

    // Cantidad
    const qtyInput = root.querySelector('.js-qty-input');
    const minusBtn = root.querySelector('.js-qty-minus');
    const plusBtn = root.querySelector('.js-qty-plus');
    qtyInput.value = item.cantidad;

    const vendidoIndividual = item.producto?.vendido_individualmente;
    if (vendidoIndividual) {
        minusBtn.disabled = true;
        plusBtn.disabled = true;
    }

    minusBtn.addEventListener('click', async () => {
        const n = parseInt(qtyInput.value) - 1;
        if (n < 1) return;
        await cambiarCantidad(item.id, n);
    });

    plusBtn.addEventListener('click', async () => {
        const n = parseInt(qtyInput.value) + 1;
        await cambiarCantidad(item.id, n);
    });

    // Subtotal
    root.querySelector('.js-subtotal').textContent = formatPrecio(item.cantidad * item.precio_unitario);

    // Eliminar
    root.querySelector('.js-remove').addEventListener('click', async () => {
        const r = await Swal.fire({
            icon: 'question',
            title: '¿Eliminar producto?',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e74c3c',
        });
        if (!r.isConfirmed) return;

        const res = await window.CarritoAPI.eliminar(item.id);
        if (res.data.ok) {
            window.actualizarContadorCarrito();
            renderCarrito();
        } else {
            Swal.fire('Error', res.data.mensaje || 'No se pudo eliminar', 'error');
        }
    });

    return clone;
}

async function cambiarCantidad(itemId, cantidad) {
    const res = await window.CarritoAPI.actualizar(itemId, cantidad);
    if (res.data.ok) {
        window.actualizarContadorCarrito();
        renderCarrito();
    } else {
        Swal.fire('Atención', res.data.mensaje || 'No se pudo actualizar', 'warning');
    }
}

function formatPrecio(n) {
    return 'S/ ' + Number(n).toFixed(2);
}

function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
}