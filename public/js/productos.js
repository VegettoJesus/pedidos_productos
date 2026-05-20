(function(){
  
  // ---- Utilities ----
  const qs = (selector, scope = document) => scope.querySelector(selector);
  const qsa = (selector, scope = document) => scope.querySelectorAll(selector);
  
  const escapeHtml = (s = '') => (s+'').replace(/&/g,'&amp;')
                                       .replace(/</g,'&lt;')
                                       .replace(/"/g,'&quot;')
                                       .replace(/'/g,'&#039;');

  // ---- Configs/Globals ----
  const MAX_IMAGES = 6;
  const MAX_SIZE_BYTES = 4 * 1024 * 1024;
  
  // Data from server
  const CATEGORIAS = window._CATEGORIAS || [];
  const ETIQUETAS = window._ETIQUETAS || [];
  const ATRIBUTOS = window._ATRIBUTOS || [];

  // State management
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
    relacionados: []
  };

  function initTipoProductoSystem(){
    const sel = qs('#tipoProductoSelect');
    if(!sel) return;

    // run una vez al inicio para aplicar UI por defecto
    applyTipoUI(sel.value || sel.options[sel.selectedIndex].value);

    sel.addEventListener('change', (e) => {
      const nuevoTipo = e.target.value;
      // limpiar TODO lo dependiente del tipo
      clearOnTipoChange(nuevoTipo);
      // aplicar UI acorde al tipo
      applyTipoUI(nuevoTipo);
    });
  }

  function clearOnTipoChange(nuevoTipo){
    const tipoSelect = qs('#tipoProductoSelect');
    const keepTipo = nuevoTipo;
    if(tipoSelect) tipoSelect.value = keepTipo;

    state.productoAtributos = [];
    state.variaciones = [];
    renderAtributoBlocks();
    renderVariacionesUI(); 
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
      showInventoryMode('simple');
      renderRelacionadosDefault();

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

      toggleAtributoVariacionCheckbox(false);

      showTab('#tab-general');

    } else if(tipo === 'variable'){
      toggleNavByTarget('#tab-general', false);
      togglePane('#tab-general', false);

      toggleNavByTarget('#tab-inventario', true);
      togglePane('#tab-inventario', true);
      showInventoryMode('variable');
      renderRelacionadosDefault();

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

      toggleAtributoVariacionCheckbox(true);

      showTab('#tab-variaciones');

      renderVariacionesUI();

    } else if(tipo === 'agrupado'){
      toggleNavByTarget('#tab-general', false);
      togglePane('#tab-general', false);

      toggleNavByTarget('#tab-inventario', true);
      togglePane('#tab-inventario', true);
      showInventoryMode('agrupado');
      renderRelacionadosAgrupado();
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

      toggleAtributoVariacionCheckbox(false);

      showFirstVisibleTab(navContainer);
    }
  }

  // ---- Helpers adicionales ----
  function showInventoryMode(mode){
    const invPane = qs('#tab-inventario');
    if(!invPane) return;

    qsa('#tab-inventario .mb-3, #tab-inventario .form-check').forEach(el => {
      const hasSku = el.querySelector('[name="sku"]');
      const hasGestion = el.querySelector('[name="gestion_inventario"]');
      const hasLimit = el.querySelector('[name="vendido_individualmente"]');
      const hasStock = el.querySelector('[name="stock"]');

      if(mode === 'simple'){
        // Mostrar todo
        el.classList.remove('d-none');

      } else if(mode === 'variable'){
        // Solo SKU, gestionar inventario y limitar compras
        if (hasSku || hasGestion || hasLimit || hasStock) {
          el.classList.remove('d-none');
        } else {
          el.classList.add('d-none');
        }

      } else if(mode === 'agrupado'){
        // Solo SKU
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
    relPane.innerHTML = `
      <div class="mb-3">
        <label class="form-label">Productos relacionados</label>
        <input type="text" id="inputRelacionados" class="form-control" placeholder="Buscar producto...">
      </div>
      <div class="mb-3">
        <label class="form-label">Ventas cruzadas (Cross-sells)</label>
        <input type="text" id="inputCrosssells" class="form-control" placeholder="Buscar producto...">
      </div>
    `;
    setupProductSearch('#inputRelacionados', 'relacionados');
    setupProductSearch('#inputCrosssells', 'crosssells');
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
          <div class="d-flex gap-2 mb-3">
            <button type="button" id="btnGenerateVariations" class="btn btn-outline-primary btn-sm"><i class="bi bi-gear-wide-connected me-1"></i> Generar variaciones</button>
            <button type="button" id="btnGenerateManualVariation" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-square me-1"></i> Generar manual</button>
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
      });
    }
  }

  function toggleAtributoVariacionCheckbox(show){
    qsa('.atributo-block').forEach(block => {
      const chk = block.querySelector('.chk-variacion');
      if(chk) {
        chk.closest('.form-check')?.classList.toggle('d-none', !show);
        // si ocultamos, desmarcar
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

  // ---- Variaciones: generación y UI ----
  function renderVariacionesUI(){
    const container = qs('#variationsContainer');
    if(!container) return;
    container.innerHTML = '';
    state.variaciones.forEach((v, idx) => {
      container.appendChild(createVariationRow(v, idx));
    });
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
          <div class="row g-2 mb-3">
            <div class="col-md-4">
              <label class="form-label small mb-1">Cantidad</label>
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

    // Eliminar variación
    wrapper.querySelector('.btn-remove-variation').addEventListener('click', () => {
      state.variaciones.splice(idx, 1);
      renderVariacionesUI();
    });

    // ===== Guardar cambios en todos los campos =====
    const variacionActual = state.variaciones[idx];

    // Atributos
    wrapper.querySelectorAll('.variation-attr').forEach(sel => {
      sel.addEventListener('change', () => {
        const atrId = sel.dataset.atrId;
        const termId = sel.value;
        if (!variacionActual.atributos) variacionActual.atributos = [];
        
        const pos = variacionActual.atributos.findIndex(a => String(a.atrId) === String(atrId));
        if (pos >= 0) {
          if (termId) {
            // Si es "0" guardamos null (Cualquier valor), si no guardamos el ID
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

    // SKU, stock, precios
    wrapper.querySelector('.variation-sku').addEventListener('input', e => variacionActual.sku = e.target.value);
    wrapper.querySelector('.variation-stock').addEventListener('input', e => variacionActual.stock = e.target.value);
    wrapper.querySelector('.variation-price-normal').addEventListener('input', e => variacionActual.price_normal = e.target.value);
    wrapper.querySelector('.variation-price-sale').addEventListener('input', e => variacionActual.price_sale = e.target.value);

    // Reprogramar precios
    const scheduleCheckbox = wrapper.querySelector('.schedule-sale');
    const datesDiv = wrapper.querySelector('.schedule-dates');
    scheduleCheckbox.addEventListener('change', () => {
      datesDiv.classList.toggle('d-none', !scheduleCheckbox.checked);
    });
    wrapper.querySelector('.variation-sale-start').addEventListener('input', e => variacionActual.sale_start = e.target.value);
    wrapper.querySelector('.variation-sale-end').addEventListener('input', e => variacionActual.sale_end = e.target.value);

    // Peso, tipo, dimensiones
    wrapper.querySelector('.variation-weight').addEventListener('input', e => variacionActual.weight = e.target.value);
    wrapper.querySelector('.variation-weight-type').addEventListener('change', e => variacionActual.weight_type = e.target.value);
    wrapper.querySelector('.variation-length').addEventListener('input', e => variacionActual.length = e.target.value);
    wrapper.querySelector('.variation-width').addEventListener('input', e => variacionActual.width = e.target.value);
    wrapper.querySelector('.variation-height').addEventListener('input', e => variacionActual.height = e.target.value);

    // Descripción
    wrapper.querySelector('.variation-description').addEventListener('input', e => variacionActual.description = e.target.value);

    // Backorder
    wrapper.querySelectorAll('.allow-backorder').forEach(radio => {
      radio.addEventListener('change', () => variacionActual.backorder = radio.value);
    });

    // ===== Imágenes =====
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

  // ===== Generar variaciones automáticamente desde atributos =====
  function generateVariationsFromAtributos() {
    const attrs = state.productoAtributos.filter(a => a.variacion && a.valores && a.valores.length);
    if (attrs.length < 1) {
      showAlert('warning', 'No hay atributos para variaciones', 'Marca como "Variación" al menos un atributo con valores.');
      return;
    }

    // Crear arrays de valores por atributo, INCLUYENDO la opción "Cualquier"
    const arrays = attrs.map(a => {
      // Primero agregamos la opción "Cualquier" como null
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

    // Función cartesiana
    function cartesian(arr) {
      return arr.reduce((a, b) => a.flatMap(d => b.map(e => d.concat([e]))), [[]]);
    }

    const combos = cartesian(arrays);

    // Guardar variaciones en state con todos los campos inicializados
    state.variaciones = combos.map(combo => ({
        atributos: combo.map(c => ({ atrId: c.atrId, termId: c.termId })),
        sku: '',
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

  // ===== Agregar fila manual de variación =====
  function addManualVariationRow() {
    const activos = state.productoAtributos.filter(a => a.variacion && a.valores.length);
    if (activos.length === 0) {
      showAlert('warning', 'No se puede generar', 'Debes activar al menos un atributo como variación con valores.');
      return;
    }

    state.variaciones.push({
      atributos: activos.map(a => ({ 
        atrId: a.atributo.id, 
        termId: null // null = "Cualquier valor"
      })),
      sku: '',
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
          console.error(err);
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

  // ---- Modal Management ----
  function initModals() {
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está cargado');
        return;
    }

    state.modals = {
        producto: new bootstrap.Modal(qs('#modalProducto')),
        crearAtributo: new bootstrap.Modal(qs('#modalCrearAtributo')),
        crearValor: new bootstrap.Modal(qs('#modalCrearValor'))
    };
    const modalElement = qs('#modalProducto');
    if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', function () {
            if (typeof tinymce !== 'undefined' && !tinymce.get('descripcionLarga')) {
                tinymce.init({
                    selector: '#descripcionLarga',
                    language: 'es_MX',
                    height: 400,
                    menubar: false,
                    license_key: 'gpl',
                    base_url: '/assets/tinymce',  
                    suffix: '.min',
                    plugins: [
                        'lists', 'link', 'autolink', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code',
                        'fullscreen', 'insertdatetime', 'media', 'table',
                        'wordcount'
                    ],
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
                    convert_urls: false,
                    remove_script_host: false,
                    paste_data_images: false
                });
            }
        });
    }

    setupModalNavigation();
  }

  function setupModalNavigation() {
    document.addEventListener('click', (e) => {
      if (e.target && e.target.id === 'btnOpenCrearAtributo') {
        state.modals.producto.hide();
        setTimeout(() => {
          state.modals.crearAtributo.show();
        }, 300);
      }
    });

    document.addEventListener('click', (e) => {
      if (e.target && e.target.classList.contains('btnCrearValor')) {
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
      state.modals.crearAtributo.hide();
      setTimeout(() => {
        state.modals.producto.show();
      }, 300);
    });

    document.addEventListener('valorCreado', () => {
      state.modals.crearValor.hide();
      setTimeout(() => {
        state.modals.producto.show();
      }, 300);
    });
  }

  // ---- Atributos System ----
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
        showAlert('success', 'Atributo creado', '', 1200, false);

        form.reset();
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearAtributo'));
        modal.hide();

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

        const attr = ATRIBUTOS.find(a => String(a.id) === String(atributoId));
        if (attr) {
          attr.terminos = attr.terminos || [];
          attr.terminos.push(data.termino);
        }

        const productoAttr = state.productoAtributos.find(x => String(x.atributo.id) === String(atributoId));
        if (productoAttr) {
          productoAttr.valores.push({
            id: data.termino.id,
            nombre: data.termino.nombre,
            slug: data.termino.slug
          });
        }

        renderAtributoBlocks();
        document.dispatchEvent(new CustomEvent('valorCreado'));
        showAlert('success', 'Valor creado', '', 1200, false);

        form.reset();
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearValor'));
        modal.hide();

      } else {
        showAlert('error', 'Error', data.mensaje);
      }
    } catch (error) {
      showAlert('error', 'Error servidor', 'Error al crear el valor');
    } finally {
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }
  }

  // ---- Categorías System ----
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

  // ---- Etiquetas System ----
  function initEtiquetasSystem() {
    renderAvailableTags();
    renderSelectedTags();
    
    qs('#btnAddTag')?.addEventListener('click', handleAddTag);
    qs('#availableTags')?.addEventListener('change', handleTagCheckboxChange);
  }

  function renderAvailableTags() {
    const container = qs('#availableTags');
    if (!container) return;
    
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
               data-id="${tag.id}" data-name="${tag.nombre}">
        <label class="form-check-label" for="${id}">${tag.nombre}</label>
      `;
      container.appendChild(wrapper);
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

        renderAvailableTags();

        const newCheckbox = document.querySelector(`input.tag-available[data-id="${nueva.id}"]`);
        if (newCheckbox) {
          newCheckbox.checked = true;
        }

        state.selectedTags.set(nueva.slug, { 
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
    if (!e.target.classList.contains('tag-available')) return;
    
    const id = e.target.dataset.id;
    const name = e.target.dataset.name;
    const slug = name.toLowerCase().replace(/\s+/g, '-');
    
    if (e.target.checked) {
      state.selectedTags.set(slug, { id, name });
    } else {
      state.selectedTags.delete(slug);
    }
    
    renderSelectedTags();
  }

  // ---- Images System ----
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
      
      // 🔥 CORRECCIÓN: Guardar el objeto File completo
      state.imageFiles.push({ 
        _id: id, 
        file: file, // Este es el objeto File que necesitamos enviar
        url: url,
        name: file.name,
        size: file.size
      });
      createPreviewCard({ _id: id, url });
    }
    
    updateImageStatus();
    
    // DEBUG: Verificar que las imágenes se están guardando
    console.log('Imágenes en state:', state.imageFiles.map(img => ({
      name: img.file.name,
      size: img.file.size,
      type: img.file.type
    })));
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
      // 🔥 IMPORTANTE: Liberar la URL del objeto
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
    
    // Limpiar del state
    state.miniaturaFile = null;
  }

  // ---- Quick Preview ----
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
                    termId: sel.value === "0" ? null : sel.value // Convertir "0" a null
                });
            }
        });
        variacion.atributos = attrs;
    });
  }

  // ---- Form Submission ----
  function validarProducto() {
    const tipo = qs('#tipoProductoSelect')?.value;
    const nombre = qs('[name="nombre"]')?.value.trim();
    const estado = qs('[name="estado"]')?.value;
    const miniatura = state.miniaturaFile;
    const categoria = qs('#categoriaSelect')?.value;
    const subcat = qs('[name="id_subCategorias"]:checked')?.value;

    // Validaciones generales
    if (!estado || !nombre || !miniatura || !categoria || !subcat) {
      showAlert('warning','Campos obligatorios','Debes completar: nombre, estado, miniatura, categoría y subcategoría.');
      return false;
    }

    // Validaciones por tipo
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
      // Stock general
      if (qs('#checkGestion')?.checked) {
          const stock = qs('[name="stock"]')?.value;
          if (!stock) {
              showAlert('warning','Stock requerido','Si gestionas inventario, el stock es obligatorio.');
              return false;
          }
      }

      // Atributos (opcional, pero si hay deben tener valores)
      const material = state.productoAtributos.find(a => a.atributo.nombre.toLowerCase() === 'material');
      if (material && (!material.valores || material.valores.length === 0)) {
          showAlert('warning','Valores requeridos','El atributo Material debe tener valores seleccionados.');
          return false;
      }

      // Variaciones - SOLO validamos que existan, no que tengan todos los valores
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
    
    // Deshabilitar botón de envío
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
            showAlert('success', 'Éxito', 'Producto guardado correctamente.');
            resetForm();
            const modalEl = document.getElementById('modalProducto');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
            $('#tablaProductos').DataTable().ajax.reload(null, false);
        } else {
            showAlert('error', 'Error', data.mensaje || 'Error al guardar el producto.');
        }
    })
    .catch(err => {
        console.error('Error de red:', err);
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

    // --- Imágenes principales ---
    if (state.imageFiles && state.imageFiles.length > 0) {
        state.imageFiles.forEach((imageFile, index) => {
            formData.append('imagenes[]', imageFile.file);
        });
    }

    // --- Etiquetas ---
    if (state.selectedTags && state.selectedTags.size > 0) {
        const tagIds = Array.from(state.selectedTags.values()).map(tag => tag.id);
        formData.append('etiquetas', JSON.stringify(tagIds));
    }

    // --- Atributos ---
    if (state.productoAtributos && state.productoAtributos.length) {
        formData.append('atributos', JSON.stringify(state.productoAtributos.map(a => ({
            atributo_id: a.atributo.id,
            valores: a.valores.map(v => v.id),
            variacion: a.variacion,
            visible: a.visible
        }))));
    }

    // --- Variaciones ---
    if (state.variaciones && state.variaciones.length) {
        const variacionesParaEnviar = state.variaciones.map((variacion, index) => {
            const variacionData = {
              sku: variacion.sku || null,                  // null si vacío
              stock: variacion.stock !== undefined && variacion.stock !== '' ? Number(variacion.stock) : 0,
              price_normal: variacion.price_normal ? Number(variacion.price_normal) : 0,
              price_sale: variacion.price_sale ? Number(variacion.price_sale) : 0,
              sale_start: variacion.sale_start || null,
              sale_end: variacion.sale_end || null,
              weight: variacion.weight ? Number(variacion.weight) : null,     // 🔥 clave
              weight_type: variacion.weight_type || 'kg',
              length: variacion.length ? Number(variacion.length) : null,     // 🔥 clave
              width: variacion.width ? Number(variacion.width) : null,        // 🔥 clave
              height: variacion.height ? Number(variacion.height) : null,     // 🔥 clave
              description: variacion.description || null,
              backorder: variacion.backorder || 'no',
              atributos: (variacion.atributos || []).map(attr => ({
                  atrId: attr.atrId,
                  termId: attr.termId // puede ser null
              }))
          };
            
            // Agregar imágenes de variación
            if (variacion.images && variacion.images.length > 0) {
              variacion.images.forEach(img => {
                formData.append(`variation_images_${index}[]`, img.file);
              });
            }
            
            return variacionData;
        });

        formData.append('variaciones', JSON.stringify(variacionesParaEnviar));
    }

    // --- Cross-sells / Upsells ---
    const crosssells = (state.crosssells || []).map(p => p.id);
    const upsells = (state.upsells || []).map(p => p.id);
    formData.append('crosssells', JSON.stringify(crosssells));
    formData.append('upsells', JSON.stringify(upsells));

    if (state.relacionados && state.relacionados.length > 0) {
      const relacionados = state.relacionados.map(p => p.id);
      formData.append('relacionados', JSON.stringify(relacionados));
    }

    // --- Campos de inventario ---
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

  // ---- Reset System ----
  function initResetSystem() {
    qsa('#modalProducto .btn-close, #modalProducto [data-bs-dismiss="modal"]').forEach(btn => {
      btn.addEventListener('click', resetForm);
    });
  }

  function resetForm() {
    const form = qs('#formProducto');
    if (form) {
      form.reset();
    }

    // Reset state
    state.imageFiles = [];
    state.selectedTags.clear();
    state.productoAtributos = [];
    state.upsells = [];
    state.crosssells = [];
    state.variaciones = [];
    state.isSubmitting = false;
    state.relacionados = [];

    const relacionadosContainer = qs('#inputRelacionados')?.nextElementSibling;
    if (relacionadosContainer && relacionadosContainer.classList.contains('tag-container')) {
      relacionadosContainer.innerHTML = '';
    }


    // Reset UI
    const previewContainer = qs('#previewContainer');
    if (previewContainer) {
      previewContainer.innerHTML = '';
    }
    updateImageStatus();

    handleRemoveMiniatura();
    renderSelectedTags();
    renderAtributoBlocks();
    renderVariacionesUI();

    // Limpiar contenedores de tags
    const upsellContainer = qs('#inputUpsells')?.nextElementSibling;
    const crosssellContainer = qs('#inputCrosssells')?.nextElementSibling;

    if (upsellContainer && upsellContainer.classList.contains('tag-container')) {
      upsellContainer.innerHTML = '';
    }
    if (crosssellContainer && crosssellContainer.classList.contains('tag-container')) {
      crosssellContainer.innerHTML = '';
    }
  }

  // ---- Toggle Systems ----
  function initToggleSystems() {
    // Programar rebaja
    const checkRebaja = qs('#checkRebaja');
    const rebajaFechas = qs('#rebajaFechas');
    
    if (checkRebaja && rebajaFechas) {
      checkRebaja.addEventListener('change', function() {
        rebajaFechas.classList.toggle('d-none', !this.checked);
      });
    }
    
    // Gestión de inventario
    const checkGestion = qs('#checkGestion');
    const invExtra = qs('#invExtra');
    
    if (checkGestion && invExtra) {
      checkGestion.addEventListener('change', function() {
        invExtra.classList.toggle('d-none', !this.checked);
      });
    }
  }

  // ---- Helper Functions ----
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

  // ---- Initialization ----
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
  }

  // Start the application
  init();

  window._productAdmin = window._productAdmin || {};
  window._productAdmin.generateVariationsFromAtributos = generateVariationsFromAtributos;
  window._productAdmin.state = state;

})();

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
      { data: 'precio', render: (data) => data },  // ✅ precio viene con <span>, se pinta directo
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
          <button class="btn btn-danger btn-sm" onclick="eliminarProducto(${data.id})">
            <i class="bi bi-trash"></i>
          </button>
        `
      }
    ]
  });
});

// Eliminar producto
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
  crosssells: []
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

        // Guardamos en editState
        editState.productoActual = producto;
        editState.imagenes = producto.imagenes || [];
        editState.imagenesNuevas = [];
        editState.imagenesAEliminar = [];
        editState.upsells = [];
        editState.crosssells = [];

        // Procesar productos relacionados (upsells/crosssells)
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

        // Abrimos modal según tipo
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
        console.error('Error al obtener producto:', err);
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