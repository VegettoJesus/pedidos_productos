<div class="carrito-page">
    <div class="carrito-header-page">
        <h1><i class="bi bi-cart3"></i> Mi carrito de compras</h1>
        <p id="carritoSubtitulo">Revisa tus productos antes de continuar</p>
    </div>

    <div id="carritoContenedor">
        <div class="carrito-vacio">
            <i class="bi bi-hourglass-split"></i>
            <h3>Cargando carrito...</h3>
        </div>
    </div>

    <section id="crossSellsSection" class="cross-sells-section" style="display:none;">
        <h2 class="cross-sells-title">
            <i class="bi bi-stars"></i> Quizá te interese añadir
        </h2>
        <div class="product-carousel" id="crossSellsCarousel">
            {{-- Se llena vía JS --}}
        </div>
    </section>
</div>

<template id="carritoItemTemplate">
    <div class="carrito-item" data-item-id="">
        <div class="carrito-item-producto">
            <div class="carrito-item-imagen">
                <img src="" alt="" class="js-img" style="display:none;">
                <i class="bi bi-box js-placeholder"></i>
            </div>
            <div class="carrito-item-detalle">
                <div class="carrito-item-titulo">
                    <a href="#" class="js-link"></a>
                </div>
                <div class="carrito-item-atributos js-atributos"></div>
                <div class="carrito-item-sku js-sku" style="display:none;"></div>
                <div class="carrito-item-bundle-tag js-bundle" style="display:none;">
                    <i class="bi bi-box-seam"></i>
                    <span>Parte de un paquete</span>
                </div>
            </div>
        </div>

        <div class="carrito-item-precio js-precio">S/. 0.00</div>

        <div class="carrito-item-cantidad">
            <div class="cart-qty">
                <button type="button" class="js-qty-minus"><i class="bi bi-dash"></i></button>
                <input type="number" class="js-qty-input" value="1" min="1" readonly>
                <button type="button" class="js-qty-plus"><i class="bi bi-plus"></i></button>
            </div>
        </div>

        <div class="carrito-item-subtotal js-subtotal">S/. 0.00</div>

        <div class="carrito-item-acciones">
            <button type="button" class="carrito-item-remove js-remove" title="Eliminar">
                <i class="bi bi-trash3"></i>
            </button>
        </div>
    </div>
</template>