let variacionesDataGlobal = [];
let productoIdGlobal = null;
let ordenAtributos = [];
let variacionSeleccionada = null;
let variacionActual = null;
let esProductoVariable = false;
let descripcionOriginal = '';
let vendidoIndividualmenteGlobal = false;

async function obtenerTerminosDisponibles(productoId, terminosIds) {
    try {
        
        const response = await axios.post('/api/productos/terminos-disponibles', {
            producto_id: productoId,
            terminos: terminosIds
        });
        
        if (response.data.success) {
            return response.data.data;
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: response.data.message || 'No se pudieron obtener los términos disponibles',
                confirmButtonColor: '#f39c12'
            });
            return null;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
            confirmButtonColor: '#e74c3c'
        });
        return null;
    }
}

async function actualizarOpcionesPorTerminos(productoId, terminosIds) {
    const atributos = await obtenerTerminosDisponibles(productoId, terminosIds);
    
    if (!atributos) return;
    
    const primerAtributoId = ordenAtributos.length > 0 ? ordenAtributos[0] : null;
    
    atributos.forEach(atributo => {
        const grupo = document.querySelector(`.variation-options[data-atributo-id="${atributo.atributo_id}"]`);
        if (!grupo) return;
        
        const esPrimero = (String(atributo.atributo_id) === String(primerAtributoId));
        
        if (esPrimero) {
            const opciones = grupo.querySelectorAll('.variation-option');
            opciones.forEach(option => {
                option.classList.remove('disabled');
                option.style.opacity = '1';
                option.style.pointerEvents = 'auto';
                option.style.cursor = 'pointer';
                const badge = option.querySelector('.stock-badge');
                if (badge) badge.remove();
            });
            return;
        }
        
        const opciones = grupo.querySelectorAll('.variation-option');
        const terminosMap = {};
        atributo.terminos.forEach(t => {
            terminosMap[t.id] = t;
        });
        
        opciones.forEach(option => {
            const terminoId = parseInt(option.dataset.terminoId);
            const esActiva = option.classList.contains('active');
            
            if (esActiva) {
                const info = terminosMap[terminoId];
                if (!info || !info.tiene_stock) {
                    option.classList.remove('active');
                    option.classList.add('disabled');
                    option.style.opacity = '0.3';
                    option.style.pointerEvents = 'none';
                    option.style.cursor = 'not-allowed';
                    return;
                }
                option.classList.remove('disabled');
                option.style.opacity = '1';
                option.style.pointerEvents = 'auto';
                option.style.cursor = 'pointer';
                const badge = option.querySelector('.stock-badge');
                if (badge) badge.remove();
                return;
            }
            
            const info = terminosMap[terminoId];
            const badge = option.querySelector('.stock-badge');
            if (badge) badge.remove();
            
            if (!info) {
                option.classList.add('disabled');
                option.style.opacity = '0.3';
                option.style.pointerEvents = 'none';
                option.style.cursor = 'not-allowed';
                option.title = option.textContent.trim() + ' (No disponible)';
            } else if (info.tiene_stock) {
                option.classList.remove('disabled');
                option.style.opacity = '1';
                option.style.pointerEvents = 'auto';
                option.style.cursor = 'pointer';
                option.title = option.textContent.trim();
                
            } else {
                option.classList.add('disabled');
                option.style.opacity = '0.5';
                option.style.pointerEvents = 'none';
                option.style.cursor = 'not-allowed';
                option.title = option.textContent.trim() + ' (Sin stock)';
            }
        });
    });
}

// 🔥 Función para obtener el atributo ID de un término
function getAtributoIdByTermino(terminoId) {
    // Buscar en todas las variaciones
    for (const variacion of variacionesDataGlobal) {
        if (variacion.atributos && Array.isArray(variacion.atributos)) {
            const found = variacion.atributos.find(a => a.id === terminoId);
            if (found) {
                return found.atributo_id;
            }
        }
    }
    
    // 🔥 Si no se encuentra, buscar en el DOM
    const option = document.querySelector(`.variation-option[data-termino-id="${terminoId}"]`);
    if (option) {
        const attrId = option.dataset.atributoId;
        if (attrId) {
            return parseInt(attrId);
        }
    }
    
    return null;
}

// 🔥 Función para actualizar imágenes de la variación
function actualizarImagenesVariacion(variacion) {
    const mainImage = document.getElementById('mainProductImage');
    let thumbnailList = document.querySelector('.thumbnail-list');
    
    if (!thumbnailList) {
        const gallery = document.querySelector('.product-gallery');
        if (gallery) {
            thumbnailList = document.createElement('div');
            thumbnailList.className = 'thumbnail-list';
            gallery.appendChild(thumbnailList);
        } else {
            return;
        }
    }
    
    if (variacion.imagenes && variacion.imagenes.length > 0) {
        const imagenesConUrl = variacion.imagenes.map(img => {
            let path = '';
            if (typeof img === 'object' && img !== null) {
                path = img.imagen_path || img.path || '';
            } else if (typeof img === 'string') {
                path = img;
            }
            
            if (path && !path.startsWith('/')) {
                return '/' + path;
            }
            return path;
        }).filter(img => img !== '');
        
        if (imagenesConUrl.length === 0) {
            return;
        }
        
        if (mainImage) {
            mainImage.src = imagenesConUrl[0];
            mainImage.alt = `Variación ${variacion.sku || ''}`;
        }
        
        thumbnailList.innerHTML = '';
        imagenesConUrl.forEach((img, index) => {
            const thumb = document.createElement('div');
            thumb.className = `thumbnail ${index === 0 ? 'active' : ''}`;
            thumb.dataset.image = img;
            thumb.innerHTML = `<img src="${img}" alt="Variación ${index + 1}">`;
            thumbnailList.appendChild(thumb);
            
            thumb.addEventListener('click', function() {
                document.getElementById('mainProductImage').src = this.dataset.image;
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        thumbnailList.style.display = 'flex';
    } else {
        thumbnailList.style.display = 'none';
        thumbnailList.innerHTML = '';
    }
}

function actualizarUI(variacion) {
    if (!variacion || typeof variacion !== 'object') {
        return;
    }
    
    // 1. ACTUALIZAR PRECIO
    const precioContainer = document.querySelector('.product-price-detailed');
    if (precioContainer) {
        const precioRegular = parseFloat(variacion.precio_regular) || 0;
        const precioRebajado = parseFloat(variacion.precio_rebajado) || 0;
        const precioFinal = parseFloat(variacion.precio_final) || precioRegular;
        const descuento = parseInt(variacion.descuento_porcentaje) || 0;
        
        if (precioRebajado > 0 && descuento > 0) {
            precioContainer.innerHTML = `
                <span class="old-price">S/. ${precioRegular.toFixed(2)}</span>
                <span class="sale-price">S/. ${precioFinal.toFixed(2)}</span>
                <span class="discount-badge-large">-${descuento}%</span>
            `;
        } else {
            precioContainer.innerHTML = `
                <span class="regular-price">S/. ${precioFinal.toFixed(2)}</span>
            `;
        }
    }
    
    // 2. ACTUALIZAR STOCK
    const stockDiv = document.querySelector('.product-stock');
    if (stockDiv) {
        const stock = parseInt(variacion.stock) || 0;
        const estadoActual = variacion.estado_actual || 'agotado';
        
        const estadoMap = {
            'en_stock': `<span class="in-stock">En stock (${stock} unidades)</span>`,
            'disponible': `<span class="in-stock">Disponible</span>`,
            'por_pedido': `<span class="on-backorder">Disponible por pedido</span>`,
            'agotado': `<span class="out-of-stock">Agotado</span>`
        };
        
        stockDiv.innerHTML = estadoMap[estadoActual] || estadoMap['agotado'];
    }
    
    // 3. ACTUALIZAR SKU
    const skuElement = document.getElementById('productSku');
    if (skuElement) {
        if (variacion.sku) {
            skuElement.innerHTML = `<span class="sku-label">SKU:</span> <span class="sku-value">${variacion.sku}</span>`;
        } else {
            skuElement.innerHTML = '';
        }
    }

    // 4. ACTUALIZAR DESCRIPCIÓN CORTA
    const shortDescription = document.querySelector('.product-short-description');
    if (shortDescription) {
        if (variacion.descripcion) {
            shortDescription.innerHTML = nl2br(variacion.descripcion);
        } else {
            shortDescription.innerHTML = descripcionOriginal;
        }
    }
    
    // 5. ACTUALIZAR IMÁGENES
    actualizarImagenesVariacion(variacion);
    
    // 6. ACTUALIZAR CONTENEDOR DE INFORMACIÓN
    const infoDiv = document.getElementById('selectedVariationInfo');
    if (infoDiv) {
        infoDiv.style.display = 'block';
        
        const priceDiv = infoDiv.querySelector('.selected-variation-price');
        if (priceDiv) {
            if (variacion.precio_rebajado && parseFloat(variacion.precio_rebajado) > 0) {
                priceDiv.innerHTML = `
                    <span class="old-price">S/. ${parseFloat(variacion.precio_regular).toFixed(2)}</span>
                    <span class="sale-price">S/. ${parseFloat(variacion.precio_final).toFixed(2)}</span>
                    <span class="discount-badge">-${variacion.descuento_porcentaje}%</span>
                `;
            } else {
                priceDiv.innerHTML = `<span class="regular-price">S/. ${parseFloat(variacion.precio_final).toFixed(2)}</span>`;
            }
        }
        
        const skuInfoDiv = infoDiv.querySelector('.selected-variation-sku');
        if (skuInfoDiv && variacion.sku) {
            skuInfoDiv.innerHTML = `📦 SKU: ${variacion.sku}`;
        }
    }
    
    // 7. ACTUALIZAR ESPECIFICACIONES
    actualizarEspecificaciones(variacion);
    
    // 🔥 8. ACTUALIZAR SELECTOR DE CANTIDAD (NUEVO)
    actualizarSelectorCantidad(variacion);
    
    // 9. MOSTRAR BOTÓN DE CARRITO
    const cartActions = document.querySelector('.cart-actions');
    if (cartActions) {
        const estadoActual = variacion.estado_actual || 'agotado';
        
        if (estadoActual !== 'agotado') {
            cartActions.style.display = 'flex';
            
            const addToCartBtn = cartActions.querySelector('.add-to-cart-btn');
            if (addToCartBtn) {
                const tieneBackorders = variacion.backorders === true || 
                                       variacion.backorders === 1 || 
                                       variacion.backorders === '1' || 
                                       variacion.backorders === 'true';
                
                const esReservar = variacion.estado_inventario === 'reservar';
                const esPorPedido = variacion.estado_actual === 'por_pedido';
                
                const debeSerReserva = tieneBackorders || esReservar || esPorPedido;
                
                if (debeSerReserva) {
                    addToCartBtn.innerHTML = '<i class="bi bi-clock-history"></i> Reservar producto';
                    addToCartBtn.className = 'add-to-cart-btn reserve-btn';
                    addToCartBtn.dataset.backorders = 'true';
                } else {
                    addToCartBtn.innerHTML = '<i class="bi bi-cart-plus"></i> Añadir al carrito';
                    addToCartBtn.className = 'add-to-cart-btn';
                    addToCartBtn.dataset.backorders = 'false';
                }
            }
        } else {
            cartActions.style.display = 'none';
        }
    }
    
    variacionSeleccionada = variacion;
    variacionActual = variacion;
}

function actualizarSelectorCantidad(variacion) {
    if (!esProductoVariable) {
        return;
    }
    
    const cartActions = document.querySelector('.cart-actions');
    if (!cartActions) {
        return;
    }
    
    const qtyInput = cartActions.querySelector('.qty-input:not(.grouped-qty-input)') || 
                     cartActions.querySelector('#qtyInput');
    
    const minusBtn = cartActions.querySelector('.qty-btn.minus:not(.grouped-minus)');
    const plusBtn = cartActions.querySelector('.qty-btn.plus:not(.grouped-plus)');
    
    if (!qtyInput) {
        return;
    }
    
    const estadoActual = variacion.estado_actual || 'agotado';
    const stock = parseInt(variacion.stock) || 0;
    const gestionInventario = variacion.gestion_inventario === true || 
                             variacion.gestion_inventario === 1 || 
                             variacion.gestion_inventario === '1' || 
                             variacion.gestion_inventario === 'true';
    
    let maxValue = 99;
    
    // Si se vende individualmente, máximo 1
    if (vendidoIndividualmenteGlobal) {
        maxValue = 1;
    }
    // 🔥 SOLO si el estado NO es 'por_pedido' ni 'agotado'
    else if (estadoActual !== 'por_pedido' && estadoActual !== 'agotado') {
        if (gestionInventario && stock > 0) {
            maxValue = stock;
        } else if (!gestionInventario && (estadoActual === 'en_stock' || estadoActual === 'disponible')) {
            maxValue = 99;
        }
    }
    
    qtyInput.max = maxValue;
    qtyInput.dataset.max = maxValue;
    qtyInput.setAttribute('max', maxValue);
    
    let currentVal = parseInt(qtyInput.value) || 1;
    if (currentVal > maxValue) {
        qtyInput.value = maxValue;
        currentVal = maxValue;
    }
    if (currentVal < 1) {
        qtyInput.value = 1;
        currentVal = 1;
    }
    
    if (minusBtn && plusBtn) {
        minusBtn.disabled = currentVal <= 1;
        plusBtn.disabled = currentVal >= maxValue;
        
        if (maxValue === 1) {
            qtyInput.disabled = true;
            qtyInput.value = 1;
            minusBtn.disabled = true;
            plusBtn.disabled = true;
        } else {
            qtyInput.disabled = false;
        }
    }
}

function actualizarEspecificaciones(variacion) {
    const grid = document.querySelector('.specifications-grid');
    if (!grid) return;
    
    function findSpecItem(className, labelContains) {
        let item = grid.querySelector(`.spec-item.${className}`);
        if (!item && labelContains) {
            const items = grid.querySelectorAll('.spec-item');
            for (const el of items) {
                const label = el.querySelector('.spec-label');
                if (label && label.textContent.includes(labelContains)) {
                    item = el;
                    break;
                }
            }
        }
        return item;
    }
    
    function createSpecItem(className, label) {
        const item = document.createElement('div');
        item.className = `spec-item ${className}`;
        item.innerHTML = `
            <span class="spec-label">${label}</span>
            <span class="spec-value"></span>
        `;
        return item;
    }
    
    const tienePeso = variacion.peso && variacion.peso > 0;
    let pesoItem = findSpecItem('peso-item', 'Peso');
    
    if (tienePeso) {
        if (!pesoItem) {
            pesoItem = createSpecItem('peso-item', 'Peso');
            grid.appendChild(pesoItem);
        }
        const valueEl = pesoItem.querySelector('.spec-value');
        if (valueEl) {
            valueEl.textContent = `${parseFloat(variacion.peso).toFixed(2)} ${variacion.peso_unidad || 'kg'}`;
        }
    } else {
        if (pesoItem) {
            pesoItem.remove();
        }
    }
    
    let dimensionesText = '';
    if (variacion.longitud) dimensionesText += `${parseFloat(variacion.longitud).toFixed(2)} cm`;
    if (variacion.anchura) dimensionesText += ` × ${parseFloat(variacion.anchura).toFixed(2)} cm`;
    if (variacion.altura) dimensionesText += ` × ${parseFloat(variacion.altura).toFixed(2)} cm`;
    
    const tieneDimensiones = dimensionesText !== '';
    let dimensionesItem = findSpecItem('dimensiones-item', 'Dimensiones');
    
    if (tieneDimensiones) {
        if (!dimensionesItem) {
            dimensionesItem = createSpecItem('dimensiones-item', 'Dimensiones');
            grid.appendChild(dimensionesItem);
        }
        const valueEl = dimensionesItem.querySelector('.spec-value');
        if (valueEl) {
            valueEl.textContent = dimensionesText;
        }
    } else {
        if (dimensionesItem) {
            dimensionesItem.remove();
        }
    }
}

function nl2br(str) {
    if (!str) return '';
    return str.replace(/\n/g, '<br>');
}

async function fetchVariacionDetalle(productoId, variacionId) {
    try {
        const response = await axios.post('/api/productos/variacion-detalle', {
            producto_id: productoId,
            variacion_id: variacionId
        });

        if (response.data.success) {
            return response.data.data;
        } else {
            return null;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error al cargar la variación',
            text: 'No se pudo obtener los detalles de la variación. Intenta recargar la página.',
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Recargar',
            showCancelButton: true,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                location.reload();
            }
        });
        return null;
    }
}

async function obtenerOrdenAtributos(productoId) {
    try {
        const response = await axios.post('/api/productos/orden-atributos', {
            producto_id: productoId
        });
        
        if (response.data.success) {
            ordenAtributos = response.data.data.map(id => String(id));
            return ordenAtributos;
        }
    } catch (error) {
    }
    
    // Fallback: obtener del DOM
    const groups = document.querySelectorAll('.variation-options');
    ordenAtributos = [];
    groups.forEach(group => {
        const atributoId = group.dataset.atributoId;
        if (atributoId) {
            ordenAtributos.push(String(atributoId));
        }
    });
    return ordenAtributos;
}

function esPrimerAtributo(atributoId) {
    return ordenAtributos.length > 0 && ordenAtributos[0] === String(atributoId);
}

function getIndiceAtributo(atributoId) {
    return ordenAtributos.indexOf(String(atributoId));
}

function getTerminosSeleccionadosHasta(atributoIdSeleccionado) {
    const seleccionados = [];
    const indiceSeleccionado = getIndiceAtributo(atributoIdSeleccionado);
    
    for (let i = 0; i <= indiceSeleccionado && i < ordenAtributos.length; i++) {
        const atributoId = ordenAtributos[i];
        const grupo = document.querySelector(`.variation-options[data-atributo-id="${atributoId}"]`);
        if (grupo) {
            const selected = grupo.querySelector('.variation-option.active');
            if (selected && selected.dataset.terminoId) {
                const terminoNombre = selected.textContent.trim().toLowerCase();
                if (!terminoNombre.includes('cualquier') && !terminoNombre.includes('any') && 
                    !terminoNombre.includes('todas') && !terminoNombre.includes('todos')) {
                    seleccionados.push(parseInt(selected.dataset.terminoId));
                }
            }
        }
    }
    
    return seleccionados;
}

function tieneStockDisponible(variacion) {
    if (!variacion || typeof variacion !== 'object') return false;
    
    const stock = parseInt(variacion.stock) || 0;
    const gestionInventario = variacion.gestion_inventario === true || 
                             variacion.gestion_inventario === 1 || 
                             variacion.gestion_inventario === '1' || 
                             variacion.gestion_inventario === 'true';
    const backorders = variacion.backorders === true || 
                      variacion.backorders === 1 || 
                      variacion.backorders === '1' || 
                      variacion.backorders === 'true';
    const estadoInventario = variacion.estado_inventario || '';
    const estadoActual = variacion.estado_actual || ''; 
    
    if (gestionInventario && stock > 0) return true;
    if (gestionInventario && stock <= 0 && backorders) return true;
    if (!gestionInventario && (estadoInventario === 'existe' || estadoInventario === 'reservar')) return true;
    if (estadoActual === 'en_stock' || estadoActual === 'disponible' || estadoActual === 'por_pedido') return true;
    return false;
}

function limpiarSeleccionesPosteriores(atributoIdSeleccionado) {
    const indiceSeleccionado = getIndiceAtributo(atributoIdSeleccionado);
    
    for (let i = indiceSeleccionado + 1; i < ordenAtributos.length; i++) {
        const atributoId = ordenAtributos[i];
        const grupo = document.querySelector(`.variation-options[data-atributo-id="${atributoId}"]`);
        if (grupo) {
            grupo.querySelectorAll('.variation-option.active').forEach(opt => {
                opt.classList.remove('active');
            });
            grupo.querySelectorAll('.variation-option').forEach(opt => {
                opt.classList.add('disabled');
                opt.style.opacity = '0.5';
                opt.style.pointerEvents = 'none';
                opt.style.cursor = 'not-allowed';
                opt.title = opt.textContent.trim() + ' (Selecciona primero el atributo anterior)';
                const badge = opt.querySelector('.stock-badge');
                if (badge) badge.remove();
            });
        }
    }
}

function actualizarMensajeSeleccion() {
    if (!esProductoVariable) return;
    
    const productStock = document.querySelector('.product-stock');
    const cartActions = document.querySelector('.cart-actions');
    const grupos = document.querySelectorAll('.variation-options');
    const tieneSeleccion = document.querySelector('.variation-option.active') !== null;
    
    if (productStock) {
        // Verificar si todos los atributos están seleccionados
        let todosSeleccionados = true;
        let seleccionesValidas = 0;
        
        grupos.forEach(group => {
            const selected = group.querySelector('.variation-option.active');
            if (!selected) {
                todosSeleccionados = false;
            } else if (selected.dataset.terminoId) {
                seleccionesValidas++;
            }
        });
        
        // 🔥 SOLO mostrar mensaje si hay al menos una selección y no están todos seleccionados
        if (tieneSeleccion && !todosSeleccionados) {
            productStock.innerHTML = '<span class="text-muted">⏳ Selecciona todas las opciones</span>';
        } else if (!tieneSeleccion) {
            // Si no hay selección, mantener el mensaje inicial
            if (!productStock.dataset.mensajeInicial) {
                productStock.dataset.mensajeInicial = productStock.innerHTML;
            }
        } else if (todosSeleccionados && seleccionesValidas === grupos.length) {
            // Todos seleccionados - el stock se actualizará en handleVariacionClick
        }
    }
    
    if (cartActions) {
        const grupos = document.querySelectorAll('.variation-options');
        let todosSeleccionados = true;
        
        grupos.forEach(group => {
            const selected = group.querySelector('.variation-option.active');
            if (!selected) {
                todosSeleccionados = false;
            }
        });
        
        if (todosSeleccionados) {
        } else {
            cartActions.style.display = 'none';
        }
    }
}

// 🔥 MANEJADOR DE CLIC (SOLO PARA PRODUCTOS VARIABLES)
async function handleVariacionClick(event) {
    const option = this;
    const parentGroup = option.closest('.variation-options');
    if (!parentGroup) return;
    
    if (option.classList.contains('disabled')) return;
    
    const atributoId = option.dataset.atributoId;
    
    // Remover active del grupo
    parentGroup.querySelectorAll('.variation-option').forEach(opt => {
        opt.classList.remove('active');
    });
    
    // Activar esta opción
    option.classList.add('active');
    
    const productoId = productoIdGlobal;
    
    // Limpiar selecciones posteriores
    limpiarSeleccionesPosteriores(atributoId);
    
    // Obtener términos seleccionados
    const terminosSeleccionados = getTerminosSeleccionadosHasta(atributoId);
    
    // Actualizar opciones disponibles
    if (terminosSeleccionados.length > 0) {
        await actualizarOpcionesPorTerminos(productoId, terminosSeleccionados);
    }
    
    // ACTUALIZAR MENSAJE DE SELECCIÓN
    actualizarMensajeSeleccion();
    
    // VERIFICAR SI TODOS LOS ATRIBUTOS ESTÁN SELECCIONADOS
    const grupos = document.querySelectorAll('.variation-options');
    let todasSeleccionadas = true;
    const terminosFinales = [];
    
    grupos.forEach(group => {
        const selected = group.querySelector('.variation-option.active');
        if (!selected) {
            todasSeleccionadas = false;
        } else if (selected.dataset.terminoId) {
            terminosFinales.push(parseInt(selected.dataset.terminoId));
        }
    });
    
    // 🔥 SOLO SI TODOS LOS ATRIBUTOS ESTÁN SELECCIONADOS
    if (todasSeleccionadas && terminosFinales.length === grupos.length && terminosFinales.length > 0) {
        
        // 🔥 Buscar la variación que coincide en variacionesDataGlobal
        const variacionExacta = variacionesDataGlobal.find(variacion => {
            if (!variacion || !variacion.atributos || !Array.isArray(variacion.atributos)) {
                return false;
            }
            
            const attrIds = variacion.atributos.map(a => a.id);
            
            return terminosFinales.every(tId => {
                if (attrIds.includes(tId)) {
                    return true;
                }
                
                const atributoDelTermino = getAtributoIdByTermino(tId);
                if (!atributoDelTermino) return false;
                
                const tieneAtributo = variacion.atributos.some(a => 
                    a.atributo_id === atributoDelTermino
                );
                
                return !tieneAtributo;
            });
        });
        
        
        if (variacionExacta) {
            const detalle = await fetchVariacionDetalle(productoId, variacionExacta.id);
            
            if (detalle && tieneStockDisponible(detalle)) {
                // ACTUALIZAR UI CON LA VARIACIÓN COMPLETA
                actualizarUI(detalle);
                variacionActual = detalle;
            } else {
                // No tiene stock disponible
                const productStock = document.querySelector('.product-stock');
                if (productStock) {
                    const estadoActual = detalle?.estado_actual || 'agotado';
                    const estadoMap = {
                        'en_stock': `<span class="in-stock">En stock (${detalle?.stock || 0} unidades)</span>`,
                        'disponible': `<span class="in-stock">Disponible</span>`,
                        'por_pedido': `<span class="on-backorder">Disponible por pedido</span>`,
                        'agotado': `<span class="out-of-stock">Agotado</span>`
                    };
                    productStock.innerHTML = estadoMap[estadoActual] || estadoMap['agotado'];
                }
                const cartActions = document.querySelector('.cart-actions');
                if (cartActions) {
                    cartActions.style.display = 'none';
                }
            }
        } else {
            // No se encontró variación
            const productStock = document.querySelector('.product-stock');
            if (productStock) {
                productStock.innerHTML = '<span class="out-of-stock">Combinación no disponible</span>';
            }
            const cartActions = document.querySelector('.cart-actions');
            if (cartActions) {
                cartActions.style.display = 'none';
            }
        }
    } else {
        // No todos los atributos están seleccionados
        const cartActions = document.querySelector('.cart-actions');
        if (cartActions) {
            cartActions.style.display = 'none';
        }
    }
    
}

function limpiarTodasLasSelecciones() {
    document.querySelectorAll('.variation-option.active').forEach(option => {
        option.classList.remove('active');
    });
}

function mostrarEstadoInicial() {
    const primerAtributoId = ordenAtributos.length > 0 ? ordenAtributos[0] : null;
    const contenedorVariaciones = document.querySelector('.product-variations');
    
    if (!contenedorVariaciones) return;
    const grupos = document.querySelectorAll('.variation-attribute');
    const gruposMap = {};
    
    grupos.forEach(group => {
        const atributoId = group.dataset.atributoId;
        gruposMap[atributoId] = group;
    });
    
    // Reordenar en el DOM según ordenAtributos
    ordenAtributos.forEach(atributoId => {
        const grupo = gruposMap[atributoId];
        if (grupo) {
            contenedorVariaciones.appendChild(grupo);
        }
    });
    
    // Ahora aplicar estados
    document.querySelectorAll('.variation-options').forEach(group => {
        const atributoId = group.dataset.atributoId;
        const esPrimero = (String(atributoId) === String(primerAtributoId));
        
        if (esPrimero) {
            group.querySelectorAll('.variation-option').forEach(option => {
                option.classList.remove('disabled');
                option.style.opacity = '1';
                option.style.pointerEvents = 'auto';
                option.style.cursor = 'pointer';
                const badge = option.querySelector('.stock-badge');
                if (badge) badge.remove();
            });
        } else {
            group.querySelectorAll('.variation-option').forEach(option => {
                option.classList.add('disabled');
                option.style.opacity = '0.5';
                option.style.pointerEvents = 'none';
                option.style.cursor = 'not-allowed';
                option.title = option.textContent.trim() + ' (Selecciona primero el atributo anterior)';
                const badge = option.querySelector('.stock-badge');
                if (badge) badge.remove();
            });
        }
    });
}

async function inicializarVariaciones() {
    mostrarLoaderInicial();

    try{
        const container = document.querySelector('.product-detail-container');
        productoIdGlobal = container ? parseInt(container.dataset.productoId) : null;
        vendidoIndividualmenteGlobal = container ? container.dataset.vendidoIndividualmente === 'true' : false;
        
        if (!productoIdGlobal) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID del producto',
                confirmButtonColor: '#e74c3c'
            });
            ocultarLoader();
            return;
        }
        
        const hayVariaciones = document.querySelector('.variation-options') !== null;
        const hayScriptVariaciones = document.getElementById('variacionesData') !== null;
        
        esProductoVariable = hayVariaciones || hayScriptVariaciones;
        
        if (!esProductoVariable) {
            ocultarLoader();
            return;
        }
        
        const dataScript = document.getElementById('variacionesData');
        if (dataScript) {
            try {
                const rawData = JSON.parse(dataScript.textContent || '[]');
                variacionesDataGlobal = rawData.filter(v => v && typeof v === 'object' && v.id);
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar variaciones',
                    text: 'No se pudieron cargar los datos de las variaciones.',
                    confirmButtonColor: '#e74c3c'
                });
            }
        }
        
        // Esperar a que el DOM se actualice
        await new Promise(resolve => setTimeout(resolve, 200));
        
        await obtenerOrdenAtributos(productoIdGlobal);
        limpiarTodasLasSelecciones();
        mostrarEstadoInicial();

        const shortDesc = document.querySelector('.product-short-description');
        if (shortDesc) {
            descripcionOriginal = shortDesc.innerHTML;
        }
        
        // OCULTAR CARRITO Y MOSTRAR MENSAJE POR DEFECTO
        const cartActions = document.querySelector('.cart-actions');
        if (cartActions) {
            cartActions.style.display = 'none';
        }
        
        // ESTABLECER MENSAJE INICIAL
        const productStock = document.querySelector('.product-stock');
        if (productStock) {
            productStock.dataset.mensajeInicial = productStock.innerHTML;
            productStock.innerHTML = '<span class="text-muted">⏳ Selecciona todas las opciones</span>';
        }
        
        // Asignar evento de clic
        document.querySelectorAll('.variation-option').forEach(option => {
            option.removeEventListener('click', handleVariacionClick);
            option.addEventListener('click', handleVariacionClick);
        });
        
        await new Promise(resolve => setTimeout(resolve, 500));
    }catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error de inicialización',
            text: 'Ocurrió un error al inicializar las variaciones: ' + error.message,
            confirmButtonColor: '#e74c3c'
        });
    } finally {
        ocultarLoader();
    }
}

// =============================================
// INICIALIZACIÓN DOM
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    mostrarLoaderInicial();
    // Inicializar variaciones
    setTimeout(inicializarVariaciones, 100);
    
    // Cambio de imagen principal al hacer clic en thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.addEventListener('click', function() {
            document.getElementById('mainProductImage').src = this.dataset.image;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const minusBtn = document.querySelector('.cart-actions .qty-btn.minus:not(.grouped-minus)');
    const plusBtn = document.querySelector('.cart-actions .qty-btn.plus:not(.grouped-plus)');
    const qtyInput = document.querySelector('.cart-actions .qty-input:not(.grouped-qty-input)');
    
    if (minusBtn && plusBtn && qtyInput) {
        // 🔥 Función que obtiene el max actual del input
        function getCurrentMax() {
            return parseInt(qtyInput.max) || parseInt(qtyInput.dataset.max) || 99;
        }
        
        function updateButtons() {
            let val = parseInt(qtyInput.value) || 1;
            const maxVal = getCurrentMax();
            minusBtn.disabled = val <= 1;
            plusBtn.disabled = val >= maxVal;
        }
        
        minusBtn.addEventListener('click', function() {
            let val = parseInt(qtyInput.value);
            if (val > 1) {
                qtyInput.value = val - 1;
                updateButtons();
                qtyInput.dispatchEvent(new Event('change'));
            }
        });
        
        plusBtn.addEventListener('click', function() {
            let val = parseInt(qtyInput.value);
            const maxVal = getCurrentMax();
            if (val < maxVal) {
                qtyInput.value = val + 1;
                updateButtons();
                qtyInput.dispatchEvent(new Event('change'));
            }
        });
        
        qtyInput.addEventListener('change', function() {
            const maxVal = getCurrentMax();
            let val = parseInt(this.value) || 1;
            if (val < 1) val = 1;
            if (val > maxVal) val = maxVal;
            this.value = val;
            updateButtons();
        });
        
        qtyInput.addEventListener('input', function() {
            const maxVal = getCurrentMax();
            let val = parseInt(this.value) || 1;
            if (val < 1) val = 1;
            if (val > maxVal) val = maxVal;
            if (parseInt(this.value) > maxVal) {
                this.style.borderColor = 'red';
            } else {
                this.style.borderColor = '';
            }
        });
        
        // Inicializar estado de los botones
        updateButtons();
        
        // Si el max inicial es 1, deshabilitar
        if (getCurrentMax() === 1) {
            qtyInput.disabled = true;
            qtyInput.value = 1;
            minusBtn.disabled = true;
            plusBtn.disabled = true;
        }
    }

    // Selectores de cantidad para productos agrupados
    document.querySelectorAll('.grouped-qty').forEach(group => {
        const minusBtn = group.querySelector('.grouped-minus');
        const plusBtn = group.querySelector('.grouped-plus');
        const qtyInput = group.querySelector('.grouped-qty-input');
        
        if (minusBtn && plusBtn && qtyInput) {
            const maxValue = parseInt(qtyInput.dataset.max || qtyInput.max || 99);
            const minValue = parseInt(qtyInput.dataset.min || qtyInput.min || 0);
            
            function updateGroupedButtons() {
                let val = parseInt(qtyInput.value) || 0;
                minusBtn.disabled = val <= minValue;
                plusBtn.disabled = val >= maxValue;
            }
            
            minusBtn.addEventListener('click', () => {
                let val = parseInt(qtyInput.value);
                if (val > minValue) {
                    qtyInput.value = val - 1;
                    updateGroupedButtons();
                    qtyInput.dispatchEvent(new Event('change'));
                }
            });
            
            plusBtn.addEventListener('click', () => {
                let val = parseInt(qtyInput.value);
                if (val < maxValue) {
                    qtyInput.value = val + 1;
                    updateGroupedButtons();
                    qtyInput.dispatchEvent(new Event('change'));
                }
            });
            
            qtyInput.addEventListener('change', function() {
                let val = parseInt(this.value) || 0;
                if (val < minValue) val = minValue;
                if (val > maxValue) val = maxValue;
                this.value = val;
                updateGroupedButtons();
            });
            
            qtyInput.addEventListener('input', function() {
                let val = parseInt(this.value) || 0;
                if (val < minValue) val = minValue;
                if (val > maxValue) val = maxValue;
                if (parseInt(this.value) > maxValue || parseInt(this.value) < minValue) {
                    this.style.borderColor = 'red';
                } else {
                    this.style.borderColor = '';
                }
            });
            
            updateGroupedButtons();
            if (maxValue === 1 && minValue === 0) {
                qtyInput.disabled = false;
                qtyInput.value = 0;
                updateGroupedButtons();
            }
        }
    });

    // Botón para añadir productos agrupados al carrito
    const addGroupedBtn = document.getElementById('addGroupedToCart');
    if (addGroupedBtn) {
        addGroupedBtn.addEventListener('click', function() {
            const items = [];
            const productItems = document.querySelectorAll('.grouped-product-item');
            let totalItems = 0;
            
            productItems.forEach(item => {
                const qtyInput = item.querySelector('.grouped-qty-input');
                if (qtyInput) {
                    const productId = qtyInput.dataset.productId;
                    const quantity = parseInt(qtyInput.value) || 0;
                    
                    if (quantity > 0) {
                        items.push({
                            id: productId,
                            quantity: quantity
                        });
                        totalItems += quantity;
                    }
                }
            });
            
            if (items.length === 0) {
                const messageDiv = document.getElementById('groupedCartMessage');
                messageDiv.innerHTML = '<div class="alert alert-warning">Por favor, selecciona al menos un producto (cantidad > 0).</div>';
                setTimeout(() => { messageDiv.innerHTML = ''; }, 3000);
                return;
            }
            
            const messageDiv = document.getElementById('groupedCartMessage');
            messageDiv.innerHTML = `<div class="alert alert-success">¡${totalItems} producto(s) añadido(s) al carrito!</div>`;
            setTimeout(() => { messageDiv.innerHTML = ''; }, 3000);
        });
    }

    // Pestañas (descripción / especificaciones / valoraciones)
    document.querySelectorAll('.tabs-nav li').forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.tab;
            document.querySelectorAll('.tabs-nav li').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            const targetContent = document.getElementById(`tab-${targetTab}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
});
