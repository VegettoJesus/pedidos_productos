(function(){
  const qs = (selector, scope = document) => scope.querySelector(selector);
  const qsa = (selector, scope = document) => scope.querySelectorAll(selector);
  
  const escapeHtml = (s = '') => (s+'').replace(/&/g,'&amp;')
                                       .replace(/</g,'&lt;')
                                       .replace(/"/g,'&quot;')
                                       .replace(/'/g,'&#039;');

  const MAX_IMAGES = 6;
  const MAX_SIZE_BYTES = 4 * 1024 * 1024;
  const CATEGORIAS = window._CATEGORIAS || [];
  const ETIQUETAS = window._ETIQUETAS || [];
  const ATRIBUTOS = window._ATRIBUTOS || [];

  const state = {
    imageFiles: [],
    nextFileId: 1,
    selectedTags: new Map(),
    productoAtributos: [],
    modals: {},
    isSubmitting: false,
    submitTimeout: null,
    upsells: [],
    crosssells: [],
    variaciones: [],
    relacionados: [],
    variacionesPage: 1,
    variacionesPerPage: 10,
    modalTransition: false
  };

  function getCurrentPageVariaciones() {
    const start = (state.variacionesPage - 1) * state.variacionesPerPage;
    return state.variaciones.slice(start, start + state.variacionesPerPage);
  }

  function resetVariacionesPagination() {
      state.variacionesPage = 1;
      renderVariacionesUI();
  }

  function initTipoProductoSystem(){
    const sel = qs('#tipoProductoSelect');
    if(!sel) return;
    applyTipoUI(sel.value || sel.options[sel.selectedIndex].value);

    sel.addEventListener('change', (e) => {
      const nuevoTipo = e.target.value;
      clearOnTipoChange(nuevoTipo);
      applyTipoUI(nuevoTipo);
    });
  }

  function clearOnTipoChange(nuevoTipo){
    state.productoAtributos = [];
    state.variaciones = [];
    state.upsells = [];
    state.crosssells = [];
    state.relacionados = [];
    state.imageFiles = [];
    state.selectedTags.clear();
    state.miniaturaFile = null;
    state.variacionesPage = 1;
    state.isSubmitting = false;
    
    if (typeof tinymce !== 'undefined' && tinymce.get('descripcionLarga')) {
        tinymce.get('descripcionLarga').setContent('');
    }
    
    const atributosBlocks = qs('#atributosBlocks');
    if (atributosBlocks) atributosBlocks.innerHTML = '';
    
    const variationsContainer = qs('#variationsContainer');
    if (variationsContainer) variationsContainer.innerHTML = '';
    
    const previewContainer = qs('#previewContainer');
    if (previewContainer) previewContainer.innerHTML = '';
    
    const tagContainers = document.querySelectorAll('.tag-container');
    tagContainers.forEach(container => {
        if (container) container.innerHTML = '';
    });
    
    const searchInputs = ['#inputUpsells', '#inputCrosssells', '#inputRelacionados'];
    searchInputs.forEach(selector => {
        const input = qs(selector);
        if (input) input.value = '';
    });
    
    const miniImg = qs('#miniImg');
    const miniPlaceholder = qs('#miniPlaceholder');
    const removeMiniBtn = qs('#removeMiniBtn');
    if (miniImg) {
        if (miniImg.src) URL.revokeObjectURL(miniImg.src);
        miniImg.src = '';
        miniImg.style.display = 'none';
    }
    if (miniPlaceholder) miniPlaceholder.style.display = 'flex';
    if (removeMiniBtn) removeMiniBtn.classList.add('d-none');
    
    const miniInput = qs('#miniaturaInput');
    if (miniInput) miniInput.value = '';
    const catSelect = qs('#categoriaSelect');
    if (catSelect) catSelect.value = '';
    
    const subList = qs('#subcategoriaList');
    if (subList) subList.innerHTML = '<div class="text-muted text-center py-3">Selecciona una categoría primero</div>';
    const subcatRadios = document.querySelectorAll('input[name="id_subCategorias"]');
    subcatRadios.forEach(radio => radio.checked = false);
    const tagCheckboxes = document.querySelectorAll('.tag-available');
    tagCheckboxes.forEach(checkbox => {
        if (checkbox) checkbox.checked = false;
    });
    
    const selectedTagsContainer = qs('#selectedTags');
    if (selectedTagsContainer) selectedTagsContainer.innerHTML = '';
    
    const tagInput = qs('#tagInput');
    if (tagInput) tagInput.value = '';
    const checkRebaja = qs('#checkRebaja');
    const rebajaFechas = qs('#rebajaFechas');
    if (checkRebaja) {
        checkRebaja.checked = false;
        if (rebajaFechas) rebajaFechas.classList.add('d-none');
    }
    
    const checkGestion = qs('#checkGestion');
    const invExtra = qs('#invExtra');
    if (checkGestion) {
        checkGestion.checked = false;
        if (invExtra) invExtra.classList.add('d-none');
    }
    
    const stockInput = qs('input[name="stock"]');
    if (stockInput) stockInput.value = '';
    
    const skuInput = qs('input[name="sku"]');
    if (skuInput) skuInput.value = '';
    
    const backordersRadios = document.querySelectorAll('input[name="backorders"]');
    backordersRadios.forEach(radio => radio.checked = false);
    if (backordersRadios.length > 0) backordersRadios[0].checked = true;
    const estadoInvRadios = document.querySelectorAll('input[name="estado_inv"]');
    estadoInvRadios.forEach(radio => radio.checked = false);
    const primerEstadoInv = document.querySelector('input[name="estado_inv"][value="existe"]');
    if (primerEstadoInv) primerEstadoInv.checked = true;
    const precioRegular = qs('input[name="precio_regular"]');
    if (precioRegular) precioRegular.value = '';
    
    const precioRebajado = qs('input[name="precio_rebajado"]');
    if (precioRebajado) precioRebajado.value = '';
    
    const fechaInicio = qs('input[name="fecha_inicio_rebaja"]');
    const fechaFin = qs('input[name="fecha_fin_rebaja"]');
    if (fechaInicio) fechaInicio.value = '';
    if (fechaFin) fechaFin.value = '';
    const peso = qs('input[name="peso"]');
    if (peso) peso.value = '';
    
    const pesoUnidad = qs('select[name="peso_unidad"]');
    if (pesoUnidad) pesoUnidad.value = 'kg';
    
    const longitud = qs('input[name="longitud"]');
    const anchura = qs('input[name="anchura"]');
    const altura = qs('input[name="altura"]');
    if (longitud) longitud.value = '';
    if (anchura) anchura.value = '';
    if (altura) altura.value = '';
    const notaInterna = qs('textarea[name="nota_interna"]');
    if (notaInterna) notaInterna.value = '';
    
    const permiteValoraciones = qs('input[name="permite_valoraciones"]');
    if (permiteValoraciones) permiteValoraciones.checked = true;
    
    const vendidoIndividualmente = qs('input[name="vendido_individualmente"]');
    if (vendidoIndividualmente) vendidoIndividualmente.checked = false;
    const imagenesInput = qs('#imagenesInput');
    if (imagenesInput) imagenesInput.value = '';
    
    state.imageFiles.forEach(img => {
        if (img.url) URL.revokeObjectURL(img.url);
    });
    
    const chkVariaciones = document.querySelectorAll('.chk-variacion');
    chkVariaciones.forEach(chk => {
        if (chk) {
            chk.checked = false;
            const formCheck = chk.closest('.form-check');
            if (formCheck) formCheck.classList.add('d-none');
        }
    });
    
    renderAtributoBlocks();
    renderVariacionesUI();
    renderSelectedTags();
    updateImageStatus();
    state.variacionesPage = 1;
  }

  function initNewProductButton() {
    const btnNuevo = qs('#btnNuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            resetForm(false); 
            const tipoSelect = qs('#tipoProductoSelect');
            if (tipoSelect) {
                tipoSelect.value = 'simple';
                applyTipoUI('simple');
            }
            
            const idInput = qs('#idProducto');
            if (idInput) idInput.value = '';
        });
    }
  }

  function applyTipoUI(tipo){
  const navContainer = qs('.nav-pills') || qs('#v-tabs');
  if(!navContainer) return;

  function toggleNavByTarget(targetSelector, show){
    const btn = navContainer.querySelector(`[data-bs-target="${targetSelector}"]`);
    if(btn) btn.classList.toggle('d-none', !show);
  }
  function togglePane(selector, show){
    const pane = qs(selector);
    if(pane) pane.classList.toggle('d-none', !show);
  }
  ensureVariacionesTab();

  if(tipo === 'simple'){
    toggleNavByTarget('#tab-general', true);
    togglePane('#tab-general', true);
    toggleNavByTarget('#tab-inventario', true);
    togglePane('#tab-inventario', true);
    toggleNavByTarget('#tab-envio', true);
    togglePane('#tab-envio', true);
    toggleNavByTarget('#tab-relacionados', true);
    togglePane('#tab-relacionados', true);
    toggleNavByTarget('#tab-atributos', true);
    togglePane('#tab-atributos', true);
    toggleNavByTarget('#tab-avanzado', true);
    togglePane('#tab-avanzado', true);
    toggleNavByTarget('#tab-variaciones', false);
    togglePane('#tab-variaciones', false);
    enablePriceFields(true);
    toggleAtributoVariacionCheckbox(false);
    showTab('#tab-general');
    renderRelacionadosDefault();

  } else if(tipo === 'variable'){
    toggleNavByTarget('#tab-general', false);
    togglePane('#tab-general', false);
    
    toggleNavByTarget('#tab-inventario', true);
    togglePane('#tab-inventario', true);
    toggleNavByTarget('#tab-envio', true);
    togglePane('#tab-envio', true);
    toggleNavByTarget('#tab-relacionados', true);
    togglePane('#tab-relacionados', true);
    toggleNavByTarget('#tab-atributos', true);
    togglePane('#tab-atributos', true);
    toggleNavByTarget('#tab-avanzado', true);
    togglePane('#tab-avanzado', true);
    toggleNavByTarget('#tab-variaciones', true);
    togglePane('#tab-variaciones', true);
    
    showInventoryMode('variable');
    enablePriceFields(false);
    toggleAtributoVariacionCheckbox(true);
    showTab('#tab-inventario');
    renderRelacionadosDefault();
    renderVariacionesUI();

  } else if(tipo === 'agrupado'){
    toggleNavByTarget('#tab-general', false);
    togglePane('#tab-general', false);
    toggleNavByTarget('#tab-inventario', true);
    togglePane('#tab-inventario', true);
    toggleNavByTarget('#tab-envio', false);
    togglePane('#tab-envio', false);
    toggleNavByTarget('#tab-relacionados', true);
    togglePane('#tab-relacionados', true);
    toggleNavByTarget('#tab-atributos', true);
    togglePane('#tab-atributos', true);
    toggleNavByTarget('#tab-avanzado', true);
    togglePane('#tab-avanzado', true);
    toggleNavByTarget('#tab-variaciones', false);
    togglePane('#tab-variaciones', false);
    
    showInventoryMode('agrupado');
    toggleAtributoVariacionCheckbox(false);
    showFirstVisibleTab(navContainer);
    
    renderRelacionadosAgrupado();
  }
}

function enablePriceFields(enable) {
    const precioRegular = qs('input[name="precio_regular"]');
    const precioRebajado = qs('input[name="precio_rebajado"]');
    const checkRebaja = qs('#checkRebaja');
    const rebajaFechas = qs('#rebajaFechas');
    
    if (precioRegular) {
        precioRegular.disabled = !enable;
        if (!enable) precioRegular.value = '';
    }
    if (precioRebajado) {
        precioRebajado.disabled = !enable;
        if (!enable) precioRebajado.value = '';
    }
    if (checkRebaja) {
        checkRebaja.disabled = !enable;
        if (!enable) {
            checkRebaja.checked = false;
            if (rebajaFechas) rebajaFechas.classList.add('d-none');
        }
    }
}

function showInventoryMode(mode){
    const invPane = qs('#tab-inventario');
    if(!invPane) return;

    qsa('#tab-inventario .mb-3, #tab-inventario .form-check').forEach(el => {
        el.classList.remove('d-none');
    });

    if(mode === 'simple'){
        enablePriceFields(true);

    } else if(mode === 'variable'){
        const estadoInv = invPane.querySelector('[name="estado_inv"]');
        if(estadoInv) {
            const estadoInvGroup = estadoInv.closest('.mb-3, .form-check');
            if(estadoInvGroup) estadoInvGroup.classList.add('d-none');
        }
        
        showInventoryInfoMessage(true);
        enablePriceFields(false);

    } else if(mode === 'agrupado'){
        const elementosAOcultar = ['gestion_inventario', 'stock', 'backorders', 'estado_inv', 'vendido_individualmente'];
        elementosAOcultar.forEach(campo => {
            const elemento = invPane.querySelector(`[name="${campo}"]`);
            if(elemento) {
                const grupo = elemento.closest('.mb-3, .form-check');
                if(grupo) grupo.classList.add('d-none');
            }
        });
        
        showInventoryInfoMessage(false);
    }
}

function showInventoryInfoMessage(isVariable) {
    const invPane = qs('#tab-inventario');
    if(!invPane) return;
    const existingMsg = invPane.querySelector('.inventory-info-message');
    if(existingMsg) existingMsg.remove();
    
    const msgDiv = document.createElement('div');
    msgDiv.className = 'alert alert-info inventory-info-message mt-3';
    msgDiv.style.fontSize = '0.9rem';
    
    if(isVariable) {
        msgDiv.innerHTML = `
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Nota:</strong> Para productos variables, el inventario y los precios se gestionan a nivel de cada variación.
            Completa los atributos y genera las variaciones para configurar SKU, stock y precios individuales.
        `;
    } else {
        msgDiv.innerHTML = `
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Nota:</strong> Los productos agrupados no gestionan inventario propio.
            El stock se calcula automáticamente basado en los productos que lo componen.
        `;
    }
    
    invPane.appendChild(msgDiv);
  }

  function showInventoryMode(mode){
    const invPane = qs('#tab-inventario');
    if(!invPane) return;

    qsa('#tab-inventario .mb-3, #tab-inventario .form-check').forEach(el => {
      const hasSku = el.querySelector('[name="sku"]');
      const hasGestion = el.querySelector('[name="gestion_inventario"]');
      const hasLimit = el.querySelector('[name="vendido_individualmente"]');
      const hasStock = el.querySelector('[name="stock"]');
      const hasEstado_inv = el.querySelector('[name="estado_inv"]');
      
      if(mode === 'simple'){
        el.classList.remove('d-none');

      } else if(mode === 'variable'){
        if (hasEstado_inv) {
          el.classList.add('d-none');
        } else {
          el.classList.remove('d-none');
        }

      } else if(mode === 'agrupado'){
        if (hasSku) {
          el.classList.remove('d-none');
        } else {
          el.classList.add('d-none');
        }
      }
    });
  }
  function renderRelacionadosDefault(){
    const relPane = qs('#tab-relacionados');
    if(!relPane) return;
    relPane.innerHTML = `
      <div class="mb-3">
        <label class="form-label fw-semibold">
          <i class="bi bi-arrow-up-circle me-1"></i> Upsells
        </label>
        <div class="note-small mb-2">
          Productos de mayor valor que sugieres en lugar del actual (ejemplo: versión premium, modelo superior).
        </div>
        <input type="text" id="inputUpsells" class="form-control" placeholder="Buscar producto...">
        <div class="mt-2">
          <small class="text-muted">Escribe al menos 2 caracteres para buscar</small>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">
          <i class="bi bi-arrow-left-right me-1"></i> Cross-sells
        </label>
        <div class="note-small mb-2">
          Productos complementarios que se pueden comprar junto con el producto actual.
        </div>
        <input type="text" id="inputCrosssells" class="form-control" placeholder="Buscar producto...">
        <div class="mt-2">
          <small class="text-muted">Escribe al menos 2 caracteres para buscar</small>
        </div>
      </div>
    `;
    setupProductSearch('#inputUpsells', 'upsells');
    setupProductSearch('#inputCrosssells', 'crosssells');
  }

  function renderRelacionadosAgrupado(){
    const relPane = qs('#tab-relacionados');
    if(!relPane) return;
    
    relPane.innerHTML = '';
    relPane.innerHTML = `
      <div class="mb-4">
        <label class="form-label fw-semibold">
          <i class="bi bi-diagram-3 me-1"></i> Productos Agrupados
        </label>
        <div class="note-small mb-2">
          Los productos agrupados son productos individuales que se venden como un conjunto.
          Busca y selecciona los productos que formarán parte de este grupo.
        </div>
        <input type="text" id="inputRelacionados" class="form-control" placeholder="Buscar producto por nombre o SKU...">
        <div class="mt-2">
          <small class="text-muted">Escribe al menos 2 caracteres para buscar y seleccionar productos</small>
        </div>
        <div id="relacionadosContainer" class="tag-container d-flex flex-wrap gap-2 mt-3"></div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">
          <i class="bi bi-arrow-left-right me-1"></i> Cross-sells
        </label>
        <div class="note-small mb-2">
          Productos complementarios que se pueden comprar junto con el producto agrupado.
        </div>
        <input type="text" id="inputCrosssells" class="form-control" placeholder="Buscar producto...">
        <div class="mt-2">
          <small class="text-muted">Escribe al menos 2 caracteres para buscar</small>
        </div>
        <div id="crosssellContainer" class="tag-container d-flex flex-wrap gap-2 mt-3"></div>
      </div>
    `;
    
    setupProductSearchAgrupado('#inputRelacionados', 'relacionados', '#relacionadosContainer');
    setupProductSearch('#inputCrosssells', 'crosssells');
  }

  function setupProductSearchAgrupado(selector, type, containerSelector) {
    const input = qs(selector);
    if (!input) return;

    const container = qs(containerSelector) || document.createElement('div');
    if (!qs(containerSelector)) {
      container.className = 'tag-container d-flex flex-wrap gap-2 mt-2';
      input.insertAdjacentElement('afterend', container);
    }

    const dropdown = document.createElement('div');
    dropdown.className = 'dropdown-menu show shadow';
    dropdown.style.display = 'none';
    dropdown.style.maxHeight = '200px';
    dropdown.style.overflowY = 'auto';
    dropdown.style.position = 'absolute';
    dropdown.style.zIndex = '9999';
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(dropdown);

    let timeout;

    input.addEventListener('input', async (e) => {
      clearTimeout(timeout);
      const term = e.target.value.trim();
      if (!term || term.length < 2) {
        dropdown.style.display = 'none';
        return;
      }
      timeout = setTimeout(async () => {
        try {
          const res = await axios.post('productos', {
            opcion: 'Buscar',
            query: term
          });
          const productos = res.data.productos || [];
          renderDropdownAgrupado(productos);
        } catch (err) {
          Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
      }, 400);
    });

    function renderDropdownAgrupado(productos) {
      dropdown.innerHTML = '';
      if (!productos.length) {
        dropdown.style.display = 'none';
        return;
      }
      productos.forEach(prod => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'dropdown-item';
        item.textContent = `${prod.nombre} (ID:${prod.id} / SKU:${prod.sku})`;
        item.addEventListener('click', () => addTagAgrupado(prod));
        dropdown.appendChild(item);
      });
      dropdown.style.display = 'block';
    }

    function addTagAgrupado(prod) {
      dropdown.style.display = 'none';
      input.value = '';
      const exists = state[type].some(p => p.id === prod.id);
      if (exists) return;

      state[type].push({ id: prod.id, nombre: prod.nombre });

      const tag = document.createElement('span');
      tag.className = 'badge bg-light text-dark border px-2 py-1 d-inline-flex align-items-center';
      tag.innerHTML = `
        ${escapeHtml(prod.nombre)}
        <button type="button" class="btn-close btn-sm ms-2 remove-tag" aria-label="Close"></button>
      `;
      tag.querySelector('.remove-tag').addEventListener('click', () => {
        tag.remove();
        state[type] = state[type].filter(p => p.id !== prod.id);
      });
      container.appendChild(tag);
    }

    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target) && e.target !== input) {
        dropdown.style.display = 'none';
      }
    });
  }            

  function ensureVariacionesTab(){
    const navContainer = qs('.nav-pills') || qs('#v-tabs');
    if(!navContainer) return;
    let btn = navContainer.querySelector('[data-bs-target="#tab-variaciones"]');
    if(!btn){
      btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'nav-link d-none'; 
      btn.setAttribute('data-bs-toggle','pill');
      btn.setAttribute('data-bs-target','#tab-variaciones');
      btn.innerHTML = 'Variaciones';
      navContainer.appendChild(btn);
    }
    let pane = qs('#tab-variaciones');
    if(!pane){
      pane = document.createElement('div');
      pane.id = 'tab-variaciones';
      pane.className = 'tab-pane fade p-3 d-none';
      pane.innerHTML = `
        <div class="mb-3">
          <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="button" id="btnGenerateVariations" class="btn btn-outline-primary shadow-sm"><i class="bi bi-gear-wide-connected me-1"></i> Generar variaciones</button>
            <button type="button" id="btnGenerateManualVariation" class="btn btn-outline-secondary shadow-sm"><i class="bi bi-plus-square me-1"></i> Generar manual</button>
            <button type="button" id="btnDeleteAllVariations" class="btn btn-outline-danger shadow-sm"><i class="bi bi-trash me-1"></i> Eliminar todas</button>
          </div>

          <!-- Panel de acciones masivas (oculto inicialmente) -->
          <div id="massActionsPanel" class="card border-0 mb-4" style="display: none; background: #fff;">
            <div class="card-header bg-white border-0 fw-bold py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
              <i class="bi bi-lightning-charge-fill text-primary me-2"></i> Acciones masivas sobre todas las variaciones
            </div>
            <div class="card-body">
              <!-- Primera fila: precios -->
              <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold"><i class="bi bi-tag me-1 text-primary"></i> Precio normal</label>
                  <select id="massPriceNormalAction" class="form-select mb-2">
                    <option value="none">Sin cambios</option>
                    <option value="inc_fixed">Incrementar (fijo)</option>
                    <option value="inc_percent">Incrementar (%)</option>
                    <option value="dec_fixed">Reducir (fijo)</option>
                    <option value="dec_percent">Reducir (%)</option>
                  </select>
                  <input type="number" id="massPriceNormalValue" class="form-control" placeholder="Valor" disabled>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold"><i class="bi bi-percent me-1 text-success"></i> Precio rebajado</label>
                  <select id="massPriceSaleAction" class="form-select mb-2">
                    <option value="none">Sin cambios</option>
                    <option value="set">Establecer (fijo)</option>
                    <option value="inc_fixed">Incrementar (fijo)</option>
                    <option value="inc_percent">Incrementar (%)</option>
                    <option value="dec_fixed">Reducir (fijo)</option>
                    <option value="dec_percent">Reducir (%)</option>
                  </select>
                  <input type="number" id="massPriceSaleValue" class="form-control" placeholder="Valor" disabled>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold"><i class="bi bi-calendar-week me-1 text-warning"></i> Periodo de rebaja</label>
                  <input type="date" id="massSaleStart" class="form-control mb-2" placeholder="Desde">
                  <input type="date" id="massSaleEnd" class="form-control" placeholder="Hasta">
                </div>
              </div>

              <!-- Segunda fila: inventario y dimensiones -->
              <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold"><i class="bi bi-box-seam me-1 text-info"></i> Gestión inventario</label>
                  <div class="form-check form-switch mb-3">
                    <input type="checkbox" id="massEnableInventory" class="form-check-input" style="cursor: pointer;">
                    <label class="form-check-label">Activar gestión</label>
                  </div>
                  <label class="form-label fw-semibold"><i class="bi bi-database me-1"></i> Establecer stock</label>
                  <input type="number" id="massStock" class="form-control" placeholder="Cantidad">
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold"><i class="bi bi-arrow-left-right me-1"></i> Largo (cm)</label>
                  <input type="number" id="massLength" class="form-control mb-2" placeholder="Largo">
                  <label class="form-label fw-semibold"><i class="bi bi-arrows-angle-expand me-1"></i> Ancho (cm)</label>
                  <input type="number" id="massWidth" class="form-control" placeholder="Ancho">
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold"><i class="bi bi-arrow-up me-1"></i> Alto (cm)</label>
                  <input type="number" id="massHeight" class="form-control mb-3" placeholder="Alto">
                  <label class="form-label fw-semibold"><i class="bi bi-weight-scale me-1"></i> Peso</label>
                  <div class="input-group">
                    <input type="number" id="massWeight" class="form-control" placeholder="Valor">
                    <select id="massWeightUnit" class="form-select" style="max-width: 80px;">
                      <option value="kg">kg</option>
                      <option value="g">g</option>
                      <option value="mg">mg</option>
                      <option value="lb">lb</option>
                      <option value="oz">oz</option>
                      <option value="ton">ton</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row g-4 mb-4">
                  <div class="col-12 col-md-12">
                    <button type="button" id="btnApplyMassActions" class="btn btn-primary w-100 shadow-sm"><i class="bi bi-check2-circle me-1"></i> Aplicar a todas</button>
                  </div>
                  <div class="col-12 col-md-12">
                    <button type="button" id="btnClearMassActions" class="btn btn-outline-secondary w-100 shadow-sm"><i class="bi bi-arrow-repeat me-1"></i> Limpiar</button>
                  </div>
              </div>
            </div>
          </div>

          <div id="variationsContainer" class="mb-3"></div>
        </div>
      `;
      const tabContent = qs('.tab-content');
      if(tabContent) tabContent.appendChild(pane);
      
      document.addEventListener('click', (e) => {
        if(e.target && e.target.id === 'btnGenerateVariations') {
          generateVariationsFromAtributos();
        }
        if(e.target && e.target.id === 'btnGenerateManualVariation') {
          addManualVariationRow();
        }
        if(e.target && e.target.id === 'btnDeleteAllVariations') {
          deleteAllVariations();
        }
        if(e.target && e.target.id === 'btnApplyMassActions') {
          applyMassActions();
        }
      });

      document.getElementById('btnClearMassActions')?.addEventListener('click', () => {
        document.getElementById('massPriceNormalAction').value = 'none';
        document.getElementById('massPriceSaleAction').value = 'none';
        document.getElementById('massPriceNormalValue').disabled = true;
        document.getElementById('massPriceSaleValue').disabled = true;
        document.getElementById('massPriceNormalValue').value = '';
        document.getElementById('massPriceSaleValue').value = '';
        document.getElementById('massSaleStart').value = '';
        document.getElementById('massSaleEnd').value = '';
        document.getElementById('massStock').value = '';
        document.getElementById('massLength').value = '';
        document.getElementById('massWidth').value = '';
        document.getElementById('massHeight').value = '';
        document.getElementById('massWeight').value = '';
        document.getElementById('massWeightUnit').value = 'kg';
        document.getElementById('massEnableInventory').checked = false;
        showAlert('info', 'Panel limpiado', 'Se han restablecido todas las acciones masivas.', 1200, false);
      });
      
      const priceNormalAction = document.getElementById('massPriceNormalAction');
      const priceNormalVal = document.getElementById('massPriceNormalValue');
      const priceSaleAction = document.getElementById('massPriceSaleAction');
      const priceSaleVal = document.getElementById('massPriceSaleValue');
      
      priceNormalAction.addEventListener('change', () => {
        priceNormalVal.disabled = (priceNormalAction.value === 'none');
      });
      priceSaleAction.addEventListener('change', () => {
        priceSaleVal.disabled = (priceSaleAction.value === 'none');
      });
    }
  }

  function toggleAtributoVariacionCheckbox(show){
    qsa('.atributo-block').forEach(block => {
      const chk = block.querySelector('.chk-variacion');
      if(chk) {
        chk.closest('.form-check')?.classList.toggle('d-none', !show);
        if(!show) chk.checked = false;
      }
    });
    if(!show){
      state.productoAtributos.forEach(a => a.variacion = false);
    }
  }

  function showTab(selector){
    const target = qs(selector);
    if(!target) return;
    const tabEl = document.querySelector(`[data-bs-target="${selector}"]`);
    if(tabEl){
      const bsTab = new bootstrap.Tab(tabEl);
      bsTab.show();
    } else {
      qs('.tab-pane')?.classList.remove('show','active');
      target.classList.add('show','active');
    }
  }

  function showFirstVisibleTab(navContainer){
    const btn = Array.from(navContainer.querySelectorAll('.nav-link')).find(b => !b.classList.contains('d-none'));
    if(btn){
      const bsTab = new bootstrap.Tab(btn);
      bsTab.show();
    }
  }
  
  function renderVariacionesUI() {
    const container = qs('#variationsContainer');
    if (!container) return;
    container.innerHTML = '';

    const massPanel = document.getElementById('massActionsPanel');
    if (massPanel) {
        massPanel.style.display = state.variaciones.length ? 'block' : 'none';
    }

    const atributosVariacion = state.productoAtributos.filter(a => a.variacion && a.valores && a.valores.length);
    
    if (!state.variaciones.length) {
        if (atributosVariacion.length === 0) {
            container.innerHTML = `
                <div class="alert alert-warning text-center py-4">
                    <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
                    <strong>No hay variaciones</strong>
                    <p class="mb-0 mt-2">Para generar variaciones, primero debes:</p>
                    <ol class="text-start mt-2">
                        <li>Ir a la pestaña <strong>Atributos</strong></li>
                        <li>Agregar un atributo (ej: Talla, Color)</li>
                        <li>Seleccionar sus valores</li>
                        <li>Marcar el atributo como <strong>Variación</strong></li>
                        <li>Volver aquí y hacer clic en <strong>Generar variaciones</strong></li>
                    </ol>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="text-muted text-center py-4">
                    <i class="bi bi-lightbulb fs-2 d-block mb-2"></i>
                    <strong>No hay variaciones generadas</strong>
                    <p class="mb-0 mt-2">Haz clic en "Generar variaciones" para crear todas las combinaciones posibles</p>
                </div>
            `;
        }
        return;
    }

    const startIndex = (state.variacionesPage - 1) * state.variacionesPerPage;
    const pageVariaciones = state.variaciones.slice(startIndex, startIndex + state.variacionesPerPage);

    pageVariaciones.forEach((variacion, localIdx) => {
        const globalIdx = startIndex + localIdx;
        const row = createVariationRow(variacion, globalIdx);
        container.appendChild(row);
    });

    renderPaginationControls(container);
  }

  function renderPaginationControls(container) {
    const totalPages = Math.ceil(state.variaciones.length / state.variacionesPerPage);
    if (totalPages <= 1) return;

    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'd-flex justify-content-between align-items-center mt-3';
    paginationDiv.innerHTML = `
        <div>
            <span class="small text-muted">Mostrando ${state.variacionesPerPage * (state.variacionesPage - 1) + 1} - ${Math.min(state.variacionesPerPage * state.variacionesPage, state.variaciones.length)} de ${state.variaciones.length} variaciones</span>
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="variacionesPrevBtn" ${state.variacionesPage === 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left"></i> Anterior
            </button>
            <span class="small mx-2">Página ${state.variacionesPage} de ${totalPages}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="variacionesNextBtn" ${state.variacionesPage === totalPages ? 'disabled' : ''}>
                Siguiente <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    `;

    const prevBtn = paginationDiv.querySelector('#variacionesPrevBtn');
    const nextBtn = paginationDiv.querySelector('#variacionesNextBtn');

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (state.variacionesPage > 1) {
                state.variacionesPage--;
                renderVariacionesUI();
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (state.variacionesPage < totalPages) {
                state.variacionesPage++;
                renderVariacionesUI();
            }
        });
    }

    container.appendChild(paginationDiv);
  }

  function createVariationRow(variation, idx) {
    const wrapper = document.createElement('div');
    wrapper.className = 'border rounded mb-2 bg-white shadow-sm';
    wrapper.dataset.variationIndex = idx;

    const activeAtrs = state.productoAtributos.filter(a => a.variacion && a.valores && a.valores.length);

    const selectsHtml = activeAtrs.map((a, i) => {
      const selectedTerm = variation?.atributos?.find(attr => String(attr.atrId) === String(a.atributo.id));
      const options = a.valores.map(t => `
        <option value="${t.id}" ${selectedTerm && String(selectedTerm.termId) === String(t.id) ? 'selected' : ''}>
          ${escapeHtml(t.nombre)}
        </option>`).join('');
      return `
        <div class="me-2">
          <label class="form-label small mb-1">${escapeHtml(a.atributo.nombre)}</label>
          <select name="variation_attr_${idx}_${a.atributo.id}" class="form-select form-select-sm variation-attr" data-atr-id="${a.atributo.id}">
            <option value="0" ${!selectedTerm || selectedTerm.termId === null ? 'selected' : ''}>Cualquier ${escapeHtml(a.atributo.nombre)}</option>
            ${options}
          </select>
        </div>`;
    }).join('');

    wrapper.innerHTML = `
      <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded-top flex-wrap">
        <div class="d-flex flex-wrap gap-2 align-items-center">
          ${selectsHtml}
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
          <button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-body" aria-expanded="false">
            <i class="bi bi-chevron-down"></i>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-variation">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>

      <div class="variation-body collapse">
        <div class="p-3">

          <!-- Imágenes y SKU -->
          <div class="d-flex gap-3 align-items-start mb-3">
            <div class="variation-images" style="max-width: 300px;">
              <label class="form-label small mb-1 d-block">Imágenes (máx 6)</label>
              <input type="file" name="variation_images_${idx}[]" class="form-control form-control-sm variation-image-input" accept="image/*" multiple>
              <div class="image-preview mt-2 d-flex flex-wrap gap-2"></div>
            </div>
            <div class="flex-grow-1">
              <label class="form-label small mb-1">SKU</label>
              <input type="text" name="variation_sku_${idx}" class="form-control form-control-sm variation-sku" placeholder="SKU" value="${escapeHtml(variation?.sku || '')}">
            </div>
          </div>

          <!-- Precios -->
          <div class="row g-2 mb-3">
            <div class="col-md-4">
              <label class="form-label small mb-1">Precio normal</label>
              <input type="number" step="0.01" name="variation_price_normal_${idx}" class="form-control form-control-sm variation-price-normal" value="${variation?.price_normal || ''}">
            </div>
            <div class="col-md-4">
              <label class="form-label small mb-1">Precio rebajado</label>
              <input type="number" step="0.01" name="variation_price_sale_${idx}" class="form-control form-control-sm variation-price-sale" value="${variation?.price_sale || ''}">
            </div>
            <div class="col-md-4">
              <label class="form-label small mb-1 d-flex align-items-center gap-2">
                <input type="checkbox" name="variation_schedule_${idx}" class="form-check-input schedule-sale" ${variation?.sale_start || variation?.sale_end ? 'checked' : ''}> Reprogramar
              </label>
              <div class="schedule-dates mt-1 ${variation?.sale_start || variation?.sale_end ? '' : 'd-none'}">
                <input type="date" name="variation_sale_start_${idx}" class="form-control form-control-sm variation-sale-start mb-1" value="${variation?.sale_start || ''}">
                <input type="date" name="variation_sale_end_${idx}" class="form-control form-control-sm variation-sale-end" value="${variation?.sale_end || ''}">
              </div>
            </div>
          </div>

          <!-- Stock y Backorder -->
          <div class="mb-3">
              <div class="form-check">
                  <input type="checkbox" class="form-check-input gestion-inventario-check" 
                        id="gestion_inventario_${idx}" ${variation.gestion_inventario ? 'checked' : ''}>
                  <label class="form-check-label small fw-bold" for="gestion_inventario_${idx}">
                      Gestionar inventario para esta variación
                  </label>
              </div>
          </div>

          <!-- Contenedor que se muestra/oculta (stock y backorder) -->
          <div class="inventario-detalles-variacion" style="display: ${variation.gestion_inventario ? 'block' : 'none'};">
              <div class="row g-2 mb-3">
                  <div class="col-md-4">
                      <label class="form-label small mb-1">Cantidad en inventario</label>
                      <input type="number" name="variation_stock_${idx}" class="form-control form-control-sm variation-stock" value="${variation?.stock != null ? variation.stock : ''}">
                  </div>
                  <div class="col-md-4">
                      <label class="form-label small mb-1 d-block">¿Permitir reservas?</label>
                      <div class="d-flex gap-2">
                          <div class="form-check">
                              <input class="form-check-input allow-backorder" type="radio" name="variation_backorder_${idx}" value="no" ${variation?.backorder !== 'yes' ? 'checked' : ''}>
                              <label class="form-check-label small">No permitir</label>
                          </div>
                          <div class="form-check">
                              <input class="form-check-input allow-backorder" type="radio" name="variation_backorder_${idx}" value="yes" ${variation?.backorder === 'yes' ? 'checked' : ''}>
                              <label class="form-check-label small">Permitir</label>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Peso y dimensiones -->
          <div class="row g-2 mb-3">
            <div class="col-md-3">
              <label class="form-label small mb-1">Peso</label>
              <input type="number" name="variation_weight_${idx}" class="form-control form-control-sm variation-weight" value="${variation?.weight || ''}">
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-1">Tipo</label>
              <select name="variation_weight_type_${idx}" class="form-select form-select-sm variation-weight-type">
                <option value="kg" ${variation?.weight_type === 'kg' ? 'selected' : ''}>kg</option>
                <option value="g" ${variation?.weight_type === 'g' ? 'selected' : ''}>g</option>
                <option value="mg" ${variation?.weight_type === 'mg' ? 'selected' : ''}>mg</option>
                <option value="lb" ${variation?.weight_type === 'lb' ? 'selected' : ''}>lb</option>
                <option value="oz" ${variation?.weight_type === 'oz' ? 'selected' : ''}>oz</option>
                <option value="ton" ${variation?.weight_type === 'ton' ? 'selected' : ''}>ton</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small mb-1 d-block">Dimensiones (cm) <label>
              <div class="d-flex gap-2">
                <input type="number" name="variation_length_${idx}" class="form-control form-control-sm variation-length" placeholder="Largo" value="${variation?.length || ''}">
                <input type="number" name="variation_width_${idx}" class="form-control form-control-sm variation-width" placeholder="Ancho" value="${variation?.width || ''}">
                <input type="number" name="variation_height_${idx}" class="form-control form-control-sm variation-height" placeholder="Alto" value="${variation?.height || ''}">
              </div>
            </div>
          </div>

          <!-- Descripción -->
          <div class="mb-3">
            <label class="form-label small mb-1">Descripción</label>
            <textarea name="variation_description_${idx}" class="form-control form-control-sm variation-description" rows="2">${variation?.description || ''}</textarea>
          </div>
        </div>
      </div>
    `;

    const toggleBtn = wrapper.querySelector('.btn-toggle-body');
    const body = wrapper.querySelector('.variation-body');
    toggleBtn.addEventListener('click', () => {
      body.classList.toggle('show');
      const icon = toggleBtn.querySelector('i');
      icon.classList.toggle('bi-chevron-down');
      icon.classList.toggle('bi-chevron-up');
    });

    wrapper.querySelector('.btn-remove-variation').addEventListener('click', () => {
      state.variaciones.splice(idx, 1);
      resetVariacionesPagination();
    });

    const variacionActual = state.variaciones[idx];

    wrapper.querySelectorAll('.variation-attr').forEach(sel => {
      sel.addEventListener('change', () => {
        const atrId = sel.dataset.atrId;
        const termId = sel.value;
        if (!variacionActual.atributos) variacionActual.atributos = [];
        
        const pos = variacionActual.atributos.findIndex(a => String(a.atrId) === String(atrId));
        if (pos >= 0) {
          if (termId) {
            variacionActual.atributos[pos].termId = termId === "0" ? null : termId;
          } else {
            variacionActual.atributos.splice(pos, 1);
          }
        } else if (termId) {
          variacionActual.atributos.push({ 
            atrId, 
            termId: termId === "0" ? null : termId 
          });
        }
      });
    });

    wrapper.querySelector('.variation-sku').addEventListener('input', e => variacionActual.sku = e.target.value);
    wrapper.querySelector('.variation-stock').addEventListener('input', e => variacionActual.stock = e.target.value);
    wrapper.querySelector('.variation-price-normal').addEventListener('input', e => variacionActual.price_normal = e.target.value);
    wrapper.querySelector('.variation-price-sale').addEventListener('input', e => variacionActual.price_sale = e.target.value);

    const scheduleCheckbox = wrapper.querySelector('.schedule-sale');
    const datesDiv = wrapper.querySelector('.schedule-dates');
    scheduleCheckbox.addEventListener('change', () => {
      datesDiv.classList.toggle('d-none', !scheduleCheckbox.checked);
    });
    wrapper.querySelector('.variation-sale-start').addEventListener('input', e => variacionActual.sale_start = e.target.value);
    wrapper.querySelector('.variation-sale-end').addEventListener('input', e => variacionActual.sale_end = e.target.value);

    wrapper.querySelector('.variation-weight').addEventListener('input', e => variacionActual.weight = e.target.value);
    wrapper.querySelector('.variation-weight-type').addEventListener('change', e => variacionActual.weight_type = e.target.value);
    wrapper.querySelector('.variation-length').addEventListener('input', e => variacionActual.length = e.target.value);
    wrapper.querySelector('.variation-width').addEventListener('input', e => variacionActual.width = e.target.value);
    wrapper.querySelector('.variation-height').addEventListener('input', e => variacionActual.height = e.target.value);
    wrapper.querySelector('.variation-description').addEventListener('input', e => variacionActual.description = e.target.value);

    wrapper.querySelectorAll('.allow-backorder').forEach(radio => {
      radio.addEventListener('change', () => variacionActual.backorder = radio.value);
    });

    const chkGestion = wrapper.querySelector('.gestion-inventario-check');
    const detallesDiv = wrapper.querySelector('.inventario-detalles-variacion');
    if (chkGestion && detallesDiv) {
        chkGestion.checked = variacionActual.gestion_inventario;
        detallesDiv.style.display = variacionActual.gestion_inventario ? 'block' : 'none';

        chkGestion.addEventListener('change', (e) => {
            variacionActual.gestion_inventario = e.target.checked;
            detallesDiv.style.display = e.target.checked ? 'block' : 'none';
        });
    }

    const imgInput = wrapper.querySelector('.variation-image-input');
    const imgPreview = wrapper.querySelector('.image-preview');
    if (!variacionActual.images) variacionActual.images = [];

    imgInput.addEventListener('change', e => {
        const files = Array.from(e.target.files);
        
        files.forEach(file => {
            if (variacionActual.images.length < 6) {
                if (file && file.size > 0) {
                    variacionActual.images.push({
                        id: Date.now() + Math.random(),
                        file: file,
                        name: file.name
                    });
                    renderImages();
                }
            }
        });
    });

    function renderImages() {
      imgPreview.innerHTML = '';
      variacionActual.images.forEach(img => {
          const imgWrap = document.createElement('div');
          imgWrap.className = 'position-relative';
          imgWrap.style.width = '60px';
          imgWrap.style.height = '60px';
          
          const src = URL.createObjectURL(img.file);
          imgWrap.innerHTML = `
              <img src="${src}" class="img-thumbnail w-100 h-100" style="object-fit: cover;">
              <button type="button" class="btn-close position-absolute top-0 end-0 btn-remove-img" style="background: #fff; border-radius:50%;"></button>
          `;
          
          imgWrap.querySelector('.btn-remove-img').addEventListener('click', () => {
              variacionActual.images = variacionActual.images.filter(i => i.id !== img.id);
              URL.revokeObjectURL(src);
              renderImages();
          });
          imgPreview.appendChild(imgWrap);
      });
    }

    renderImages();

    return wrapper;
  }

  function generateVariationsFromAtributos() {
    const attrs = state.productoAtributos.filter(a => a.variacion && a.valores && a.valores.length);
    if (attrs.length < 1) {
      showAlert('warning', 'No hay atributos para variaciones', 'Marca como "Variación" al menos un atributo con valores.');
      return;
    }

    const arrays = attrs.map(a => {
      const valores = [
        { atrId: a.atributo.id, termId: null, nombre: `Cualquier ${a.atributo.nombre}` },
        ...a.valores.map(v => ({
          atrId: a.atributo.id,
          termId: v.id,
          nombre: v.nombre
        }))
      ];
      return valores;
    });

    function cartesian(arr) {
      return arr.reduce((a, b) => a.flatMap(d => b.map(e => d.concat([e]))), [[]]);
    }

    const combos = cartesian(arrays);

    state.variaciones = combos.map(combo => ({
        atributos: combo.map(c => ({ atrId: c.atrId, termId: c.termId })),
        sku: '',
        gestion_inventario: false,
        stock: 0,
        price_normal: '',
        price_sale: '',
        sale_start: '',
        sale_end: '',
        weight: '',
        weight_type: 'kg',
        length: '',
        width: '',
        height: '',
        description: '',
        backorder: 'no',
        images: []
    }));

    renderVariacionesUI();
    showAlert('success', 'Variaciones generadas', `${state.variaciones.length} variación(es) generadas.`, 1400, false);
}

  function addManualVariationRow() {
    const activos = state.productoAtributos.filter(a => a.variacion && a.valores.length);
    if (activos.length === 0) {
      showAlert('warning', 'No se puede generar', 'Debes activar al menos un atributo como variación con valores.');
      return;
    }

    state.variaciones.push({
      atributos: activos.map(a => ({ 
        atrId: a.atributo.id, 
        termId: null 
      })),
      sku: '',
      gestion_inventario: false,
      stock: 0,
      price_normal: '',
      price_sale: '',
      sale_start: '',
      sale_end: '',
      weight: '',
      weight_type: 'kg',
      length: '',
      width: '',
      height: '',
      description: '',
      backorder: 'no',
      images: []
    });

    renderVariacionesUI();
  }

    function deleteAllVariations() {
      if (!state.variaciones.length) return;
      Swal.fire({
        title: '¿Eliminar todas las variaciones?',
        text: `Se eliminarán ${state.variaciones.length} variaciones. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          state.variaciones = [];
          renderVariacionesUI();
          showAlert('success', 'Eliminadas', 'Todas las variaciones han sido eliminadas.');
        }
      });
    }

    function applyMassActions() {
    if (!state.variaciones.length) {
      showAlert('warning', 'Sin variaciones', 'No hay variaciones para modificar.');
      return;
    }

    const priceNormalAction = document.getElementById('massPriceNormalAction')?.value || 'none';
    const priceNormalValue = parseFloat(document.getElementById('massPriceNormalValue')?.value);
    const priceSaleAction = document.getElementById('massPriceSaleAction')?.value || 'none';
    const priceSaleValue = parseFloat(document.getElementById('massPriceSaleValue')?.value);
    const saleStart = document.getElementById('massSaleStart')?.value;
    const saleEnd = document.getElementById('massSaleEnd')?.value;
    const enableInventory = document.getElementById('massEnableInventory')?.checked;
    const massStock = document.getElementById('massStock')?.value;
    const massLength = document.getElementById('massLength')?.value;
    const massWidth = document.getElementById('massWidth')?.value;
    const massHeight = document.getElementById('massHeight')?.value;
    const massWeight = document.getElementById('massWeight')?.value;
    const massWeightUnit = document.getElementById('massWeightUnit')?.value;

    let changesApplied = false;

    for (let variacion of state.variaciones) {
      if (priceNormalAction !== 'none' && !isNaN(priceNormalValue)) {
        let current = parseFloat(variacion.price_normal) || 0;
        let newValue = current;
        switch (priceNormalAction) {
          case 'inc_fixed': newValue = current + priceNormalValue; break;
          case 'inc_percent': newValue = current * (1 + priceNormalValue / 100); break;
          case 'dec_fixed': newValue = current - priceNormalValue; break;
          case 'dec_percent': newValue = current * (1 - priceNormalValue / 100); break;
        }
        variacion.price_normal = Math.max(0, newValue).toFixed(2);
        changesApplied = true;
      }

      if (priceSaleAction !== 'none' && !isNaN(priceSaleValue)) {
        let current = parseFloat(variacion.price_sale) || 0;
        let newValue = current;
        switch (priceSaleAction) {
          case 'set': newValue = priceSaleValue; break;
          case 'inc_fixed': newValue = current + priceSaleValue; break;
          case 'inc_percent': newValue = current * (1 + priceSaleValue / 100); break;
          case 'dec_fixed': newValue = current - priceSaleValue; break;
          case 'dec_percent': newValue = current * (1 - priceSaleValue / 100); break;
        }
        variacion.price_sale = Math.max(0, newValue).toFixed(2);
        changesApplied = true;
      }

      if (saleStart) {
        variacion.sale_start = saleStart;
        changesApplied = true;
      }
      if (saleEnd) {
        variacion.sale_end = saleEnd;
        changesApplied = true;
      }

      if (enableInventory) {
        variacion.gestion_inventario = true;
        changesApplied = true;
      }

      if (massStock !== undefined && massStock !== '') {
        variacion.stock = Number(massStock);
        changesApplied = true;
      }

      if (massLength !== undefined && massLength !== '') {
        variacion.length = Number(massLength);
        changesApplied = true;
      }
      if (massWidth !== undefined && massWidth !== '') {
        variacion.width = Number(massWidth);
        changesApplied = true;
      }
      if (massHeight !== undefined && massHeight !== '') {
        variacion.height = Number(massHeight);
        changesApplied = true;
      }
      if (massWeight !== undefined && massWeight !== '') {
        variacion.weight = Number(massWeight);
        variacion.weight_type = massWeightUnit;
        changesApplied = true;
      }
    }

    if (changesApplied) {
      renderVariacionesUI(); 
      showAlert('success', 'Actualización masiva', 'Se han modificado todas las variaciones.');

      document.getElementById('massPriceNormalAction').value = 'none';
      document.getElementById('massPriceSaleAction').value = 'none';
      document.getElementById('massPriceNormalValue').disabled = true;
      document.getElementById('massPriceSaleValue').disabled = true;
      document.getElementById('massPriceNormalValue').value = '';
      document.getElementById('massPriceSaleValue').value = '';
      document.getElementById('massSaleStart').value = '';
      document.getElementById('massSaleEnd').value = '';
      document.getElementById('massStock').value = '';
      document.getElementById('massLength').value = '';
      document.getElementById('massWidth').value = '';
      document.getElementById('massHeight').value = '';
      document.getElementById('massWeight').value = '';
      document.getElementById('massWeightUnit').value = 'kg';
      document.getElementById('massEnableInventory').checked = false;
    } else {
      showAlert('info', 'Sin cambios', 'No se seleccionó ninguna acción o valores inválidos.');
    }
  }

  function initRelacionadosInputs() {
    setupProductSearch('#inputUpsells', 'upsells');
    setupProductSearch('#inputCrosssells', 'crosssells');
  }

  function setupProductSearch(selector, type) {
    const input = qs(selector);
    if (!input) return;

    const container = document.createElement('div');
    container.className = 'tag-container d-flex flex-wrap gap-2 mt-2';
    input.insertAdjacentElement('afterend', container);

    const dropdown = document.createElement('div');
    dropdown.className = 'dropdown-menu show shadow';
    dropdown.style.display = 'none';
    dropdown.style.maxHeight = '200px';
    dropdown.style.overflowY = 'auto';
    dropdown.style.position = 'absolute';
    dropdown.style.zIndex = '9999';
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(dropdown);

    let timeout;

    input.addEventListener('input', async (e) => {
      clearTimeout(timeout);
      const term = e.target.value.trim();
      if (!term) {
        dropdown.style.display = 'none';
        return;
      }
      timeout = setTimeout(async () => {
        try {
          const res = await axios.post('productos', {
            opcion: 'Buscar',
            query: term
          });
          const productos = res.data.productos || [];
          renderDropdown(productos);
        } catch (err) {
          Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
      }, 400);
    });

    function renderDropdown(productos) {
      dropdown.innerHTML = '';
      if (!productos.length) {
        dropdown.style.display = 'none';
        return;
      }
      productos.forEach(prod => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'dropdown-item';
        item.textContent = `${prod.nombre} (ID:${prod.id} / SKU:${prod.sku})`;
        item.addEventListener('click', () => addTag(prod));
        dropdown.appendChild(item);
      });
      dropdown.style.display = 'block';
    }

    function addTag(prod) {
      dropdown.style.display = 'none';
      input.value = '';

      const exists = state[type].some(p => p.id === prod.id);
      if (exists) return;

      state[type].push({ id: prod.id, nombre: prod.nombre });

      const tag = document.createElement('span');
      tag.className = 'badge bg-light text-dark border px-2 py-1 d-inline-flex align-items-center';
      tag.innerHTML = `
        ${escapeHtml(prod.nombre)}
        <button type="button" class="btn-close btn-sm ms-2 remove-tag" aria-label="Close"></button>
      `;
      tag.querySelector('.remove-tag').addEventListener('click', () => {
        tag.remove();
        state[type] = state[type].filter(p => p.id !== prod.id);
      });
      container.appendChild(tag);
    }

    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target) && e.target !== input) {
        dropdown.style.display = 'none';
      }
    });
  }

  function initModals() {
    if (typeof bootstrap === 'undefined') {
        return;
    }

    state.modals = {
        producto: new bootstrap.Modal(qs('#modalProducto')),
        crearAtributo: new bootstrap.Modal(qs('#modalCrearAtributo')),
        crearValor: new bootstrap.Modal(qs('#modalCrearValor'))
    };
    
    const modalElement = qs('#modalProducto');
    if (modalElement) {
        modalElement.addEventListener('show.bs.modal', function () {
          if (state.modalTransition) {
            state.modalTransition = false;
            return;
          }
          resetForm(false);
        });
        
        modalElement.addEventListener('hidden.bs.modal', function () {

      if (state.modalTransition) {
      return;
    }
    resetForm(false);

    const firstTab = document.querySelector('.nav-link');

    if (firstTab && typeof bootstrap !== 'undefined') {
        const bsTab = new bootstrap.Tab(firstTab);
        bsTab.show();
    }
  });
        
        modalElement.addEventListener('shown.bs.modal', function () {
            if (typeof tinymce !== 'undefined' && !tinymce.get('descripcionLarga')) {
                tinymce.init({
                    selector: '#descripcionLarga',
                    language: 'es_MX',
                    height: 400,
                    menubar: false,
                    license_key: 'gpl',
                    plugins: ['lists', 'link', 'autolink', 'charmap', 'preview',
                              'anchor', 'searchreplace', 'visualblocks', 'code',
                              'fullscreen', 'insertdatetime', 'media', 'table', 'wordcount'],
                    toolbar: 'undo redo | styles forecolor | bold italic | alignleft aligncenter alignright alignjustify',
                    content_style: `
                      @font-face {
                          font-family: 'default';
                          src: url('/fonts/Poppins-Light.ttf');
                      }
                      body {
                          font-family: 'default', Poppins, sans-serif;
                          font-size: 14px;
                      }
                    `,
                    statusbar: false,
                    forced_root_block: 'p',
                    convert_urls: false
                });
            }
        });
    }

    setupModalNavigation();
  }

  function setupModalNavigation() {
    document.addEventListener('click', (e) => {
      if (e.target && e.target.id === 'btnOpenCrearAtributo') {
        state.modalTransition = true;
        state.modals.producto.hide();

        setTimeout(() => {
            state.modals.crearAtributo.show();
        }, 300);
      }
    });

    document.addEventListener('click', (e) => {
      if (e.target && e.target.classList.contains('btnCrearValor')) {
        state.modalTransition = true;
        state.modals.producto.hide();

        setTimeout(() => {
          state.modals.crearValor.show();
        }, 300);
      }
    });

    qsa('#modalCrearAtributo .btn-close, #modalCrearAtributo [data-bs-dismiss="modal"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const form = qs('#formCrearAtributo');
        form?.reset();
        state.modals.crearAtributo.hide();
        setTimeout(() => {
          state.modals.producto.show();
        }, 300);
      });
    });

    qsa('#modalCrearValor .btn-close, #modalCrearValor [data-bs-dismiss="modal"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const form = qs('#formCrearValor');
        form?.reset();
        state.modals.crearValor.hide();
        setTimeout(() => {
          state.modals.producto.show();
        }, 300);
      });
    });

    setupSuccessHandlers();
  }

  function setupSuccessHandlers() {
    document.addEventListener('atributoCreado', () => {

    state.modalTransition = true;

    state.modals.crearAtributo.hide();

    setTimeout(() => {
        state.modals.producto.show();
    }, 300);
  });

  document.addEventListener('valorCreado', () => {

    state.modalTransition = true;
    state.modals.crearValor.hide();

    setTimeout(() => {
      state.modals.producto.show();
    }, 300);
  });
  }

  function initAtributosSystem() {
    renderAtributosSelect();
    renderAtributoBlocks();
    
    qs('#formCrearAtributo')?.addEventListener('submit', handleCrearAtributo);
    qs('#formCrearValor')?.addEventListener('submit', handleCrearValor);
  }

  function renderAtributosSelect() {
    const container = qs('#tab-atributos');
    if (!container) return;
    
    let selectWrap = container.querySelector('.atributos-select-wrap');
    
    if (!selectWrap) {
      selectWrap = document.createElement('div');
      selectWrap.className = 'atributos-select-wrap mb-3';
      selectWrap.innerHTML = `
        <label class="form-label">Seleccionar atributo existente</label>
        <select class="form-select mb-2" name="select_atributos_existentes">
          <option value="">-- Seleccione --</option>
          ${ATRIBUTOS.map(a => `<option value="${a.id}">${escapeHtml(a.nombre)}</option>`).join('')}
        </select>
        <div>
          <button type="button" class="btn btn-sm btn-outline-primary me-2" id="btnOpenCrearAtributo">Crear nuevo atributo</button>
        </div>
        <div id="atributosBlocks"></div>
      `;
      container.prepend(selectWrap);
      
      selectWrap.querySelector('select').addEventListener('change', function() {
        const id = this.value;
        if (!id) return;
        const atributo = ATRIBUTOS.find(a => String(a.id) === String(id));
        if (atributo) addAtributoBlock(atributo);
        this.value = '';
      });
    }
  }

  function addAtributoBlock(atributoObj) {
    if (state.productoAtributos.some(x => x.atributo.id === atributoObj.id)) {
      showAlert('info', 'Ya agregado', 'Ese atributo ya fue agregado al producto.');
      return;
    }

    state.productoAtributos.push({
      atributo: { 
        id: atributoObj.id, 
        nombre: atributoObj.nombre, 
        slug: atributoObj.slug 
      },
      valores: [],
      visible: true,
      variacion: false,
    });
    
    renderAtributoBlocks();
  }

  function renderAtributoBlocks() {
    const blocks = qs('#atributosBlocks');
    if (!blocks) return;
    
    blocks.innerHTML = '';
    
    state.productoAtributos.forEach((entry) => {
      const block = createAtributoBlock(entry);
      blocks.appendChild(block);
    });
  }

  function createAtributoBlock(entry) {
    const a = entry.atributo;
    const attrFull = ATRIBUTOS.find(x => String(x.id) === String(a.id));

    const wrapper = document.createElement('div');
    wrapper.className = 'atributo-block border rounded p-3 mt-3 mb-3 shadow-sm bg-white';
    wrapper.dataset.atributoId = a.id;

    wrapper.innerHTML = `
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <h6 class="mb-1 fw-bold">${escapeHtml(a.nombre)}</h6>
          <span class="badge bg-light text-secondary border small">${escapeHtml(a.slug)}</span>
        </div>
        <div class="d-flex flex-column gap-1 text-end">
          <div class="form-check form-switch">
            <input class="form-check-input chk-visible" type="checkbox" ${entry.visible ? 'checked' : ''}>
            <label class="form-check-label small">Visible</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input chk-variacion" type="checkbox" ${entry.variacion ? 'checked' : ''}>
            <label class="form-check-label small">Variación</label>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <button type="button" class="btn btn-sm btn-outline-primary btnCrearValor me-2">
          <i class="bi bi-plus-circle"></i> Crear valor
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary btnSelectAll">
          <i class="bi bi-check2-square"></i> Seleccionar todos
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger ms-2 btnEliminarAtributo">
          <i class="bi bi-trash"></i>
        </button>
      </div>

      <div class="valores-area">
        <div class="small fw-semibold mb-2">Valores seleccionados:</div>
        <div class="valores-chips d-flex flex-wrap gap-2 mb-3"></div>

        <div class="small text-muted">Valores disponibles:</div>
        <div class="valores-available d-flex flex-wrap gap-2 mt-2"></div>
      </div>
    `;

    const tipoProd = qs('#tipoProductoSelect')?.value;
    if (tipoProd !== 'variable') {
      const chk = wrapper.querySelector('.chk-variacion');
      if (chk) {
        const formCheck = chk.closest('.form-check');
        formCheck?.classList.add('d-none');
      }
    }
    
    const availDiv = wrapper.querySelector('.valores-available');
    if (availDiv && attrFull?.terminos) {
      attrFull.terminos.forEach(term => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-info btn-termino';
        btn.dataset.termId = term.id;
        btn.textContent = term.nombre;
        btn.addEventListener('click', () => {
          if (!entry.valores.some(v => v.id === term.id)) {
            entry.valores.push({
              id: term.id,
              nombre: term.nombre,
              slug: term.slug
            });
            renderAtributoBlocks();
          }
        });
        availDiv.appendChild(btn);
      });
    }

    const chipsDiv = wrapper.querySelector('.valores-chips');
    if (chipsDiv) {
      entry.valores.forEach(v => {
        const chip = document.createElement('span');
        chip.className = 'chip d-inline-flex align-items-center px-2 py-1 rounded bg-light border';
        chip.innerHTML = `
          ${escapeHtml(v.slug)}
          <button type="button" class="btn-close btn-sm ms-2 remove-valor"></button>
        `;
        chip.querySelector('.remove-valor').addEventListener('click', () => {
          entry.valores = entry.valores.filter(x => x.id !== v.id);
          renderAtributoBlocks();
        });
        chipsDiv.appendChild(chip);
      });
    }

    attachAtributoBlockEvents(wrapper, entry, a);

    return wrapper;
  }

  function attachAtributoBlockEvents(wrapper, entry, atributo) {
    wrapper.querySelector('.btnCrearValor')?.addEventListener('click', () => {
      const valorAtributoId = qs('#valor_atributo_id');
      if (valorAtributoId) {
        valorAtributoId.value = atributo.id;
      }
      state.modals.producto.hide();
      setTimeout(() => {
        state.modals.crearValor.show();
      }, 300);
    });

    wrapper.querySelector('.btnSelectAll')?.addEventListener('click', () => {
      const attrFull = ATRIBUTOS.find(x => String(x.id) === String(atributo.id));
      if (attrFull) {
        entry.valores = (attrFull.terminos || []).map(t => ({
          id: t.id,
          nombre: t.nombre,
          slug: t.slug
        }));
        renderAtributoBlocks();
      }
    });

    wrapper.querySelector('.btnEliminarAtributo')?.addEventListener('click', () => {
      Swal.fire({
        title: '¿Quitar atributo?',
        text: 'Este atributo se eliminará de tu producto.',
        icon: 'warning',
        showCancelButton: true
      }).then(res => {
        if (res.isConfirmed) {
          const index = state.productoAtributos.findIndex(x => x.atributo.id === atributo.id);
          if (index >= 0) state.productoAtributos.splice(index, 1);
          renderAtributoBlocks();
        }
      });
    });

    const chkVisible = wrapper.querySelector('.chk-visible');
    const chkVariacion = wrapper.querySelector('.chk-variacion');
    
    if (chkVisible) {
      chkVisible.addEventListener('change', (e) => {
        entry.visible = e.target.checked;
      });
    }
    
    if (chkVariacion) {
      chkVariacion.addEventListener('change', (e) => {
        entry.variacion = e.target.checked;
      });
    }
  }

  $('#modalCrearAtributo').on('show.bs.modal', function () {
    this.querySelector('form').reset();
  });

  $('#modalCrearValor').on('show.bs.modal', function () {
    this.querySelector('form').reset();
  });

  async function handleCrearAtributo(ev) {
    ev.preventDefault();
    const form = ev.target;
    const formData = new FormData(form);

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Guardando...';
    submitBtn.disabled = true;

    try {
      const { data } = await axios.post('productos', {
        opcion: 'CrearAtributo',
        nombre: formData.get('nombre'),
        slug: formData.get('slug') || ''
      });

      if (data.respuesta === 'ok') {

    ATRIBUTOS.push(data.atributo);

    const container = qs('#tab-atributos');
    const selectWrap = container?.querySelector('.atributos-select-wrap');

    if (selectWrap) {
        selectWrap.remove();
    }

    renderAtributosSelect();
    renderAtributoBlocks();
    addAtributoBlock(data.atributo);

    document.dispatchEvent(new CustomEvent('atributoCreado'));

    showAlert(
        'success',
        'Atributo creado',
        '',
        1200,
        false
    );

    form.reset();

      } else {
        showAlert('error', 'Error', data.mensaje);
      }
    } catch (error) {
      showAlert('error', 'Error', 'Error al crear el atributo');
    } finally {
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }
  }

  async function handleCrearValor(ev) {
    ev.preventDefault();
    const form = ev.target;
    const formData = new FormData(form);

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Guardando...';
    submitBtn.disabled = true;

    try {
      const { data } = await axios.post('productos', {
        opcion: 'CrearValorAtributo',
        atributo_id: formData.get('atributo_id'),
        nombre: formData.get('nombre'),
        slug: formData.get('slug') || '',
        descripcion: formData.get('descripcion') || ''
      });

      if (data.respuesta === 'ok') {

        const atributoId = formData.get('atributo_id');
        const attr = ATRIBUTOS.find(
            a => String(a.id) === String(atributoId)
        );

        if (attr) {
            attr.terminos = attr.terminos || [];
            attr.terminos.push(data.termino);
        }

        const productoAttr = state.productoAtributos.find(
            x => String(x.atributo.id) === String(atributoId)
        );

        if (productoAttr) {
            productoAttr.valores.push({
                id: data.termino.id,
                nombre: data.termino.nombre,
                slug: data.termino.slug
            });
        }

        renderAtributoBlocks();

        document.dispatchEvent(new CustomEvent('valorCreado'));

        showAlert(
            'success',
            'Valor creado',
            '',
            1200,
            false
        );

        form.reset();
      }else {
        showAlert('error', 'Error', data.mensaje);
      }
    } catch (error) {
      showAlert('error', 'Error servidor', 'Error al crear el valor');
    } finally {
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }
  }

  function initCategoriasSystem() {
    const catSelect = qs('#categoriaSelect');
    const subList = qs('#subcategoriaList');
    
    if (!catSelect || !subList) return;
    
    catSelect.innerHTML = '<option value="">-- Seleccione categoría --</option>';
    
    CATEGORIAS.forEach(c => {
      const option = document.createElement('option');
      option.value = c.id;
      option.textContent = c.nombre;
      catSelect.appendChild(option);
    });

    catSelect.addEventListener('change', function() {
      const id = this.value;
      subList.innerHTML = '';
      
      if (!id) {
        subList.innerHTML = '<div class="text-muted text-center py-3">Selecciona una categoría primero</div>';
        return;
      }
      
      const cat = CATEGORIAS.find(x => String(x.id) === String(id));
      if (!cat?.subcategorias?.length) {
        subList.innerHTML = '<div class="text-muted text-center py-3">No hay subcategorías</div>';
        return;
      }
      
      cat.subcategorias.forEach(s => {
        const uid = `sub_${s.id}`;
        const row = document.createElement('div');
        row.className = 'form-check';
        row.innerHTML = `
          <input class="form-check-input" type="radio" name="id_subCategorias" id="${uid}" value="${s.id}">
          <label class="form-check-label" for="${uid}">${s.nombre}</label>
        `;
        subList.appendChild(row);
      });
    });
  }

  function initEtiquetasSystem() {
    renderAvailableTags();
    renderSelectedTags();
    
    qs('#btnAddTag')?.addEventListener('click', handleAddTag);
    qs('#availableTags')?.addEventListener('change', handleTagCheckboxChange);
  }

  function renderAvailableTags() {
    const container = qs('#availableTags');
    if (!container) return;
    
    const selectedTagIds = new Set();
    state.selectedTags.forEach((tag, slug) => {
        if (tag.id) selectedTagIds.add(tag.id);
    });
    
    container.innerHTML = '';
    
    if (!ETIQUETAS.length) {
        container.innerHTML = '<div class="text-muted py-2 text-center">No hay etiquetas disponibles</div>';
        return;
    }
    
    ETIQUETAS.forEach(tag => {
        const id = `tag_av_${tag.id}`;
        const wrapper = document.createElement('div');
        wrapper.className = 'form-check';
        wrapper.innerHTML = `
            <input class="form-check-input tag-available" type="checkbox" id="${id}" 
                   data-id="${tag.id}" data-name="${tag.nombre}"
                   ${selectedTagIds.has(tag.id) ? 'checked' : ''}>
            <label class="form-check-label" for="${id}">${tag.nombre}</label>
        `;
        container.appendChild(wrapper);
    });
    
    container.querySelectorAll('.tag-available').forEach(checkbox => {
        checkbox.removeEventListener('change', handleTagCheckboxChange);
        checkbox.addEventListener('change', handleTagCheckboxChange);
    });
  }

  function renderSelectedTags() {
    const container = qs('#selectedTags');
    if (!container) return;
    
    container.innerHTML = '';
    
    state.selectedTags.forEach((tag, slug) => {
      const chip = document.createElement('span');
      chip.className = 'chip';
      chip.innerHTML = `
        <span>${escapeHtml(slug)}</span>
        <span class="remove ms-2" data-slug="${slug}">&times;</span>
      `;
      container.appendChild(chip);
    });
    
    qsa('#selectedTags .remove').forEach(el => {
      el.addEventListener('click', (e) => {
        const slug = e.target.dataset.slug;
        const tagData = state.selectedTags.get(slug);
        
        state.selectedTags.delete(slug);
        
        if (tagData?.id) {
          const checkbox = document.querySelector(`input.tag-available[data-id="${tagData.id}"]`);
          if (checkbox) checkbox.checked = false;
        }
        
        renderSelectedTags();
      });
    });
  }

  async function handleAddTag() {
    const tagInput = qs('#tagInput');
    const val = (tagInput.value || '').trim();
    if (!val) return;

    try {
        const { data } = await axios.post('productos', {
            opcion: 'CrearEtiqueta',
            nombre: val
        });

        if (data.respuesta === 'ok') {
            const nueva = data.etiqueta;
            
            ETIQUETAS.push(nueva);
            
            const container = qs('#availableTags');
            if (container) {
                const id = `tag_av_${nueva.id}`;
                const wrapper = document.createElement('div');
                wrapper.className = 'form-check';
                wrapper.innerHTML = `
                    <input class="form-check-input tag-available" type="checkbox" id="${id}" 
                           data-id="${nueva.id}" data-name="${nueva.nombre}"
                           checked>
                    <label class="form-check-label" for="${id}">${escapeHtml(nueva.nombre)}</label>
                `;
                container.appendChild(wrapper);
                const checkbox = wrapper.querySelector('.tag-available');
                checkbox.addEventListener('change', handleTagCheckboxChange);
            }
            
            const newSlug = nueva.nombre.toLowerCase().replace(/\s+/g, '-');
            state.selectedTags.set(newSlug, { 
                id: nueva.id, 
                name: nueva.nombre 
            });
            
            renderSelectedTags();
            
            tagInput.value = '';

            showAlert('success', 'Etiqueta creada', `"${nueva.nombre}" fue agregada correctamente`, 1800, false);
        } else {
            showAlert('error', 'Oops...', data.mensaje);
        }
    } catch (error) {
        showAlert('error', 'Error de servidor', 'No se pudo crear la etiqueta');
    }
  }

  function handleTagCheckboxChange(e) {
    const checkbox = e.target;
    if (!checkbox.classList.contains('tag-available')) return;
    
    const id = checkbox.dataset.id;
    const name = checkbox.dataset.name;
    const slug = name.toLowerCase().replace(/\s+/g, '-');
    
    if (checkbox.checked) {
        state.selectedTags.set(slug, { id, name });
    } else {
        state.selectedTags.delete(slug);
    }
    
    renderSelectedTags();
  }

  function initImagesSystem() {
    const imagenesInput = qs('#imagenesInput');
    const miniInput = qs('#miniaturaInput');
    const removeMiniBtn = qs('#removeMiniBtn');
    
    if (imagenesInput) {
      imagenesInput.addEventListener('change', handleImageUpload);
    }
    
    if (miniInput) {
      miniInput.addEventListener('change', handleMiniaturaUpload);
    }
    
    if (removeMiniBtn) {
      removeMiniBtn.addEventListener('click', handleRemoveMiniatura);
    }
    
    updateImageStatus();
  }

  function handleImageUpload(e) {
    addFilesToList(e.target.files);
    e.target.value = '';
  }

  function addFilesToList(files) {
    const fileArray = Array.from(files || []);
    
    for (const file of fileArray) {
      if (state.imageFiles.length >= MAX_IMAGES) {
        showAlert('error', 'Error', `Máximo ${MAX_IMAGES} imágenes`);
        break;
      }
      
      if (!file.type.startsWith('image/')) {
        showAlert('warning', 'Advertencia', 'Solo imágenes permitidas');
        continue;
      }
      
      if (file.size > MAX_SIZE_BYTES) {
        showAlert('warning', 'Advertencia', 'Cada imagen debe ser <= 4MB');
        continue;
      }
      
      const isDuplicate = state.imageFiles.some(x => 
        x.file.name === file.name && x.file.size === file.size
      );
      if (isDuplicate) continue;
      
      const id = 'f' + (state.nextFileId++);
      const url = URL.createObjectURL(file);
      
      state.imageFiles.push({ 
        _id: id, 
        file: file, 
        url: url,
        name: file.name,
        size: file.size
      });
      createPreviewCard({ _id: id, url });
    }
    
    updateImageStatus();
  }

  function createPreviewCard(fileObj) {
    const container = qs('#previewContainer');
    if (!container) return;
    
    const wrapper = document.createElement('div');
    wrapper.className = 'preview-card';
    wrapper.dataset.id = fileObj._id;
    wrapper.innerHTML = `
      <img src="${fileObj.url}" class="img-preview" />
      <button type="button" class="btn-remove" title="Eliminar imagen">&times;</button>
    `;
    
    wrapper.querySelector('.btn-remove').addEventListener('click', () => {
      removeImageById(fileObj._id);
    });
    
    container.appendChild(wrapper);
  }

  function removeImageById(id) {
    const index = state.imageFiles.findIndex(i => i._id === id);
    if (index >= 0) {
      URL.revokeObjectURL(state.imageFiles[index].url);
      state.imageFiles.splice(index, 1);
    }
    
    const element = qs(`[data-id="${id}"]`);
    if (element) element.remove();
    
    updateImageStatus();
  }

  function updateImageStatus() {
    const imagenesInput = qs('#imagenesInput');
    if (imagenesInput) {
      imagenesInput.disabled = state.imageFiles.length >= MAX_IMAGES;
    }
  }

  function handleMiniaturaUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
      showAlert('warning', 'Advertencia', 'Solo imágenes permitidas');
      e.target.value = '';
      return;
    }
    
    if (file.size > MAX_SIZE_BYTES) {
      showAlert('warning', 'Advertencia', 'La miniatura debe ser <= 4MB');
      e.target.value = '';
      return;
    }
    
    const miniImg = qs('#miniImg');
    const miniPlaceholder = qs('#miniPlaceholder');
    const removeMiniBtn = qs('#removeMiniBtn');
    
    if (!miniImg || !miniPlaceholder || !removeMiniBtn) return;
    
    const url = URL.createObjectURL(file);
    miniImg.src = url;
    miniImg.style.display = 'block';
    miniPlaceholder.style.display = 'none';
    removeMiniBtn.classList.remove('d-none');
    
    state.miniaturaFile = file;
  }

  function handleRemoveMiniatura() {
    const miniInput = qs('#miniaturaInput');
    const miniImg = qs('#miniImg');
    const miniPlaceholder = qs('#miniPlaceholder');
    const removeMiniBtn = qs('#removeMiniBtn');
    
    if (!miniInput || !miniImg || !miniPlaceholder || !removeMiniBtn) return;
    
    miniInput.value = '';
    
    if (miniImg.src) {
      URL.revokeObjectURL(miniImg.src);
    }
    
    miniImg.src = '';
    miniImg.style.display = 'none';
    miniPlaceholder.style.display = 'flex';
    removeMiniBtn.classList.add('d-none');
    state.miniaturaFile = null;
  }

  function initQuickPreview() {
    const quickName = qs('#quickName');
    const quickPrice = qs('#quickPrice');
    
    if (!quickName || !quickPrice) return;
    
    qsa('[name="nombre"]').forEach(input => {
      input.addEventListener('input', () => {
        quickName.textContent = input.value || '—';
      });
    });
    
    qsa('[name="precio_regular"]').forEach(input => {
      input.addEventListener('input', () => {
        quickPrice.textContent = input.value ? `S/ ${Number(input.value).toFixed(2)}` : 'S/ 0.00';
      });
    });
  }

  function syncAllVariationsBeforeSave() {
    document.querySelectorAll('[data-variation-index]').forEach(wrapper => {
        const idx = wrapper.dataset.variationIndex;
        const variacion = state.variaciones[idx];
        if (!variacion) return;

        variacion.sku = wrapper.querySelector('.variation-sku')?.value || '';
        variacion.stock = wrapper.querySelector('.variation-stock')?.value || '';
        variacion.price_normal = wrapper.querySelector('.variation-price-normal')?.value || '';
        variacion.price_sale = wrapper.querySelector('.variation-price-sale')?.value || '';
        variacion.weight = wrapper.querySelector('.variation-weight')?.value || '';
        variacion.weight_type = wrapper.querySelector('.variation-weight-type')?.value || '';
        variacion.length = wrapper.querySelector('.variation-length')?.value || '';
        variacion.width = wrapper.querySelector('.variation-width')?.value || '';
        variacion.height = wrapper.querySelector('.variation-height')?.value || '';
        variacion.description = wrapper.querySelector('.variation-description')?.value || '';

        const checkedBackorder = wrapper.querySelector('.allow-backorder:checked');
        variacion.backorder = checkedBackorder ? checkedBackorder.value : 'no';

        const attrs = [];
        wrapper.querySelectorAll('.variation-attr').forEach(sel => {
            if (sel.value) {
                attrs.push({
                    atrId: sel.dataset.atrId,
                    termId: sel.value === "0" ? null : sel.value 
                });
            }
        });
        variacion.atributos = attrs;
    });
  }

  function validarProducto() {
    const tipo = qs('#tipoProductoSelect')?.value;
    const nombre = qs('[name="nombre"]')?.value.trim();
    const estado = qs('[name="estado"]')?.value;
    const miniatura = state.miniaturaFile;
    const categoria = qs('#categoriaSelect')?.value;
    const subcat = qs('[name="id_subCategorias"]:checked')?.value;

    if (!estado || !nombre || !miniatura || !categoria || !subcat) {
      showAlert('warning','Campos obligatorios','Debes completar: nombre, estado, miniatura, categoría y subcategoría.');
      return false;
    }

    if (tipo === 'simple') {
      const precio = qs('[name="precio_regular"]')?.value;
      if (!precio) {
        showAlert('warning','Precio requerido','El producto simple requiere precio regular.');
        return false;
      }

      if (qs('#checkRebaja')?.checked) {
        const pr = qs('[name="precio_rebajado"]')?.value;
        const fi = qs('[name="fecha_inicio_rebaja"]')?.value;
        const ff = qs('[name="fecha_fin_rebaja"]')?.value;
        if (!pr || !fi || !ff) {
          showAlert('warning','Datos incompletos','Si programas rebaja debes indicar precio rebajado y fechas.');
          return false;
        }
      }

      if (qs('#checkGestion')?.checked) {
        const stock = qs('[name="stock"]')?.value;
        if (!stock) {
          showAlert('warning','Stock requerido','Si gestionas inventario, el stock es obligatorio.');
          return false;
        }
      }

    } else if (tipo === 'variable') {
      if (qs('#checkGestion')?.checked) {
          const stock = qs('[name="stock"]')?.value;
          if (!stock) {
              showAlert('warning','Stock requerido','Si gestionas inventario, el stock es obligatorio.');
              return false;
          }
      }

      const material = state.productoAtributos.find(a => a.atributo.nombre.toLowerCase() === 'material');
      if (material && (!material.valores || material.valores.length === 0)) {
          showAlert('warning','Valores requeridos','El atributo Material debe tener valores seleccionados.');
          return false;
      }

      if (!state.variaciones.length) {
          showAlert('warning','Variaciones requeridas','Debes generar al menos una variación.');
          return false;
      }

    } else if (tipo === 'agrupado') {
      if (!state.relacionados.length) {
        showAlert('warning','Productos agrupados','Debes seleccionar productos relacionados en agrupado.');
        return false;
      }
    }

    return true;
  }

  function initFormSubmission() {
    const form = qs('#formProducto');
    if (form) {
      form.addEventListener('submit', handleFormSubmit);
    }
  }

  function handleFormSubmit(ev) {
    ev.preventDefault();

    
    if (!validarProducto()) {
      state.isSubmitting = false;
      return;
    }
    
    state.isSubmitting = true;

    const form = ev.target;
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Guardando...';
    submitBtn.disabled = true;
    
    syncAllVariationsBeforeSave();
    
    const formData = buildFormData(form);
    
    fetch('productos', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.respuesta === 'ok') {

          showAlert(
              'success',
              'Éxito',
              'Producto guardado correctamente.'
          );

          state.modalTransition = false;
          const modalEl = document.getElementById('modalProducto');
          const modal = bootstrap.Modal.getInstance(modalEl) ||
                        new bootstrap.Modal(modalEl);
          modal.hide();

          $('#tablaProductos').DataTable().ajax.reload(null, false);
        } else {
            showAlert('error', 'Error', data.mensaje || 'Error al guardar el producto.');
        }
    })
    .catch(err => {
        showAlert('error', 'Error', 'No se pudo conectar con el servidor.');
    })
    .finally(() => {
        setTimeout(() => {
          state.isSubmitting = false;
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        }, 2000);
    });
  }

  function buildFormData(form) {
    const formData = new FormData(form);
    if (typeof tinymce !== 'undefined' && tinymce.get('descripcionLarga')) {
        const contenido = tinymce.get('descripcionLarga').getContent();
        formData.set('descripcion_larga', contenido);
    }
    
    const keysToRemove = [];
    for (let key of formData.keys()) {
      if (key.startsWith('variation_') || key.startsWith('variation_images_')) {
        keysToRemove.push(key);
      }
    }
    keysToRemove.forEach(key => formData.delete(key));
    
    formData.append('opcion', 'Crear');

    if (state.imageFiles && state.imageFiles.length > 0) {
        state.imageFiles.forEach((imageFile, index) => {
            formData.append('imagenes[]', imageFile.file);
        });
    }

    if (state.selectedTags && state.selectedTags.size > 0) {
        const tagIds = Array.from(state.selectedTags.values()).map(tag => tag.id);
        formData.append('etiquetas', JSON.stringify(tagIds));
    }

    if (state.productoAtributos && state.productoAtributos.length) {
        formData.append('atributos', JSON.stringify(state.productoAtributos.map(a => ({
            atributo_id: a.atributo.id,
            valores: a.valores.map(v => v.id),
            variacion: a.variacion,
            visible: a.visible
        }))));
    }

    if (state.variaciones && state.variaciones.length) {
        const variacionesParaEnviar = state.variaciones.map((variacion, index) => {
            const variacionData = {
              sku: variacion.sku || null,
              gestion_inventario: variacion.gestion_inventario ? 1 : 0,
              stock: variacion.stock !== undefined && variacion.stock !== '' ? Number(variacion.stock) : 0,
              price_normal: variacion.price_normal ? Number(variacion.price_normal) : 0,
              price_sale: variacion.price_sale ? Number(variacion.price_sale) : 0,
              sale_start: variacion.sale_start || null,
              sale_end: variacion.sale_end || null,
              weight: variacion.weight ? Number(variacion.weight) : null,     
              weight_type: variacion.weight_type || 'kg',
              length: variacion.length ? Number(variacion.length) : null,    
              width: variacion.width ? Number(variacion.width) : null,       
              height: variacion.height ? Number(variacion.height) : null,     
              description: variacion.description || null,
              backorder: variacion.backorder || 'no',
              atributos: (variacion.atributos || []).map(attr => ({
                  atrId: attr.atrId,
                  termId: attr.termId 
              }))
          };
            
            if (variacion.images && variacion.images.length > 0) {
              variacion.images.forEach(img => {
                formData.append(`variation_images_${index}[]`, img.file);
              });
            }
            
            return variacionData;
        });

        formData.append('variaciones', JSON.stringify(variacionesParaEnviar));
    }

    const crosssells = (state.crosssells || []).map(p => p.id);
    const upsells = (state.upsells || []).map(p => p.id);
    formData.append('crosssells', JSON.stringify(crosssells));
    formData.append('upsells', JSON.stringify(upsells));

    if (state.relacionados && state.relacionados.length > 0) {
      const relacionados = state.relacionados.map(p => p.id);
      formData.append('relacionados', JSON.stringify(relacionados));
    }

    const camposAdicionales = [
        'gestion_inventario', 'estado_inventario', 'stock', 'stock_minimo',
        'max_stock', 'vendido_individualmente'
    ];

    camposAdicionales.forEach(campo => {
        const elemento = form.querySelector(`[name="${campo}"]`);
        if (elemento && !formData.has(campo)) {
            formData.append(campo, elemento.type === 'checkbox' ? (elemento.checked ? '1' : '0') : elemento.value);
        }
    });

    return formData;
}

  function initResetSystem() {
    const closeButtons = document.querySelectorAll('#modalProducto .btn-close, #modalProducto [data-bs-dismiss="modal"]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            resetForm(false); 
        });
    });
    
    const cancelBtn = qs('#modalProducto .btn-secondary');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            resetForm(false); 
        });
    }
  }

  function resetForm(keepType = false) {
    const form = qs('#formProducto');
    if (form) {
        form.reset();
    }

    if (typeof tinymce !== 'undefined' && tinymce.get('descripcionLarga')) {
        tinymce.get('descripcionLarga').setContent('');
    }

    state.imageFiles = [];
    state.selectedTags.clear();
    state.productoAtributos = [];
    state.upsells = [];
    state.crosssells = [];
    state.variaciones = [];
    state.isSubmitting = false;
    state.relacionados = [];
    state.miniaturaFile = null;
    state.variacionesPage = 1;

    let currentType = null;
    if (keepType) {
        const tipoSelect = qs('#tipoProductoSelect');
        if (tipoSelect) currentType = tipoSelect.value;
    }

    const tagContainers = document.querySelectorAll('.tag-container');
    tagContainers.forEach(container => container.innerHTML = '');

    const previewContainer = qs('#previewContainer');
    if (previewContainer) previewContainer.innerHTML = '';
    
    const miniImg = qs('#miniImg');
    const miniPlaceholder = qs('#miniPlaceholder');
    const removeMiniBtn = qs('#removeMiniBtn');
    if (miniImg) miniImg.src = '';
    if (miniImg) miniImg.style.display = 'none';
    if (miniPlaceholder) miniPlaceholder.style.display = 'flex';
    if (removeMiniBtn) removeMiniBtn.classList.add('d-none');
    
    const miniInput = qs('#miniaturaInput');
    if (miniInput) miniInput.value = '';

    const imagenesInput = qs('#imagenesInput');
    if (imagenesInput) imagenesInput.value = '';

    const subcatRadios = document.querySelectorAll('input[name="id_subCategorias"]');
    subcatRadios.forEach(radio => radio.checked = false);

    const catSelect = qs('#categoriaSelect');
    if (catSelect) catSelect.value = '';
    const subList = qs('#subcategoriaList');
    if (subList) subList.innerHTML = '<div class="text-muted text-center py-3">Selecciona una categoría primero</div>';
    const checkRebaja = qs('#checkRebaja');
    const rebajaFechas = qs('#rebajaFechas');
    if (checkRebaja) {
        checkRebaja.checked = false;
        if (rebajaFechas) rebajaFechas.classList.add('d-none');
    }

    const checkGestion = qs('#checkGestion');
    const invExtra = qs('#invExtra');
    if (checkGestion) {
        checkGestion.checked = false;
        if (invExtra) invExtra.classList.add('d-none');
    }

    const stockInput = qs('input[name="stock"]');
    if (stockInput) stockInput.value = '';
    
    const backordersRadios = document.querySelectorAll('input[name="backorders"]');
    backordersRadios.forEach(radio => radio.checked = false);
    if (backordersRadios.length > 0) backordersRadios[0].checked = true;

    const precioRegular = qs('input[name="precio_regular"]');
    if (precioRegular) precioRegular.value = '';
    
    const precioRebajado = qs('input[name="precio_rebajado"]');
    if (precioRebajado) precioRebajado.value = '';
    
    const fechaInicio = qs('input[name="fecha_inicio_rebaja"]');
    const fechaFin = qs('input[name="fecha_fin_rebaja"]');
    if (fechaInicio) fechaInicio.value = '';
    if (fechaFin) fechaFin.value = '';
    const peso = qs('input[name="peso"]');
    if (peso) peso.value = '';
    
    const longitud = qs('input[name="longitud"]');
    const anchura = qs('input[name="anchura"]');
    const altura = qs('input[name="altura"]');
    if (longitud) longitud.value = '';
    if (anchura) anchura.value = '';
    if (altura) altura.value = '';

    const notaInterna = qs('textarea[name="nota_interna"]');
    if (notaInterna) notaInterna.value = '';
    
    const permiteValoraciones = qs('input[name="permite_valoraciones"]');
    if (permiteValoraciones) permiteValoraciones.checked = true;
    const tipoSelect = qs('#tipoProductoSelect');
    if (tipoSelect && !keepType) {
        tipoSelect.value = 'simple';
    } else if (tipoSelect && keepType && currentType) {
        tipoSelect.value = currentType;
    }

    renderSelectedTags();
    renderAtributoBlocks();
    renderVariacionesUI();
    updateImageStatus();

    state.imageFiles.forEach(img => {
        if (img.url) URL.revokeObjectURL(img.url);
    });
  }

  function initToggleSystems() {
    const checkRebaja = qs('#checkRebaja');
    const rebajaFechas = qs('#rebajaFechas');
    
    if (checkRebaja && rebajaFechas) {
      checkRebaja.addEventListener('change', function() {
        rebajaFechas.classList.toggle('d-none', !this.checked);
      });
    }
    
    const checkGestion = qs('#checkGestion');
    const invExtra = qs('#invExtra');
    
    if (checkGestion && invExtra) {
      checkGestion.addEventListener('change', function() {
        invExtra.classList.toggle('d-none', !this.checked);
      });
    }
  }

  function showAlert(icon, title, text, timer = null, showConfirmButton = true) {
    const config = {
      icon,
      title,
      text,
      showConfirmButton
    };
    
    if (timer) {
      config.timer = timer;
    }
    
    Swal.fire(config);
  }

  function init() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initializeApp);
    } else {
      setTimeout(initializeApp, 100);
    }
  }

  function initializeApp() {
    initModals();
    initAtributosSystem();
    initCategoriasSystem();
    initEtiquetasSystem();
    initImagesSystem();
    initQuickPreview();
    initFormSubmission();
    initResetSystem();
    initToggleSystems();
    initTipoProductoSystem();
    initRelacionadosInputs();
    initNewProductButton();
  }

  init();

  window._productAdmin = window._productAdmin || {};
  window._productAdmin.generateVariationsFromAtributos = generateVariationsFromAtributos;
  window._productAdmin.state = state;

})();

const configState = {
    productoId: null,
    atributos: [],
    historial: [],
    imagenesSubidas: {}
};

let variacionesStockData = [];
let variacionesStockOriginal = {};
let cambiosPendientes = {};
let datatableVariacionesStock = null;
let isDataTableInitialized = false;

async function renderVariacionesStock(productoId) {
    const container = document.getElementById('variaciones-stock-container');
    if (!container) return;

    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted">Cargando variaciones...</p>
        </div>
    `;

    try {
        const { data } = await axios.post('productos', {
            opcion: 'ObtenerVariacionesStock',
            id: productoId
        });

        if (data.respuesta !== 'ok') {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${data.mensaje || 'No se pudieron cargar las variaciones'}
                </div>
            `;
            return;
        }

        variacionesStockData = data.variaciones || [];
        variacionesStockOriginal = {};
        variacionesStockData.forEach(v => {
            variacionesStockOriginal[v.id] = { stock: v.stock };
        });
        
        cambiosPendientes = {};

        if (variacionesStockData.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No hay variaciones con gestión de inventario activada.
                    <br><small>Las variaciones deben tener marcada la opción "Gestionar inventario" para aparecer aquí.</small>
                </div>
            `;
            return;
        }

        if (datatableVariacionesStock) {
            datatableVariacionesStock.destroy();
            datatableVariacionesStock = null;
            isDataTableInitialized = false;
        }

        renderVariacionesStockTable(container);

    } catch (error) {
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Error al cargar las variaciones. Intenta nuevamente.
            </div>
        `;
    }
}

function renderVariacionesStockTable(container) {
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Procesando...</span>
            </div>
            <p class="text-muted">Preparando tabla de variaciones...</p>
        </div>
    `;

    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover" id="tablaVariacionesStock">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Variación</th>
                        <th>SKU</th>
                        <th style="width: 120px;">Stock Actual</th>
                        <th style="width: 130px;">Nuevo Stock</th>
                        <th>Motivo</th>
                        <th style="width: 60px; text-align: center;">Historial</th>
                        <th style="width: 90px; text-align: center;">Acción</th>
                    </tr>
                </thead>
                <tbody>
    `;

    variacionesStockData.forEach((variacion, index) => {
        const atributosStr = variacion.atributos_nombres || 'Sin atributos';
        const tieneCambio = cambiosPendientes[variacion.id] !== undefined;
        const nuevoStock = tieneCambio ? cambiosPendientes[variacion.id].nuevoStock : variacion.stock;
        const motivo = tieneCambio ? cambiosPendientes[variacion.id].motivo : '';
        const esDiferente = tieneCambio && nuevoStock !== variacion.stock;
        
        html += `
            <tr data-variacion-id="${variacion.id}" class="${esDiferente ? 'table-warning' : ''}">
                <td>${index + 1}</td>
                <td>
                    <span class="fw-semibold">${escapeHtml(atributosStr)}</span>
                    ${variacion.nombre ? `<br><small class="text-muted">${escapeHtml(variacion.nombre)}</small>` : ''}
                </td>
                <td><code>${escapeHtml(variacion.sku || 'N/A')}</code></td>
                <td>
                    <span class="badge ${variacion.stock > 0 ? 'bg-success' : 'bg-danger'} fs-6">
                        ${variacion.stock}
                    </span>
                </td>
                <td>
                    <div class="input-group input-group-sm" style="min-width: 110px;">
                        <button class="btn btn-sm btn-outline-secondary variacion-stock-decrement" 
                                data-variacion-id="${variacion.id}"
                                title="Disminuir stock en 1">
                            <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" class="form-control form-control-sm variacion-stock-input text-center" 
                               data-variacion-id="${variacion.id}"
                               min="0" 
                               value="${nuevoStock}" 
                               style="width: 60px; max-width: 60px;">
                        <button class="btn btn-sm btn-outline-secondary variacion-stock-increment" 
                                data-variacion-id="${variacion.id}"
                                title="Aumentar stock en 1">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm variacion-motivo-input" 
                           data-variacion-id="${variacion.id}"
                           placeholder="Motivo del cambio" 
                           value="${escapeHtml(motivo)}"
                           style="min-width: 120px;">
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-info variacion-ver-historial" 
                            data-variacion-id="${variacion.id}"
                            title="Ver historial de stock">
                        <i class="bi bi-clock-history"></i>
                    </button>
                </td>
                <td class="text-center">
                    ${esDiferente ? `
                        <button class="btn btn-sm btn-outline-danger variacion-descartar-cambio" 
                                data-variacion-id="${variacion.id}"
                                title="Descartar cambio">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    ` : `
                        <span class="text-muted small">Sin cambios</span>
                    `}
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;

    setTimeout(() => {
        inicializarDataTableVariaciones();
    }, 100);
}

function inicializarDataTableVariaciones() {
    if (!$('#tablaVariacionesStock').length) {
        console.warn('Tabla #tablaVariacionesStock no encontrada en el DOM');
        return;
    }

    if ($.fn.DataTable.isDataTable('#tablaVariacionesStock')) {
        $('#tablaVariacionesStock').DataTable().destroy();
    }

    datatableVariacionesStock = $('#tablaVariacionesStock').DataTable({
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "No se encontraron registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "searchPlaceholder": "Buscar variación..."
        },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [4, 5, 6, 7] },
            { className: 'text-center', targets: [6, 7] }
        ],
        drawCallback: function(settings) {
            attachVariacionesStockEvents();
        }
    });

    isDataTableInitialized = true;
    actualizarContadorCambios();
}

function attachVariacionesStockEvents() {
    const container = document.getElementById('variaciones-stock-container');
    if (!container) return;

    container.querySelectorAll('.variacion-stock-increment').forEach(btn => {
        btn.removeEventListener('click', handleIncrementClick);
        btn.addEventListener('click', handleIncrementClick);
    });

    container.querySelectorAll('.variacion-stock-decrement').forEach(btn => {
        btn.removeEventListener('click', handleDecrementClick);
        btn.addEventListener('click', handleDecrementClick);
    });

    container.querySelectorAll('.variacion-stock-input').forEach(input => {
        input.removeEventListener('change', handleStockChange);
        input.addEventListener('change', handleStockChange);
    });

    container.querySelectorAll('.variacion-motivo-input').forEach(input => {
        input.removeEventListener('input', handleMotivoChange);
        input.addEventListener('input', handleMotivoChange);
    });

    container.querySelectorAll('.variacion-descartar-cambio').forEach(btn => {
        btn.removeEventListener('click', handleDescartarCambio);
        btn.addEventListener('click', handleDescartarCambio);
    });

    container.querySelectorAll('.variacion-ver-historial').forEach(btn => {
        btn.removeEventListener('click', handleVerHistorial);
        btn.addEventListener('click', handleVerHistorial);
    });
}

function handleIncrementClick(e) {
    const variacionId = this.dataset.variacionId;
    const row = document.querySelector(`tr[data-variacion-id="${variacionId}"]`);
    if (!row) return;
    const input = row.querySelector('.variacion-stock-input');
    if (!input) return;
    const currentVal = parseInt(input.value) || 0;
    input.value = currentVal + 1;
    input.dispatchEvent(new Event('change'));
}

function handleDecrementClick(e) {
    const variacionId = this.dataset.variacionId;
    const row = document.querySelector(`tr[data-variacion-id="${variacionId}"]`);
    if (!row) return;
    const input = row.querySelector('.variacion-stock-input');
    if (!input) return;
    const currentVal = parseInt(input.value) || 0;
    if (currentVal > 0) {
        input.value = currentVal - 1;
        input.dispatchEvent(new Event('change'));
    }
}

function handleStockChange(e) {
    const variacionId = this.dataset.variacionId;
    const newStock = parseInt(this.value) || 0;
    const row = document.querySelector(`tr[data-variacion-id="${variacionId}"]`);
    if (!row) return;
    const motivoInput = row.querySelector('.variacion-motivo-input');
    const motivo = motivoInput?.value || '';
    
    const variacion = variacionesStockData.find(v => v.id == variacionId);
    if (!variacion) return;
    if (newStock !== variacion.stock) {
        cambiosPendientes[variacionId] = {
            stockOriginal: variacion.stock,
            nuevoStock: newStock,
            motivo: motivo || 'Ajuste manual'
        };
    } else {
        delete cambiosPendientes[variacionId];
    }

    actualizarFilaVariacion(variacionId);
    actualizarContadorCambios();
}

function handleMotivoChange(e) {
    const variacionId = this.dataset.variacionId;
    const motivo = this.value;
    const row = document.querySelector(`tr[data-variacion-id="${variacionId}"]`);
    if (!row) return;
    const stockInput = row.querySelector('.variacion-stock-input');
    const currentStock = parseInt(stockInput?.value) || 0;
    
    const variacion = variacionesStockData.find(v => v.id == variacionId);
    if (!variacion) return;
    if (motivo && (currentStock !== variacion.stock || cambiosPendientes[variacionId])) {
        cambiosPendientes[variacionId] = {
            stockOriginal: variacion.stock,
            nuevoStock: currentStock,
            motivo: motivo
        };
        actualizarFilaVariacion(variacionId);
        actualizarContadorCambios();
    } else if (!motivo && cambiosPendientes[variacionId] && currentStock === variacion.stock) {
        delete cambiosPendientes[variacionId];
        actualizarFilaVariacion(variacionId);
        actualizarContadorCambios();
    }
}

function handleDescartarCambio(e) {
    const variacionId = this.dataset.variacionId;
    descartarCambioVariacion(variacionId);
}

function handleVerHistorial(e) {
    const variacionId = this.dataset.variacionId;
    const variacion = variacionesStockData.find(v => v.id == variacionId);
    if (variacion) {
        mostrarHistorialVariacion(variacion);
    }
}

function actualizarFilaVariacion(variacionId) {
    const row = document.querySelector(`tr[data-variacion-id="${variacionId}"]`);
    if (!row) return;

    const variacion = variacionesStockData.find(v => v.id == variacionId);
    if (!variacion) return;

    const tieneCambio = cambiosPendientes[variacionId] !== undefined;
    const nuevoStock = tieneCambio ? cambiosPendientes[variacionId].nuevoStock : variacion.stock;
    const esDiferente = tieneCambio && nuevoStock !== variacion.stock;

    row.className = esDiferente ? 'table-warning' : '';

    const input = row.querySelector('.variacion-stock-input');
    if (input) input.value = nuevoStock;

    const motivoInput = row.querySelector('.variacion-motivo-input');
    if (motivoInput && tieneCambio) {
        motivoInput.value = cambiosPendientes[variacionId].motivo || '';
    }

    const accionCell = row.querySelector('td:last-child');
    if (accionCell) {
        if (esDiferente) {
            accionCell.innerHTML = `
                <button class="btn btn-sm btn-outline-danger variacion-descartar-cambio" 
                        data-variacion-id="${variacionId}"
                        title="Descartar cambio">
                    <i class="bi bi-x-lg"></i>
                </button>
            `;
            
            accionCell.querySelector('.variacion-descartar-cambio')?.addEventListener('click', function() {
                descartarCambioVariacion(variacionId);
            });
        } else {
            accionCell.innerHTML = `<span class="text-muted small">Sin cambios</span>`;
        }
    }
}

function descartarCambioVariacion(variacionId) {
    delete cambiosPendientes[variacionId];
    const variacion = variacionesStockData.find(v => v.id == variacionId);
    if (variacion) {
        const row = document.querySelector(`tr[data-variacion-id="${variacionId}"]`);
        if (row) {
            const input = row.querySelector('.variacion-stock-input');
            if (input) input.value = variacion.stock;
            const motivoInput = row.querySelector('.variacion-motivo-input');
            if (motivoInput) motivoInput.value = '';
        }
        actualizarFilaVariacion(variacionId);
        actualizarContadorCambios();
    }
}

function actualizarContadorCambios() {
    const totalCambios = Object.keys(cambiosPendientes).filter(id => {
        const cambio = cambiosPendientes[id];
        const variacion = variacionesStockData.find(v => v.id == id);
        return variacion && cambio.nuevoStock !== variacion.stock;
    }).length;

    const badge = document.getElementById('badgeCambiosPendientes');
    if (badge) {
        if (totalCambios > 0) {
            badge.className = 'badge bg-warning text-dark align-self-center';
            badge.textContent = `${totalCambios} cambio(s) pendiente(s)`;
        } else {
            badge.className = 'badge bg-secondary align-self-center';
            badge.textContent = 'Sin cambios pendientes';
        }
    }
}

document.getElementById('btnGuardarCambiosStockVariaciones')?.addEventListener('click', async function() {
    const changes = Object.keys(cambiosPendientes).filter(id => {
        const cambio = cambiosPendientes[id];
        const variacion = variacionesStockData.find(v => v.id == id);
        return variacion && cambio.nuevoStock !== variacion.stock;
    });

    if (changes.length === 0) {
        Swal.fire('Info', 'No hay cambios pendientes para guardar.', 'info');
        return;
    }

    const confirm = await Swal.fire({
        title: '¿Guardar todos los cambios?',
        html: `
            <p>Se aplicarán <strong>${changes.length}</strong> cambio(s) en el stock de las variaciones.</p>
            <div class="text-start mt-3" style="max-height: 200px; overflow-y: auto;">
                ${changes.map(id => {
                    const cambio = cambiosPendientes[id];
                    const variacion = variacionesStockData.find(v => v.id == id);
                    const diff = cambio.nuevoStock - variacion.stock;
                    return `
                        <div class="small border-bottom py-1">
                            <span class="fw-semibold">${variacion.atributos_nombres || 'Sin nombre'}</span>
                            <span class="text-muted">(${variacion.sku || 'N/A'})</span>
                            <span class="badge ${diff > 0 ? 'bg-success' : 'bg-danger'}">
                                ${diff > 0 ? '+' : ''}${diff}
                            </span>
                            <span class="text-muted">→ ${cambio.nuevoStock}</span>
                            <br><small class="text-muted">Motivo: ${cambio.motivo || 'Ajuste manual'}</small>
                        </div>
                    `;
                }).join('')}
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar cambios',
        cancelButtonText: 'Cancelar'
    });

    if (!confirm.isConfirmed) return;

    const preloaderMessages = showPreloader("Guardando cambios de stock...", "registrar");

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Guardando...';

    try {
        const productoId = document.getElementById('config_producto_id').value;
        
        const cambiosData = changes.map(id => {
            const cambio = cambiosPendientes[id];
            const variacion = variacionesStockData.find(v => v.id == id);
            return {
                variacion_id: id,
                nuevo_stock: cambio.nuevoStock,
                motivo: cambio.motivo || 'Ajuste manual',
                stock_anterior: variacion.stock
            };
        });

        const { data } = await axios.post('productos', {
            opcion: 'GuardarCambiosMasivosVariacionesStock',
            producto_id: productoId,
            cambios: cambiosData
        });

        Swal.close();

        if (data.respuesta === 'ok') {
            Swal.fire({
                icon: 'success',
                title: '¡Cambios guardados!',
                text: data.mensaje || 'Todos los cambios de stock se han aplicado correctamente.',
                timer: 2000,
                showConfirmButton: false
            });

            cambiosData.forEach(c => {
                const variacion = variacionesStockData.find(v => v.id == c.variacion_id);
                if (variacion) {
                    variacion.stock = c.nuevo_stock;
                }
                delete cambiosPendientes[c.variacion_id];
            });

            await renderVariacionesStock(productoId);
            
            if (document.getElementById('panel-movimientos-stock').classList.contains('show')) {
                await cargarConfiguracionProducto(productoId);
            }
        } else {
            Swal.fire('Error', data.mensaje || 'No se pudieron guardar los cambios', 'error');
        }
    } catch (error) {
        Swal.close();
        Swal.fire('Error', 'Error al guardar los cambios', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar todos los cambios';
    }
});

document.getElementById('btnDescartarCambiosStockVariaciones')?.addEventListener('click', async function() {
    const totalCambios = Object.keys(cambiosPendientes).length;
    if (totalCambios === 0) {
        Swal.fire('Info', 'No hay cambios pendientes para descartar.', 'info');
        return;
    }

    const confirm = await Swal.fire({
        title: '¿Descartar todos los cambios?',
        text: `Se descartarán ${totalCambios} cambio(s) pendiente(s).`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, descartar',
        cancelButtonText: 'Cancelar'
    });

    if (confirm.isConfirmed) {
        Object.keys(cambiosPendientes).forEach(id => {
            const variacion = variacionesStockData.find(v => v.id == id);
            if (variacion) {
                const row = document.querySelector(`tr[data-variacion-id="${id}"]`);
                if (row) {
                    const input = row.querySelector('.variacion-stock-input');
                    if (input) input.value = variacion.stock;
                    const motivoInput = row.querySelector('.variacion-motivo-input');
                    if (motivoInput) motivoInput.value = '';
                }
            }
        });

        cambiosPendientes = {};
        actualizarContadorCambios();
        
        document.querySelectorAll('#tablaVariacionesStock tbody tr').forEach(row => {
            const id = row.dataset.variacionId;
            if (id) actualizarFilaVariacion(id);
        });

        Swal.fire({
            icon: 'info',
            title: 'Cambios descartados',
            timer: 1000,
            showConfirmButton: false
        });
    }
});

document.getElementById('btnResetearStockVariaciones')?.addEventListener('click', async function() {
    const confirm = await Swal.fire({
        title: '¿Resetear stock a valores originales?',
        text: 'Esto restaurará el stock de TODAS las variaciones a los valores guardados en la base de datos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, resetear',
        cancelButtonText: 'Cancelar'
    });

    if (confirm.isConfirmed) {
        cambiosPendientes = {};
        const productoId = document.getElementById('config_producto_id').value;
        await renderVariacionesStock(productoId);
        
        Swal.fire({
            icon: 'success',
            title: 'Stock restaurado',
            text: 'El stock de todas las variaciones ha sido restaurado a los valores originales.',
            timer: 1500,
            showConfirmButton: false
        });
    }
});

async function mostrarHistorialVariacion(variacion) {
    try {
        const { data } = await axios.post('productos', {
            opcion: 'ObtenerHistorialVariacionStock',
            variacion_id: variacion.id
        });

        if (data.respuesta !== 'ok') {
            Swal.fire('Error', data.mensaje || 'No se pudo cargar el historial', 'error');
            return;
        }

        const historial = data.historial || [];
        const atributosStr = variacion.atributos_nombres || 'Sin atributos';

        let historialHtml = '';
        if (historial.length === 0) {
            historialHtml = '<tr><td colspan="6" class="text-center text-muted">No hay movimientos registrados</td></tr>';
        } else {
            historialHtml = historial.map(item => `
                <tr>
                    <td>${new Date(item.created_at).toLocaleString()}</td>
                    <td>${item.cantidad_anterior}</td>
                    <td>${item.nueva_cantidad}</td>
                    <td class="${item.diferencia > 0 ? 'text-success' : 'text-danger'}">
                        ${item.diferencia > 0 ? '+' : ''}${item.diferencia}
                    </td>
                    <td>${item.motivo || 'Sin motivo'}</td>
                    <td>${item.usuario_nombre || 'Sistema'}</td>
                </tr>
            `).join('');
        }

        Swal.fire({
            title: `Historial de Stock - ${atributosStr}`,
            html: `
                <div class="text-start">
                    <p><strong>SKU:</strong> <code>${variacion.sku || 'N/A'}</code></p>
                    <p><strong>Stock actual:</strong> <span class="badge ${variacion.stock > 0 ? 'bg-success' : 'bg-danger'}">${variacion.stock}</span></p>
                    <hr>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Anterior</th>
                                    <th>Nuevo</th>
                                    <th>Diferencia</th>
                                    <th>Motivo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${historialHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            `,
            width: 900,
            confirmButtonText: 'Cerrar'
        });

    } catch (error) {
        Swal.fire('Error', 'Error al cargar el historial', 'error');
    }
}

async function cargarConfiguracionProducto(productoId) {
    const preloaderMessages = showPreloader("Cargando configuración...", "cargar");
    
    try {
        const { data } = await axios.post('productos', {
            opcion: 'ObtenerConfiguracion',
            id: productoId
        });

        Swal.close();

        if (data.respuesta !== 'ok') {
            Swal.fire('Error', data.mensaje || 'No se pudo cargar la configuración', 'error');
            return;
        }

        configState.productoId = productoId;
        configState.atributos = data.atributos_config || [];
        configState.historial = data.historial_stock || [];
        configState.imagenesSubidas = {};
        configState.etiquetas = data.etiquetas_producto || [];

        const gestionaInventario = data.producto.gestion_inventario == 1 || data.producto.gestion_inventario === true;
        const esVariable = data.producto.tipo_producto === 'variable';

        configState.tipoProducto = data.producto.tipo_producto;

        toggleAllTabsVisibility(esVariable, gestionaInventario);

        if (esVariable) {
            await renderVariacionesStock(productoId);
        }

        document.getElementById('config_stock_minimo').value = data.producto.stock_minimo || 0;
        document.getElementById('config_max_stock').value = data.producto.max_stock || '';

        renderHistorialStock(configState.historial);
        
        if (esVariable) {
            renderAtributosConfig(configState.atributos);
        } else {
            const container = document.getElementById('config_atributos_container');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Los atributos solo están disponibles para productos de tipo <strong>Variable</strong>.
                        <br><small>Cambia el tipo de producto a "Variable" en el formulario principal para gestionar atributos.</small>
                    </div>
                `;
            }
        }
        
        renderEtiquetasConfig(configState.etiquetas);
        
        document.getElementById('config_producto_id').value = productoId;

        Swal.fire({
            icon: 'success',
            title: 'Configuración cargada',
            text: 'Los datos del producto se han cargado correctamente.',
            timer: 1500,
            showConfirmButton: false
        });

    } catch (error) {
        Swal.close();
        console.error('Error cargando configuración:', error);
        Swal.fire('Error', 'No se pudo cargar la configuración del producto', 'error');
    }
}

function toggleVariacionesStockTab(mostrar) {
    const navItem = document.getElementById('tab-variaciones-stock-nav');
    const panel = document.getElementById('panel-variaciones-stock');
    
    if (navItem) {
        navItem.style.display = mostrar ? '' : 'none';
        navItem.classList.toggle('d-none', !mostrar);
    }
    
    if (panel) {
        panel.style.display = mostrar ? '' : 'none';
        panel.classList.toggle('d-none', !mostrar);
    }
    
    if (!mostrar) {
        const activeTab = document.querySelector('#configTabs .nav-link.active');
        if (activeTab && activeTab.id === 'tab-variaciones-stock') {
            const allTabs = document.querySelectorAll('#configTabs .nav-link:not(.d-none)');
            let firstVisible = null;
            for (const tab of allTabs) {
                if (tab.id !== 'tab-variaciones-stock' && tab.id !== 'tab-atributos-config') {
                    firstVisible = tab;
                    break;
                }
            }
            if (firstVisible) {
                new bootstrap.Tab(firstVisible).show();
            }
        }

        if (datatableVariacionesStock) {
            datatableVariacionesStock.destroy();
            datatableVariacionesStock = null;
            isDataTableInitialized = false;
        }
    } else {
        if (configState.productoId) {
            cambiosPendientes = {};
            renderVariacionesStock(configState.productoId);
        }
    }
}

function toggleStockTabs(mostrar) {
    const tabLimitesStock = document.getElementById('tab-limites-stock');
    const tabMovimientosStock = document.getElementById('tab-movimientos-stock');
    const panelLimitesStock = document.getElementById('panel-limites-stock');
    const panelMovimientosStock = document.getElementById('panel-movimientos-stock');
    const tabAtributos = document.getElementById('tab-atributos-config');
    const panelAtributos = document.getElementById('panel-atributos-config');
    const tabEtiquetas = document.getElementById('tab-etiquetas-config');
    const panelEtiquetas = document.getElementById('panel-etiquetas-config');

    const esVariable = configState.tipoProducto === 'variable';

    if (mostrar) {
        if (tabLimitesStock) {
            tabLimitesStock.style.display = '';
            tabLimitesStock.classList.remove('d-none');
        }
        if (tabMovimientosStock) {
            tabMovimientosStock.style.display = '';
            tabMovimientosStock.classList.remove('d-none');
        }
        if (panelLimitesStock) {
            panelLimitesStock.style.display = '';
            panelLimitesStock.classList.remove('d-none');
        }
        if (panelMovimientosStock) {
            panelMovimientosStock.style.display = '';
            panelMovimientosStock.classList.remove('d-none');
        }

        if (tabAtributos) {
            if (esVariable) {
                tabAtributos.style.display = '';
                tabAtributos.classList.remove('d-none');
            } else {
                tabAtributos.style.display = 'none';
                tabAtributos.classList.add('d-none');
            }
        }
        if (panelAtributos) {
            if (esVariable) {
                panelAtributos.style.display = '';
                panelAtributos.classList.remove('d-none');
            } else {
                panelAtributos.style.display = 'none';
                panelAtributos.classList.add('d-none');
            }
        }

        if (tabEtiquetas) {
            tabEtiquetas.style.display = '';
            tabEtiquetas.classList.remove('d-none');
        }
        if (panelEtiquetas) {
            panelEtiquetas.style.display = '';
            panelEtiquetas.classList.remove('d-none');
        }

        if (esVariable) {
            toggleVariacionesStockTab(true);
        }

        document.querySelectorAll('.stock-disabled-message').forEach(el => el.remove());

        const allTabs = document.querySelectorAll('#configTabs .nav-link:not(.d-none)');
        let firstVisibleTab = null;
        for (const tab of allTabs) {
            const style = window.getComputedStyle(tab);
            if (style.display !== 'none' && !tab.classList.contains('d-none')) {
                firstVisibleTab = tab;
                break;
            }
        }

        if (firstVisibleTab && !firstVisibleTab.classList.contains('active')) {
            const bsTab = new bootstrap.Tab(firstVisibleTab);
            bsTab.show();
        }

    } else {
        if (tabLimitesStock) {
            tabLimitesStock.style.display = 'none';
            tabLimitesStock.classList.add('d-none');
        }
        if (tabMovimientosStock) {
            tabMovimientosStock.style.display = 'none';
            tabMovimientosStock.classList.add('d-none');
        }
        if (panelLimitesStock) {
            panelLimitesStock.style.display = 'none';
            panelLimitesStock.classList.add('d-none');
        }
        if (panelMovimientosStock) {
            panelMovimientosStock.style.display = 'none';
            panelMovimientosStock.classList.add('d-none');
        }

        toggleVariacionesStockTab(false);

        if (tabAtributos) {
            if (!esVariable) {
                tabAtributos.style.display = 'none';
                tabAtributos.classList.add('d-none');
            } else {
                tabAtributos.style.display = 'none';
                tabAtributos.classList.add('d-none');
            }
        }
        if (panelAtributos) {
            if (!esVariable) {
                panelAtributos.style.display = 'none';
                panelAtributos.classList.add('d-none');
            } else {
                panelAtributos.style.display = 'none';
                panelAtributos.classList.add('d-none');
            }
        }

        const activeTab = document.querySelector('#configTabs .nav-link.active');
        if (activeTab && 
            (activeTab.id === 'tab-limites-stock' || 
             activeTab.id === 'tab-movimientos-stock' || 
             activeTab.id === 'tab-variaciones-stock' ||
             activeTab.id === 'tab-atributos-config')) {
            
            const allTabs = document.querySelectorAll('#configTabs .nav-link:not(.d-none)');
            let firstVisibleTab = null;
            for (const tab of allTabs) {
                if (tab.id !== 'tab-limites-stock' && 
                    tab.id !== 'tab-movimientos-stock' && 
                    tab.id !== 'tab-variaciones-stock' &&
                    tab.id !== 'tab-atributos-config') {
                    firstVisibleTab = tab;
                    break;
                }
            }

            if (firstVisibleTab) {
                new bootstrap.Tab(firstVisibleTab).show();
            }
        }

        const panelAtributosEl = document.getElementById('panel-atributos-config');
        if (panelAtributosEl) {
            const existingMsg = panelAtributosEl.querySelector('.stock-disabled-message');
            if (existingMsg) existingMsg.remove();

            const msgDiv = document.createElement('div');
            msgDiv.className = 'alert alert-warning stock-disabled-message mt-3';
            msgDiv.innerHTML = `
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Inventario desactivado:</strong> Este producto no tiene activada la gestión de inventario. 
                Los tabs de "Límites de Stock" y "Historial de Stock" están ocultos.
                <br><br>
                <small>Para activar la gestión de inventario, edita el producto desde el formulario principal y marca la opción "Gestionar inventario".</small>
            `;
            panelAtributosEl.prepend(msgDiv);
        }
    }
}

function toggleAllTabsVisibility(esVariable, gestionaInventario) {
    const tabLimitesStock = document.getElementById('tab-limites-stock');
    const tabMovimientosStock = document.getElementById('tab-movimientos-stock');
    const tabAtributos = document.getElementById('tab-atributos-config');
    const tabVariacionesStock = document.getElementById('tab-variaciones-stock-nav');
    const tabEtiquetas = document.getElementById('tab-etiquetas-config');
    
    const panelLimitesStock = document.getElementById('panel-limites-stock');
    const panelMovimientosStock = document.getElementById('panel-movimientos-stock');
    const panelAtributos = document.getElementById('panel-atributos-config');
    const panelVariacionesStock = document.getElementById('panel-variaciones-stock');
    const panelEtiquetas = document.getElementById('panel-etiquetas-config');

    const mostrarStock = gestionaInventario;
    
    toggleElementVisibility(tabLimitesStock, mostrarStock);
    toggleElementVisibility(tabMovimientosStock, mostrarStock);
    toggleElementVisibility(panelLimitesStock, mostrarStock);
    toggleElementVisibility(panelMovimientosStock, mostrarStock);

    const mostrarAtributos = esVariable;
    toggleElementVisibility(tabAtributos, mostrarAtributos);
    toggleElementVisibility(panelAtributos, mostrarAtributos);

    toggleElementVisibility(tabVariacionesStock, mostrarAtributos);
    toggleElementVisibility(panelVariacionesStock, mostrarAtributos);

    toggleElementVisibility(tabEtiquetas, true);
    toggleElementVisibility(panelEtiquetas, true);

    if (!mostrarStock) {
        const panelAtributosEl = document.getElementById('panel-atributos-config');
        if (panelAtributosEl) {
            const existingMsg = panelAtributosEl.querySelector('.stock-disabled-message');
            if (existingMsg) existingMsg.remove();

            const msgDiv = document.createElement('div');
            msgDiv.className = 'alert alert-warning stock-disabled-message mt-3';
            msgDiv.innerHTML = `
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Inventario desactivado:</strong> Este producto no tiene activada la gestión de inventario. 
                Los tabs de "Límites de Stock", "Historial de Stock" y "Atributos" están ocultos.
                <br><br>
                <small>Para activar la gestión de inventario, edita el producto desde el formulario principal y marca la opción "Gestionar inventario".</small>
            `;
            panelAtributosEl.prepend(msgDiv);
        }
    }

    if (!esVariable) {
        const container = document.getElementById('config_atributos_container');
        if (container) {
            container.innerHTML = `
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Atributos solo para productos variables:</strong>
                    <br>Los atributos con valores extra (colores, imágenes, etc.) solo están disponibles para productos de tipo <strong>Variable</strong>.
                    <br><small>Cambia el tipo de producto a "Variable" en el formulario principal para gestionar atributos.</small>
                </div>
            `;
        }
    }

    const allTabs = document.querySelectorAll('#configTabs .nav-link:not(.d-none)');
    let firstVisibleTab = null;
    for (const tab of allTabs) {
        const style = window.getComputedStyle(tab);
        if (style.display !== 'none' && !tab.classList.contains('d-none')) {
            firstVisibleTab = tab;
            break;
        }
    }

    if (firstVisibleTab && !firstVisibleTab.classList.contains('active')) {
        const bsTab = new bootstrap.Tab(firstVisibleTab);
        bsTab.show();
    }
}

function toggleElementVisibility(element, show) {
    if (!element) return;
    if (show) {
        element.style.display = '';
        element.classList.remove('d-none');
    } else {
        element.style.display = 'none';
        element.classList.add('d-none');
    }
}

function renderEtiquetasConfig(etiquetas) {
    const container = document.getElementById('config_etiquetas_container');
    if (!container) return;

    if (!etiquetas || etiquetas.length === 0) {
        container.innerHTML = `
            <div class="text-muted text-center py-4">
                <i class="bi bi-tags fs-2 d-block mb-2"></i>
                <p>Este producto no tiene etiquetas asociadas.</p>
                <p class="small">Puedes agregar etiquetas desde el formulario principal del producto.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Etiqueta</th>
                        <th>Color actual</th>
                        <th>Nuevo color</th>
                        <th>Vista previa</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    ${etiquetas.map(etq => `
                        <tr data-etiqueta-id="${etq.id}">
                            <td class="fw-semibold">${escapeHtml(etq.nombre)}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:30px;height:30px;border-radius:50%;background:${etq.color};border:2px solid #ddd;"></div>
                                    <span class="badge" style="background:${etq.color};color:#fff;">${etq.color}</span>
                                </div>
                            </td>
                            <td>
                                <input type="color" class="form-control form-control-sm etiqueta-color-input" 
                                       data-etiqueta-id="${etq.id}" 
                                       value="${etq.color}" 
                                       style="width:60px;padding:2px;cursor:pointer;">
                            </td>
                            <td>
                                <span class="badge etiqueta-preview" style="background:${etq.color};color:#fff;font-size:1rem;padding:8px 16px;">
                                    ${escapeHtml(etq.nombre)}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary btn-actualizar-color-etiqueta" 
                                        data-etiqueta-id="${etq.id}" 
                                        data-color="${etq.color}">
                                    <i class="bi bi-check2-circle"></i> Actualizar
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    document.querySelectorAll('.etiqueta-color-input').forEach(input => {
        input.addEventListener('input', function() {
            const etiquetaId = this.dataset.etiquetaId;
            const nuevoColor = this.value;
            
            const preview = document.querySelector(`.etiqueta-preview[data-etiqueta-id="${etiquetaId}"]`);
            if (preview) {
                preview.style.background = nuevoColor;
            }
            
            const colorBadge = document.querySelector(`tr[data-etiqueta-id="${etiquetaId}"] .badge:not(.etiqueta-preview)`);
            if (colorBadge) {
                colorBadge.style.background = nuevoColor;
                colorBadge.textContent = nuevoColor;
            }
            
            const colorCircle = document.querySelector(`tr[data-etiqueta-id="${etiquetaId}"] div[style*="border-radius:50%"]`);
            if (colorCircle) {
                colorCircle.style.background = nuevoColor;
            }

            const btn = document.querySelector(`.btn-actualizar-color-etiqueta[data-etiqueta-id="${etiquetaId}"]`);
            if (btn) {
                btn.dataset.color = nuevoColor;
            }
        });
    });

    document.querySelectorAll('.btn-actualizar-color-etiqueta').forEach(btn => {
        btn.addEventListener('click', async function() {
            const etiquetaId = this.dataset.etiquetaId;
            const color = this.dataset.color;
            const productoId = configState.productoId;

            if (!color || color === '#000000') {
                Swal.fire('Advertencia', 'El color no puede ser negro (#000000). Elige otro color.', 'warning');
                return;
            }

            try {
                const { data } = await axios.post('productos', {
                    opcion: 'ActualizarColorEtiqueta',
                    producto_id: productoId,
                    etiqueta_id: etiquetaId,
                    color: color
                });

                if (data.respuesta === 'ok') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Color actualizado',
                        text: 'El color de la etiqueta se ha actualizado correctamente.',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    const row = document.querySelector(`tr[data-etiqueta-id="${etiquetaId}"]`);
                    if (row) {
                        const badge = row.querySelector('.badge:not(.etiqueta-preview)');
                        if (badge) {
                            badge.style.background = color;
                            badge.textContent = color;
                        }
                        
                        const circle = row.querySelector('div[style*="border-radius:50%"]');
                        if (circle) {
                            circle.style.background = color;
                        }
                        
                        const preview = row.querySelector('.etiqueta-preview');
                        if (preview) {
                            preview.style.background = color;
                        }
                        
                        const input = row.querySelector('.etiqueta-color-input');
                        if (input) {
                            input.value = color;
                        }
                    }
                    cargarConfiguracionProducto(productoId);

                } else {
                    Swal.fire('Error', data.mensaje || 'No se pudo actualizar el color', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            }
        });
    });
}

function renderHistorialStock(historial) {
    const tbody = document.getElementById('config_historial_table_body');
    if (!tbody) return;

    if (!historial || historial.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay movimientos registrados</td></tr>';
        return;
    }

    tbody.innerHTML = historial.map(item => {
        let nombreUsuario = 'Sistema';
        if (item.usuario) {
            nombreUsuario = `${item.usuario.nombres || ''} ${item.usuario.apellidos || ''}`.trim() || 'Usuario';
        }
        
        return `
            <tr>
                <td>${new Date(item.created_at).toLocaleString()}</td>
                <td>${item.cantidad_anterior}</td>
                <td>${item.nueva_cantidad}</td>
                <td class="${item.diferencia > 0 ? 'text-success' : 'text-danger'}">
                    ${item.diferencia > 0 ? '+' : ''}${item.diferencia}
                </td>
                <td>${item.motivo || 'Sin motivo'}</td>
                <td>${nombreUsuario}</td>
            </tr>
        `;
    }).join('');
}

function renderAtributosConfig(atributos) {
    const container = document.getElementById('config_atributos_container');
    if (!container) return;

    if (!atributos || atributos.length === 0) {
        container.innerHTML = '<p class="text-muted">Este producto no tiene atributos.</p>';
        return;
    }

    // Ordenar atributos por prioridad (si existe)
    const atributosOrdenados = [...atributos].sort((a, b) => {
        const prioridadA = a.prioridad || 999;
        const prioridadB = b.prioridad || 999;
        return prioridadA - prioridadB;
    });

    container.innerHTML = atributosOrdenados.map((attr, index) => {
        const tipoActual = attr.tipo || 'Default';
        const shapeActual = attr.shape || 'Default';
        const prioridadActual = attr.prioridad || index + 1;

        const terminosHtml = (attr.terminos || []).map(term => {
            let inputExtra = '';
            
            if (tipoActual === 'Color') {
                inputExtra = `
                    <input type="color" class="form-control form-control-sm valor-extra-input" 
                           data-attr-id="${attr.id}" data-term-id="${term.id}" 
                           value="${term.valor_extra || '#000000'}" style="width: 50px; padding: 0;">
                `;
            } else if (tipoActual === 'Image') {
                const imagenActual = term.valor_extra || '';
                const tieneImagen = imagenActual && imagenActual.length > 0;
                
                inputExtra = `
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="file" class="form-control form-control-sm valor-extra-input-file" 
                               data-attr-id="${attr.id}" data-term-id="${term.id}" 
                               accept=".jpg,.png,.ico" style="width: 200px;">
                        ${tieneImagen ? `
                            <div class="d-flex align-items-center gap-2">
                                <img src="/${imagenActual}" 
                                     style="max-height: 40px; max-width: 40px; border-radius: 4px; border: 1px solid #ddd; object-fit: cover;">
                                <a href="/${imagenActual}" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-imagen-termino" 
                                        data-attr-id="${attr.id}" data-term-id="${term.id}" 
                                        data-imagen="${imagenActual}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        ` : ''}
                    </div>
                    <div class="file-preview-${attr.id}-${term.id} mt-1"></div>
                `;
            } else {
                inputExtra = `<span class="text-muted small">Sin valor extra</span>`;
            }

            return `
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-secondary" style="min-width: 80px;">${term.nombre}</span>
                    ${inputExtra}
                </div>
            `;
        }).join('');

        return `
            <div class="card mb-3 atributo-config-card" data-attr-id="${attr.id}" data-prioridad="${prioridadActual}">
                <div class="card-header d-flex justify-content-between align-items-center text-white">
                    <div class="d-flex align-items-center gap-2">
                        <span class="grip-handle" style="cursor: grab; color: white;">
                            <i class="bi bi-grip-vertical"></i>
                        </span>
                        <span class="orden-badge" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #f35b08; color: white; font-weight: bold; font-size: 0.8rem;">
                            ${prioridadActual}
                        </span>
                        <strong>${attr.nombre}</strong>
                        <span class="badge bg-info">ID: ${attr.id}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <select class="form-select form-select-sm attr-tipo-select" data-attr-id="${attr.id}">
                                <option value="Default" ${tipoActual === 'Default' ? 'selected' : ''}>Default</option>
                                <option value="Label" ${tipoActual === 'Label' ? 'selected' : ''}>Label</option>
                                <option value="Color" ${tipoActual === 'Color' ? 'selected' : ''}>Color</option>
                                <option value="Image" ${tipoActual === 'Image' ? 'selected' : ''}>Image</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shape</label>
                            <select class="form-select form-select-sm attr-shape-select" data-attr-id="${attr.id}">
                                <option value="Default" ${shapeActual === 'Default' ? 'selected' : ''}>Default</option>
                                <option value="Square" ${shapeActual === 'Square' ? 'selected' : ''}>Square</option>
                                <option value="Rounded Corner" ${shapeActual === 'Rounded Corner' ? 'selected' : ''}>Rounded Corner</option>
                                <option value="Circle" ${shapeActual === 'Circle' ? 'selected' : ''}>Circle</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <label class="form-label">Valores Extra por Término</label>
                    ${terminosHtml}
                </div>
            </div>
        `;
    }).join('');

    // Inicializar SortableJS para drag & drop
    initSortableAtributos();

    // Event listeners para cambios de tipo y shape
    document.querySelectorAll('.attr-tipo-select').forEach(select => {
        select.addEventListener('change', function() {
            const attrId = this.dataset.attrId;
            const nuevoTipo = this.value;
            const attrData = configState.atributos.find(a => String(a.id) === String(attrId));
            if (attrData) {
                attrData.tipo = nuevoTipo;
                renderAtributosConfig(configState.atributos);
            }
        });
    });

    document.querySelectorAll('.attr-shape-select').forEach(select => {
        select.addEventListener('change', function() {
            const attrId = this.dataset.attrId;
            const nuevoShape = this.value;
            const attrData = configState.atributos.find(a => String(a.id) === String(attrId));
            if (attrData) {
                attrData.shape = nuevoShape;
            }
        });
    });

    // Event listeners para inputs de archivos (imágenes)
    document.querySelectorAll('.valor-extra-input-file').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const attrId = this.dataset.attrId;
            const termId = this.dataset.termId;
            const previewContainer = document.querySelector(`.file-preview-${attrId}-${termId}`);
            if (!previewContainer) return;

            const validExtensions = ['.jpg', '.jpeg', '.png', '.ico'];
            const fileExt = '.' + file.name.split('.').pop().toLowerCase();
            if (!validExtensions.includes(fileExt)) {
                Swal.fire('Error', 'Solo se permiten archivos .jpg, .png o .ico', 'error');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                previewContainer.innerHTML = `
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <img src="${event.target.result}" style="max-height: 50px; max-width: 50px; border-radius: 4px; border: 1px solid #ddd; object-fit: cover;">
                        <span class="small text-success">${file.name}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-cancelar-archivo" 
                                data-attr-id="${attrId}" data-term-id="${termId}">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                `;

                if (!configState.imagenesSubidas[attrId]) {
                    configState.imagenesSubidas[attrId] = {};
                }
                configState.imagenesSubidas[attrId][termId] = file;
                
                previewContainer.querySelector('.btn-cancelar-archivo')?.addEventListener('click', function() {
                    delete configState.imagenesSubidas[attrId]?.[termId];
                    const input = document.querySelector(`.valor-extra-input-file[data-attr-id="${attrId}"][data-term-id="${termId}"]`);
                    if (input) input.value = '';
                    previewContainer.innerHTML = '';
                });
            };
            reader.readAsDataURL(file);
        });
    });

    // Event listeners para eliminar imágenes de términos
    document.querySelectorAll('.btn-remove-imagen-termino').forEach(btn => {
        btn.addEventListener('click', function() {
            const attrId = this.dataset.attrId;
            const termId = this.dataset.termId;
            const imagenPath = this.dataset.imagen;

            Swal.fire({
                title: '¿Eliminar imagen?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const { data } = await axios.post('productos', {
                            opcion: 'EliminarImagenTermino',
                            producto_id: configState.productoId,
                            atributo_id: attrId,
                            termino_id: termId,
                            imagen_path: imagenPath
                        });

                        if (data.respuesta === 'ok') {
                            Swal.fire('Eliminada', 'La imagen ha sido eliminada', 'success');
                            cargarConfiguracionProducto(configState.productoId);
                        } else {
                            Swal.fire('Error', data.mensaje || 'No se pudo eliminar la imagen', 'error');
                        }
                    } catch (error) {
                        Swal.fire('Error', 'No se pudo eliminar la imagen', 'error');
                    }
                }
            });
        });
    });
}

/**
 * Inicializa SortableJS para ordenar atributos por arrastre
 */
function initSortableAtributos() {
    const container = document.getElementById('config_atributos_container');
    if (!container) return;
    initSortableInstance(container);
}

function initSortableInstance(container) {
    // Destruir instancia anterior si existe
    if (container._sortableInstance) {
        container._sortableInstance.destroy();
    }

    const sortable = new Sortable(container, {
        handle: '.grip-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            // Obtener el nuevo orden de los atributos
            const cards = container.querySelectorAll('.atributo-config-card');
            const nuevosAtributos = [];
            const prioridadesActualizadas = [];

            cards.forEach((card, index) => {
                const attrId = card.dataset.attrId;
                const nuevaPrioridad = index + 1;
                prioridadesActualizadas.push({
                    atributo_id: attrId,
                    prioridad: nuevaPrioridad
                });

                // Actualizar el badge de orden
                const badge = card.querySelector('.orden-badge');
                if (badge) {
                    badge.textContent = nuevaPrioridad;
                }
                card.dataset.prioridad = nuevaPrioridad;

                // Actualizar en configState
                const attrData = configState.atributos.find(a => String(a.id) === String(attrId));
                if (attrData) {
                    attrData.prioridad = nuevaPrioridad;
                    nuevosAtributos.push(attrData);
                }
            });

            // Guardar la nueva prioridad en el servidor
            guardarPrioridadAtributos(prioridadesActualizadas);
        }
    });

    // Guardar la instancia para poder destruirla después
    container._sortableInstance = sortable;
}

/**
 * Guarda las prioridades de los atributos en el servidor
 */
async function guardarPrioridadAtributos(prioridades) {
    try {
        const productoId = configState.productoId;
        
        const { data } = await axios.post('productos', {
            opcion: 'GuardarPrioridadAtributos',
            producto_id: productoId,
            prioridades: prioridades
        });

        if (data.respuesta === 'ok') {
            // Mostrar notificación sutil
            showToast('Orden de atributos actualizado', 'success');
        } else {
            console.error('Error al guardar prioridades:', data.mensaje);
            showToast('Error al guardar el orden', 'error');
        }
    } catch (error) {
        console.error('Error al guardar prioridades:', error);
        showToast('Error al guardar el orden', 'error');
    }
}

/**
 * Muestra un toast/notificación simple
 */
function showToast(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'} border-0`;
    toast.role = 'alert';
    toast.ariaLive = 'assertive';
    toast.ariaAtomic = 'true';
    toast.style.position = 'fixed';
    toast.style.bottom = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '250px';
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

window.abrirConfiguracion = function(productoId) {
    document.getElementById('config_producto_id').value = productoId;
    const modalConfig = new bootstrap.Modal(document.getElementById('modalConfiguracionProducto'));
    modalConfig.show();
};

document.getElementById('modalConfiguracionProducto')?.addEventListener('show.bs.modal', async function (event) {
    const productoId = document.getElementById('config_producto_id').value;
    if (productoId) {
      
        const preloaderMessages = showPreloader("Cargando configuración avanzada...", "cargar");
        
        try {
            const { data: tipoData } = await axios.post('productos', {
                opcion: 'ObtenerTipoProducto',
                id: productoId
            });
            
            if (tipoData.respuesta === 'ok') {
                configState.tipoProducto = tipoData.tipo_producto;
            }
            
            await cargarConfiguracionProducto(productoId);
            
            Swal.close();
            
        } catch (error) {
            Swal.close();
            Swal.fire('Error', 'No se pudo cargar la configuración del producto', 'error');
        }
    } else {
        Swal.fire('Advertencia', 'No se encontró el ID del producto.', 'warning');
    }
});

document.getElementById('btnGuardarLimitesStock')?.addEventListener('click', async function() {
    const productoId = configState.productoId;
    const stockMinimo = document.getElementById('config_stock_minimo').value;
    const maxStock = document.getElementById('config_max_stock').value;

    try {
        const { data } = await axios.post('productos', {
            opcion: 'GuardarLimitesStock',
            id: productoId,
            stock_minimo: stockMinimo,
            max_stock: maxStock
        });

        if (data.respuesta === 'ok') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.mensaje,
                timer: 1500,
                showConfirmButton: false
            });
            cargarConfiguracionProducto(productoId);
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo guardar los límites de stock', 'error');
    }
});

document.getElementById('btnAddStock')?.addEventListener('click', async function() {
    const productoId = configState.productoId;
    const cantidad = document.getElementById('config_add_stock').value;
    const motivo = document.getElementById('config_motivo_movimiento').value;

    if (!cantidad || parseInt(cantidad) <= 0) {
        Swal.fire('Advertencia', 'Ingresa una cantidad válida para añadir.', 'warning');
        return;
    }

    try {
        const { data: stockData } = await axios.post('productos', {
            opcion: 'ObtenerStockYLimites',
            id: productoId
        });

        if (stockData.respuesta === 'ok') {
            const stockActual = stockData.stock || 0;
            const maxStock = stockData.max_stock;
            
            if (maxStock !== null && maxStock > 0) {
                const nuevoStock = stockActual + parseInt(cantidad);
                if (nuevoStock > maxStock) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Límite de stock excedido',
                        html: `
                            <p>No puedes añadir ${cantidad} unidades porque superarías el stock máximo.</p>
                            <p><strong>Stock actual:</strong> ${stockActual}</p>
                            <p><strong>Stock máximo:</strong> ${maxStock}</p>
                            <p><strong>Intentas añadir:</strong> ${cantidad}</p>
                            <p><strong>Nuevo stock:</strong> ${nuevoStock} (excede el máximo)</p>
                            <p class="text-danger">Puedes añadir como máximo ${maxStock - stockActual} unidades.</p>
                        `,
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
            }
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error al validar stock',
            text: 'No se pudo verificar los límites de stock. Intenta nuevamente.',
            confirmButtonText: 'Reintentar'
        });
    }

    try {
        const { data } = await axios.post('productos', {
            opcion: 'RegistrarMovimientoStock',
            id: productoId,
            cantidad: parseInt(cantidad),
            motivo: motivo,
            tipo_movimiento: 'add'
        });

        if (data.respuesta === 'ok') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.mensaje,
                timer: 1500,
                showConfirmButton: false
            });
            document.getElementById('config_add_stock').value = '';
            document.getElementById('config_motivo_movimiento').value = '';
            cargarConfiguracionProducto(productoId);
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo registrar el movimiento', 'error');
    }
});

document.getElementById('btnRemoveStock')?.addEventListener('click', async function() {
    const productoId = configState.productoId;
    const cantidad = document.getElementById('config_remove_stock').value;
    const motivo = document.getElementById('config_motivo_movimiento').value;

    if (!cantidad || parseInt(cantidad) <= 0) {
        Swal.fire('Advertencia', 'Ingresa una cantidad válida para quitar.', 'warning');
        return;
    }

    try {
        const { data } = await axios.post('productos', {
            opcion: 'RegistrarMovimientoStock',
            id: productoId,
            cantidad: parseInt(cantidad),
            motivo: motivo,
            tipo_movimiento: 'remove'
        });

        if (data.respuesta === 'ok') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.mensaje,
                timer: 1500,
                showConfirmButton: false
            });
            document.getElementById('config_remove_stock').value = '';
            document.getElementById('config_motivo_movimiento').value = '';
            cargarConfiguracionProducto(productoId);
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo registrar el movimiento', 'error');
    }
});

document.getElementById('btnGuardarAtributosConfig')?.addEventListener('click', async function() {
    const productoId = configState.productoId;
    const atributosData = [];
    
    document.querySelectorAll('.card.mb-3').forEach(card => {
        const attrId = card.dataset.attrId;
        if (!attrId) return;

        const tipo = card.querySelector('.attr-tipo-select')?.value || 'Default';
        const shape = card.querySelector('.attr-shape-select')?.value || 'Default';
        
        const prioridad = parseInt(card.dataset.prioridad) || parseInt(card.querySelector('.orden-badge')?.textContent) || 999;

        const terminos = [];
        card.querySelectorAll('.valor-extra-input, .valor-extra-input-file').forEach(input => {
            const termId = input.dataset.termId;
            let valorExtra = '';
            
            if (input.type === 'color') {
                valorExtra = input.value || '#000000';
            } else if (input.type === 'file') {
                const file = configState.imagenesSubidas[attrId]?.[termId];
                if (file) {
                    valorExtra = file;
                } else {
                    valorExtra = null;
                }
            }

            terminos.push({
                id: termId,
                valor_extra: valorExtra,
                es_archivo: input.type === 'file' && !!configState.imagenesSubidas[attrId]?.[termId]
            });
        });

        atributosData.push({
            id: attrId,
            tipo: tipo,
            shape: shape,
            prioridad: prioridad, 
            terminos: terminos
        });
    });

    const formData = new FormData();
    formData.append('opcion', 'GuardarAtributosConfig');
    formData.append('id', productoId);
    formData.append('atributos', JSON.stringify(atributosData.map(attr => ({
        ...attr,
        terminos: attr.terminos.map(t => ({
            id: t.id,
            valor_extra: t.es_archivo ? '__FILE__' : t.valor_extra
        }))
    }))));

    Object.keys(configState.imagenesSubidas).forEach(attrId => {
        Object.keys(configState.imagenesSubidas[attrId]).forEach(termId => {
            const file = configState.imagenesSubidas[attrId][termId];
            if (file instanceof File) {
                formData.append(`imagen_${attrId}_${termId}`, file);
            }
        });
    });

    try {
        const { data } = await axios.post('productos', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (data.respuesta === 'ok') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.mensaje,
                timer: 1500,
                showConfirmButton: false
            });
            configState.imagenesSubidas = {};
            cargarConfiguracionProducto(productoId);
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo guardar la configuración de atributos', 'error');
    }
});

$(document).ready(function () {
  $('#tablaProductos').DataTable({
    ajax: {
      url: 'productos',
      type: 'POST',
      data: { opcion: 'Listar', _token: $('meta[name="csrf-token"]').attr('content') },
      dataSrc: function (json) {
        if (json.respuesta === 'ok') return json.productos;
        Swal.fire('Error', json.mensaje, 'error');
        return [];
      }
    },
    columns: [
      { data: null, render: (data) => `<img src="${data.imagen || '/img/default.png'}" width="50" class="rounded">` },
      { data: 'id' },
      { data: 'nombre' },
      { data: 'descripcion' },
      { data: 'precio', render: (data) => data },  
      { data: 'inventario' },
      { data: 'marca' },
      { data: 'tipo_producto' },
      { data: 'subcategoria' },
      { data: 'etiquetas', render: (etqs) => etqs.map(e => 
          `<span class="badge me-1" style="background:${e.color}">${e.nombre}</span>`
        ).join(' ') 
      },
      { data: 'created_at' },
      {
        data: null,
        className: 'text-center',
        render: (data) => `
          <button class="btn btn-warning btn-sm me-1" onclick="obtenerProducto(${data.id})">
            <i class="bi bi-pencil-square"></i>
          </button>
          <button class="btn btn-info btn-sm me-1" onclick="abrirConfiguracion(${data.id})" title="Configuración Avanzada">
            <i class="bi bi-gear"></i>
          </button>
          <button class="btn btn-danger btn-sm" onclick="eliminarProducto(${data.id})">
            <i class="bi bi-trash"></i>
          </button>
        `
      }
    ]
  });
});

function eliminarProducto(id) {
  Swal.fire({
    title: '¿Eliminar producto?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: 'productos',
        type: 'POST',
        data: { opcion: 'Eliminar', id, _token: $('meta[name="csrf-token"]').attr('content') },
        success: function (res) {
          if (res.respuesta === 'ok') {
            Swal.fire('Eliminado', res.mensaje, 'success');
            $('#tablaProductos').DataTable().ajax.reload();
          } else {
            Swal.fire('Error', res.mensaje, 'error');
          }
        },
        error: function () {
          Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
      });
    }
  });
}
const editState = {
  productoActual: null,
  variaciones: [],
  relacionados: [],
  agrupados: [],
  imagenes: [],
  imagenesNuevas: [],
  upsells: [],
  crosssells: [],
  variacionesPage: 1,
  variacionesPerPage: 10
};

async function obtenerProducto(id) {
    try {
        Swal.fire({
            title: 'Cargando producto...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const { data } = await axios.post('productos', {
            opcion: 'Obtener',
            id
        });

        Swal.close();

        if (data.respuesta !== 'ok') {
            Swal.fire('Error', data.mensaje || 'No se pudo obtener el producto', 'error');
            return;
        }

        const producto = data.producto;

        editState.productoActual = producto;
        editState.imagenes = producto.imagenes || [];
        editState.imagenesNuevas = [];
        editState.imagenesAEliminar = [];
        editState.upsells = [];
        editState.crosssells = [];

        if (producto.productos_relacionados && producto.productos_relacionados.length > 0) {
            producto.productos_relacionados.forEach(rel => {
                if (rel.pivot && rel.pivot.tipo === 'upsell') {
                    editState.upsells.push({
                        id: rel.id,
                        nombre: rel.nombre || 'Producto sin nombre',
                        sku: rel.sku || 'N/A'
                    });
                } else if (rel.pivot && rel.pivot.tipo === 'crosssell') {
                    editState.crosssells.push({
                        id: rel.id,
                        nombre: rel.nombre || 'Producto sin nombre',
                        sku: rel.sku || 'N/A'
                    });
                }
            });
        }

        switch (producto.tipo_producto) {
            case 'simple':
                abrirModalSimple(producto);
                break;
            case 'variable':
                abrirModalVariable(producto);
                break;
            case 'agrupado':
                abrirModalAgrupado(producto);
                break;
            default:
                Swal.fire('Error', 'Tipo de producto no soportado', 'error');
        }

    } catch (err) {
        Swal.close();
        Swal.fire('Error', 'Error al obtener el producto: ' + (err.response?.data?.mensaje || err.message), 'error');
    }
}

function escapeHtml(unsafe) {
  if (typeof unsafe !== "string") return "";
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}