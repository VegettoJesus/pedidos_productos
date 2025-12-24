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
        <label class="form-label">Ventas dirigidas (Upsells)</label>
        <input type="text" id="inputUpsells" class="form-control" placeholder="Buscar producto...">
      </div>
      <div class="mb-3">
        <label class="form-label">Ventas cruzadas (Cross-sells)</label>
        <input type="text" id="inputCrosssells" class="form-control" placeholder="Buscar producto...">
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
            <option value="">Seleccionar...</option>
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
          variacionActual.atributos[pos].termId = termId;
        } else {
          variacionActual.atributos.push({ atrId, termId });
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

    // Crear arrays de valores por atributo
    const arrays = attrs.map(a => a.valores.map(v => ({
      atrId: a.atributo.id,
      termId: v.id,
      nombre: v.nombre
    })));

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
        termId: a.valores[0]?.id || null 
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
                    termId: sel.value
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

      // Atributos
      const material = state.productoAtributos.find(a => a.atributo.nombre.toLowerCase() === 'material');
      if (material && (!material.valores || material.valores.length === 0)) {
        showAlert('warning','Valores requeridos','El atributo Material debe tener valores seleccionados.');
        return false;
      }

      // Variaciones
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
                sku: variacion.sku || '',
                stock: variacion.stock || 0,
                price_normal: variacion.price_normal || '',
                price_sale: variacion.price_sale || '',
                sale_start: variacion.sale_start || '',
                sale_end: variacion.sale_end || '',
                weight: variacion.weight || '',
                weight_type: variacion.weight_type || 'kg',
                length: variacion.length || '',
                width: variacion.width || '',
                height: variacion.height || '',
                description: variacion.description || '',
                backorder: variacion.backorder || 'no',
                atributos: variacion.atributos || []
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
    const { data } = await axios.post('productos', {
      opcion: 'Obtener',
      id
    });

    if (data.respuesta !== 'ok') {
      Swal.fire('Error', data.mensaje || 'No se pudo obtener el producto', 'error');
      return;
    }

    const producto = data.producto;

    // Guardamos en editState
    editState.productoActual = producto;
    editState.variaciones    = producto.variaciones || [];
    editState.relacionados   = producto.productos_relacionados || [];
    editState.agrupados      = producto.productos_agrupados || [];
    editState.imagenes       = producto.imagenes || [];

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
    }

  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'Error al obtener el producto', 'error');
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
function renderEtiquetasSimpleUI(producto) {
  const availableDiv = $('#simple_availableTags');
  const selectedDiv  = $('#simple_selectedTags');

  availableDiv.html('');
  selectedDiv.html('');

  const seleccionadas = (producto.etiquetas || []).map(e => e.id);

  // Render disponibles
  _ETIQUETAS.forEach(tag => {
    const checked = seleccionadas.includes(tag.id) ? 'checked' : '';
    availableDiv.append(`
      <div class="form-check">
        <input class="form-check-input tag-available simple_tagCheck" type="checkbox" value="${tag.id}" id="tag_${tag.id}" ${checked}>
        <label class="form-check-label" for="tag_${tag.id}">
          <span class="text-black">${tag.nombre}</span>
        </label>
      </div>
    `);
  });

  // Render chips seleccionados
  producto.etiquetas?.forEach(t => {
    selectedDiv.append(`
      <span class="badge d-inline-flex align-items-center text-black chip">
        ${t.slug}
        <button type="button" class="btn-close btn-close-white btn-sm ms-2 remove-tag" data-id="${t.id}"></button>
      </span>
    `);
  });

  // Evento check
  $('.simple_tagCheck').off('change').on('change', function () {
    const tagId = parseInt(this.value);
    const tag = _ETIQUETAS.find(e => e.id === tagId);
    if (this.checked) {
      if (!producto.etiquetas.some(e => e.id === tagId)) {
        producto.etiquetas.push(tag);
      }
    } else {
      producto.etiquetas = producto.etiquetas.filter(e => e.id !== tagId);
    }
    renderEtiquetasSimpleUI(producto);
  });

  // Evento eliminar desde chip
  selectedDiv.find('.remove-tag').off('click').on('click', function () {
    const tagId = parseInt($(this).data('id'));
    producto.etiquetas = producto.etiquetas.filter(e => e.id !== tagId);
    renderEtiquetasSimpleUI(producto);
  });
}

// Crear nueva etiqueta en frontend
$('#simple_btnAddTag').off('click').on('click', async function () {
  const val = $('#simple_tagInput').val().trim();
  if (!val) return;

  try {
    const { data } = await axios.post('productos', {
      opcion: 'CrearEtiqueta',
      nombre: val
    });

    if (data.respuesta === 'ok') {
      const nueva = data.etiqueta;

      _ETIQUETAS.push(nueva);

      if (!editState.productoActual.etiquetas) {
        editState.productoActual.etiquetas = [];
      }
      editState.productoActual.etiquetas.push(nueva);

      $('#simple_tagInput').val('');

      renderEtiquetasSimpleUI(editState.productoActual);

      Swal.fire('Etiqueta creada', `"${nueva.nombre}" fue agregada correctamente`, 'success');
    } else {
      Swal.fire('Error', data.mensaje || 'No se pudo crear la etiqueta', 'error');
    }
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'Error de servidor al crear la etiqueta', 'error');
  }
});

function renderAtributosSimpleUI() {
  const container = document.getElementById('simple_atributosContainer');
  if (!container) return;

  container.innerHTML = '';

  // --- Selector de atributos existentes ---
  let selectWrap = document.createElement('div');
  selectWrap.className = 'atributos-select-wrap mb-3';
  selectWrap.innerHTML = `
    <label class="form-label">Seleccionar atributo existente</label>
    <select class="form-select mb-2" id="simple_select_atributo">
      <option value="">-- Seleccione --</option>
      ${_ATRIBUTOS.map(a => `<option value="${a.id}">${escapeHtml(a.nombre)}</option>`).join('')}
    </select>
    <div>
      <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="abrirModalCrearAtributo()">Crear nuevo atributo</button>
    </div>
    <div id="simple_atributosBlocks" class="mt-3"></div>
  `;
  container.appendChild(selectWrap);

  // --- Listener para añadir atributos existentes ---
  selectWrap.querySelector('#simple_select_atributo').addEventListener('change', function() {
    const id = this.value;
    if (!id) return;
    const atributo = _ATRIBUTOS.find(a => String(a.id) === String(id));
    if (atributo) addAtributoBlockSimple(atributo);
    this.value = '';
  });

  renderAtributoBlocksSimple();
}
async function abrirModalCrearAtributo() {
  // Ocultar temporalmente el modal de Bootstrap
  const modalSimple = bootstrap.Modal.getInstance(document.getElementById('modalProductoSimple'));
  if (modalSimple) {
    modalSimple.hide();
  }

  const { value: formValues } = await Swal.fire({
    title: 'Nuevo atributo',
    html: `
      <div id="swalAtributoContainer" style="max-width: 500px; margin: 0 auto;">
        <label class="form-label mb-2">Nombre del atributo</label>
        <input id="swal_attr_nombre" class="form-control mb-3" placeholder="Ej: Color, Tamaño, Material" autofocus>
        
        <label class="form-label mb-2">Slug (opcional)</label>
        <input id="swal_attr_slug" class="form-control" placeholder="Ej: color, tamano, material">
        
        <div class="mt-3 small text-muted">
          <i class="bi bi-info-circle"></i> El slug se generará automáticamente si lo dejas vacío
        </div>
      </div>
    `,
    width: 600,
    showCancelButton: true,
    confirmButtonText: 'Guardar atributo',
    cancelButtonText: 'Cancelar',
    showLoaderOnConfirm: true,
    allowOutsideClick: () => !Swal.isLoading(),
    preConfirm: async () => {
      const nombre = document.getElementById('swal_attr_nombre').value.trim();
      const slug = document.getElementById('swal_attr_slug').value.trim();
      
      if (!nombre) {
        Swal.showValidationMessage('El nombre del atributo es requerido');
        return false;
      }
      
      return { nombre, slug };
    }
  });

  // Mostrar nuevamente el modal de Bootstrap
  if (modalSimple) {
    setTimeout(() => {
      modalSimple.show();
    }, 100);
  }

  if (!formValues) return;

  try {
    const { data } = await axios.post('productos', {
      opcion: 'CrearAtributo',
      nombre: formValues.nombre,
      slug: formValues.slug
    });

    if (data.respuesta === 'ok') {
      const nuevoAtributo = data.atributo;
      
      // 1. Actualizar la lista global de atributos
      _ATRIBUTOS.push(nuevoAtributo);
      
      // 2. AGREGAR AUTOMÁTICAMENTE EL ATRIBUTO AL PRODUCTO ACTUAL
      if (!editState.productoActual.atributos) {
        editState.productoActual.atributos = [];
      }
      
      // Verificar si ya existe
      const yaExiste = editState.productoActual.atributos.some(a => a.id === nuevoAtributo.id);
      
      if (!yaExiste) {
        // Agregar el nuevo atributo al producto con array de términos vacío
        editState.productoActual.atributos.push({
          id: nuevoAtributo.id,
          nombre: nuevoAtributo.nombre,
          slug: nuevoAtributo.slug,
          terminos: [] // Inicialmente vacío
        });
      }
      
      // 3. Re-renderizar la UI de atributos
      renderAtributosSimpleUI();
      
      // 4. Seleccionar automáticamente el atributo recién creado en el dropdown
      setTimeout(() => {
        $('#simple_select_atributo').val(nuevoAtributo.id);
      }, 100);
      
      // 5. Mostrar mensaje de éxito
      Swal.fire({
        title: '¡Atributo creado y agregado!',
        text: `"${nuevoAtributo.nombre}" ha sido agregado al producto`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      Swal.fire('Error', data.mensaje, 'error');
    }
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'No se pudo crear el atributo', 'error');
  }
}

async function abrirModalCrearValor(atributoId) {
  // Obtener información del atributo para mostrar en el título
  const atributo = _ATRIBUTOS.find(a => a.id == atributoId);
  if (!atributo) {
    Swal.fire('Error', 'Atributo no encontrado', 'error');
    return;
  }

  // Verificar si el atributo está agregado al producto
  let atributoEnProducto = editState.productoActual.atributos?.find(a => a.id == atributoId);
  
  // Si el atributo no está en el producto, agregarlo primero
  if (!atributoEnProducto) {
    if (!editState.productoActual.atributos) {
      editState.productoActual.atributos = [];
    }
    
    editState.productoActual.atributos.push({
      id: atributo.id,
      nombre: atributo.nombre,
      slug: atributo.slug,
      terminos: []
    });
    
    atributoEnProducto = editState.productoActual.atributos.find(a => a.id == atributoId);
  }

  // Ocultar temporalmente el modal de Bootstrap
  const modalSimple = bootstrap.Modal.getInstance(document.getElementById('modalProductoSimple'));
  if (modalSimple) {
    modalSimple.hide();
  }

  const { value: formValues } = await Swal.fire({
    title: `Nuevo valor para "${atributo.nombre}"`,
    html: `
      <div id="swalValorContainer" style="max-width: 500px; margin: 0 auto;">
        <input type="hidden" id="swal_val_attrId" value="${atributoId}">
        
        <label class="form-label mb-2">Nombre del valor</label>
        <input id="swal_val_nombre" class="form-control mb-3" placeholder="Ej: Rojo, Grande, Algodón" autofocus>
        
        <label class="form-label mb-2">Slug (opcional)</label>
        <input id="swal_val_slug" class="form-control mb-3" placeholder="Ej: rojo, grande, algodon">
        
        <label class="form-label mb-2">Descripción (opcional)</label>
        <textarea id="swal_val_desc" class="form-control" rows="2" placeholder="Descripción adicional..."></textarea>
        
        <div class="mt-3 small text-muted">
          <i class="bi bi-info-circle"></i> El slug se generará automáticamente si lo dejas vacío
        </div>
      </div>
    `,
    width: 600,
    showCancelButton: true,
    confirmButtonText: 'Guardar valor',
    cancelButtonText: 'Cancelar',
    showLoaderOnConfirm: true,
    allowOutsideClick: () => !Swal.isLoading(),
    preConfirm: async () => {
      const nombre = document.getElementById('swal_val_nombre').value.trim();
      
      if (!nombre) {
        Swal.showValidationMessage('El nombre del valor es requerido');
        return false;
      }
      
      const slug = document.getElementById('swal_val_slug').value.trim();
      const descripcion = document.getElementById('swal_val_desc').value.trim();
      
      return {
        atributo_id: document.getElementById('swal_val_attrId').value,
        nombre,
        slug,
        descripcion
      };
    }
  });

  // Mostrar nuevamente el modal de Bootstrap
  if (modalSimple) {
    setTimeout(() => {
      modalSimple.show();
    }, 100);
  }

  if (!formValues) return;

  try {
    const { data } = await axios.post('productos', {
      opcion: 'CrearValorAtributo',
      ...formValues
    });

    if (data.respuesta === 'ok') {
      const nuevoValor = data.termino;
      
      // 1. Buscar atributo en la lista global y añadir valor nuevo
      const attrGlobal = _ATRIBUTOS.find(a => a.id == formValues.atributo_id);
      if (attrGlobal) {
        if (!attrGlobal.terminos) attrGlobal.terminos = [];
        attrGlobal.terminos.push(nuevoValor);
      }
      
      // 2. SELECCIONAR AUTOMÁTICAMENTE EL NUEVO VALOR EN EL ATRIBUTO DEL PRODUCTO
      if (atributoEnProducto) {
        // Verificar si el valor ya está seleccionado
        const yaSeleccionado = atributoEnProducto.terminos?.some(t => t.id === nuevoValor.id);
        
        if (!yaSeleccionado) {
          // Agregar el nuevo valor a los términos seleccionados
          if (!atributoEnProducto.terminos) {
            atributoEnProducto.terminos = [];
          }
          
          atributoEnProducto.terminos.push({
            id: nuevoValor.id,
            nombre: nuevoValor.nombre,
            slug: nuevoValor.slug
          });
        }
      }
      
      // 3. Re-renderizar los bloques de atributos
      renderAtributoBlocksSimple();
      
      // 4. Mostrar mensaje de éxito
      Swal.fire({
        title: '¡Valor creado y seleccionado!',
        text: `"${nuevoValor.nombre}" ha sido agregado y seleccionado en "${atributo.nombre}"`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      Swal.fire('Error', data.mensaje, 'error');
    }
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'No se pudo crear el valor', 'error');
  }
}

function addAtributoBlockSimple(atributoObj) {
  if (!editState.productoActual.atributos) {
    editState.productoActual.atributos = [];
  }

  if (editState.productoActual.atributos.some(x => x.id === atributoObj.id)) {
    showAlert('info', 'Ya agregado', 'Ese atributo ya fue agregado al producto.');
    return;
  }

  editState.productoActual.atributos.push({
    id: atributoObj.id,
    nombre: atributoObj.nombre,
    slug: atributoObj.slug,
    terminos: []
  });

  renderAtributoBlocksSimple();
}

function renderAtributoBlocksSimple() {
  const blocks = document.getElementById('simple_atributosBlocks');
  if (!blocks) return;

  blocks.innerHTML = '';

  (editState.productoActual.atributos || []).forEach(attr => {
    const block = createAtributoBlockSimple(attr);
    blocks.appendChild(block);
  });
}

function createAtributoBlockSimple(attr) {
  const attrFull = _ATRIBUTOS.find(x => String(x.id) === String(attr.id));

  const wrapper = document.createElement('div');
  wrapper.className = 'atributo-block border rounded p-3 mt-3 mb-3 shadow-sm bg-white';
  wrapper.dataset.atributoId = attr.id;

  wrapper.innerHTML = `
    <div class="d-flex justify-content-between align-items-start mb-2">
      <div>
        <h6 class="mb-1 fw-bold">${escapeHtml(attr.nombre)}</h6>
        <span class="badge bg-light text-secondary border small">${escapeHtml(attr.slug)}</span>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input chk-visible" type="checkbox" checked>
        <label class="form-check-label small">Visible</label>
      </div>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-sm btn-outline-primary btnCrearValor2 me-2">
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

  // --- Render valores disponibles ---
  const availDiv = wrapper.querySelector('.valores-available');
  if (availDiv && attrFull?.terminos) {
    attrFull.terminos.forEach(term => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-sm btn-outline-info btn-termino';
      btn.dataset.termId = term.id;
      btn.textContent = term.nombre;
      btn.addEventListener('click', () => {
        if (!attr.terminos.some(v => v.id === term.id)) {
          attr.terminos.push({
            id: term.id,
            nombre: term.nombre,
            slug: term.slug
          });
          renderAtributoBlocksSimple();
        }
      });
      availDiv.appendChild(btn);
    });
  }

  // --- Render chips (valores seleccionados) ---
  const chipsDiv = wrapper.querySelector('.valores-chips');
  (attr.terminos || []).forEach(v => {
    const chip = document.createElement('span');
    chip.className = 'chip d-inline-flex align-items-center px-2 py-1 rounded bg-light border';
    chip.innerHTML = `
      ${escapeHtml(v.nombre)}
      <button type="button" class="btn-close btn-sm ms-2 remove-valor"></button>
    `;
    chip.querySelector('.remove-valor').addEventListener('click', () => {
      attr.terminos = attr.terminos.filter(x => x.id !== v.id);
      renderAtributoBlocksSimple();
    });
    chipsDiv.appendChild(chip);
  });

  // --- Botones ---
  wrapper.querySelector('.btnEliminarAtributo').addEventListener('click', () => {
    editState.productoActual.atributos = editState.productoActual.atributos.filter(a => a.id !== attr.id);
    renderAtributoBlocksSimple();
  });

  wrapper.querySelector('.btnSelectAll').addEventListener('click', () => {
    attr.terminos = (attrFull?.terminos || []).map(t => ({
      id: t.id, nombre: t.nombre, slug: t.slug
    }));
    renderAtributoBlocksSimple();
  });

  wrapper.querySelector('.btnCrearValor2').addEventListener('click', () => {
    abrirModalCrearValor(attr.id);
  });

  return wrapper;
}

function abrirModalSimple(producto) {
  editState.productoActual = producto;
  editState.imagenes = producto.imagenes || [];
  editState.imagenesNuevas = []; 
  editState.imagenesAEliminar = [];editState.imagenes = producto.imagenes || [];
  editState.imagenesNuevas = [];  
  editState.imagenesAEliminar = [];
  editState.upsells = [];
  editState.crosssells = [];

  // Datos básicos
  $('#simple_id').val(producto.id);
  $('#simple_nombre').val(producto.nombre);
  $('#simple_marca').val(producto.marca || '');
  $('#simple_descripcion').val(producto.descripcion || '');
  $('#simple_estado').val(producto.estado || 'borrador');
  $('#simple_precio_regular').val(producto.precio_regular || '');
  $('#simple_precio_rebajado').val(producto.precio_rebajado || '');
  setupInventarioFields(producto);
  $('#simple_sku').val(producto.sku || '');
  $('#simple_stock').val(producto.stock || '');
  $('#simple_estado_inventario').val(producto.estado_inventario || 'existe');
  $('#simple_peso').val(producto.peso || '');
  $('#simple_longitud').val(producto.longitud || '');
  $('#simple_anchura').val(producto.anchura || '');
  $('#simple_altura').val(producto.altura || '');
  $('#simple_nota_interna').val(producto.nota_interna || '');
  $('#simple_valoraciones').prop('checked', !!producto.permite_valoraciones);

  const productosRelacionados = producto.productos_relacionados || [];
  
  console.log('Productos relacionados cargados:', productosRelacionados);
  
  productosRelacionados.forEach(rel => {
    console.log('Procesando relación:', rel);
    
    // Verificar que la relación tenga datos válidos
    if (!rel || !rel.id) {
      console.warn('Relación inválida encontrada:', rel);
      return;
    }
    
    if (rel.tipo === 'upsell') {
      editState.upsells.push({
        id: rel.id,
        nombre: rel.nombre || 'Producto no encontrado',
        sku: rel.sku || 'N/A'
      });
    } else if (rel.tipo === 'crosssell') {
      editState.crosssells.push({
        id: rel.id,
        nombre: rel.nombre || 'Producto no encontrado',
        sku: rel.sku || 'N/A'
      });
    }
  });

  console.log('Upsells cargados:', editState.upsells);
  console.log('Crosssells cargados:', editState.crosssells);

  // Miniatura
  if (producto.imagen_miniatura) {
    $('#simple_miniImg').attr('src', "/" + producto.imagen_miniatura).show();
    $('#simple_miniPlaceholder').hide();
    $('#simple_removeMini').removeClass('d-none');
  }

  if (producto.productos_relacionados && producto.productos_relacionados.length > 0) {
    producto.productos_relacionados.forEach(rel => {
      if (rel.pivot.tipo === 'upsell') {
        editState.upsells.push({
          id: rel.id,
          nombre: rel.nombre || 'Producto no encontrado',
          sku: rel.sku || 'N/A'
        });
      } else if (rel.pivot.tipo === 'crosssell') {
        editState.crosssells.push({
          id: rel.id,
          nombre: rel.nombre || 'Producto no encontrado',
          sku: rel.sku || 'N/A'
        });
      }
    });
  }

  renderImagenesExistentes();

  if (!document.getElementById('simple_removeAllImages')) {
    $('#simple_imagenes').after(`
      <button type="button" id="simple_removeAllImages" class="btn btn-sm btn-outline-danger mt-2 d-none">
        <i class="bi bi-trash"></i> Eliminar todas las imágenes
      </button>
    `);
  }

  setupEventosImagenes();
  setupMiniaturaEvents();
  setupProgramarRebaja(producto);
  setupProductoRelacionadoSearch('#simple_upsells', 'upsells');
  setupProductoRelacionadoSearch('#simple_crosssells', 'crosssells');

  // Etiquetas
  const tags = $('#simple_tags');
  tags.html('');
  if (producto.etiquetas && producto.etiquetas.length > 0) {
    producto.etiquetas.forEach(t => {
      tags.append(`<span class="badge me-1" style="background:${t.color}">${t.nombre}</span>`);
    });
  } else {
    tags.html('<span class="text-muted">Sin etiquetas</span>');
  }

  // 🔹 CATEGORÍAS + SUBCATEGORÍAS
  const categoriaId = producto.subcategoria ? producto.subcategoria.id_categoria : null;
  const subcategoriaId = producto.subcategoria ? producto.subcategoria.id : null;
  renderCategoriasSimple(categoriaId, subcategoriaId);

  // 🔹 ETIQUETAS
  renderEtiquetasSimpleUI(producto);

  // 🔹 ATRIBUTOS
  renderAtributosSimpleUI();
  

  // Mostrar modal
  const modal = new bootstrap.Modal(document.getElementById('modalProductoSimple'));
  modal.show();
}
function setupInventarioFields(producto) {
  // SKU
  $('#simple_sku').val(producto.sku || '');
  
  // Gestión de inventario (checkbox)
  const gestionInventario = producto.gestion_inventario == 1 || producto.gestion_inventario === true;
  $('#simple_gestion_inventario').prop('checked', gestionInventario);
  
  // Stock
  $('#simple_stock').val(producto.stock || 0);
  
  // Backorders (radio buttons)
  const backorders = producto.backorders == 1 || producto.backorders === true;
  if (backorders) {
    $('#simple_backorders_si').prop('checked', true);
  } else {
    $('#simple_backorders_no').prop('checked', true);
  }
  
  // Estado de inventario (radio buttons)
  const estadoInventario = producto.estado_inventario || 'existe';
  $(`#simple_estado_${estadoInventario}`).prop('checked', true);
  
  // Vendido individualmente (checkbox)
  const vendidoIndividualmente = producto.vendido_individualmente == 1 || producto.vendido_individualmente === true;
  $('#simple_vendido_individualmente').prop('checked', vendidoIndividualmente);
  
  // Mostrar/ocultar detalles de inventario según el checkbox
  toggleInventarioDetalles(gestionInventario);
  
  // Evento para el checkbox de gestión de inventario
  $('#simple_gestion_inventario').off('change').on('change', function() {
    toggleInventarioDetalles(this.checked);
  });
}

// Función para mostrar/ocultar los detalles de inventario
function toggleInventarioDetalles(mostrar) {
  const $detalles = $('#simple_inventario_detalles');
  const $stockInput = $('#simple_stock');
  
  if (mostrar) {
    $detalles.slideDown();
    $stockInput.prop('disabled', false);
  } else {
    $detalles.slideUp();
    $stockInput.prop('disabled', true).val(0);
    
    // Resetear radios cuando se oculta
    $('#simple_backorders_no').prop('checked', true);
    $('#simple_estado_existe').prop('checked', true);
  }
}
function setupProductoRelacionadoSearch(selector, type) {
  const $input = $(selector);
  if (!$input.length) {
    console.error(`Input ${selector} no encontrado`);
    return;
  }

  console.log(`Configurando búsqueda para: ${type}, selector: ${selector}`);

  // VERIFICAR SI YA EXISTE EL CONTENEDOR - EVITAR DUPLICADOS
  const containerId = `${type}-tags-container`;
  let $container = $(`#${containerId}`);
  
  if ($container.length === 0) {
    // Crear contenedor para tags solo si no existe
    $container = $(`<div id="${containerId}" class="tag-container d-flex flex-wrap gap-2 mt-2 mb-3"></div>`);
    $input.after($container);
    console.log(`Contenedor ${containerId} creado`);
  } else {
    console.log(`Contenedor ${containerId} ya existe, limpiando contenido...`);
    $container.empty();
  }

  // VERIFICAR SI YA EXISTE EL DROPDOWN - EVITAR DUPLICADOS
  const dropdownClass = `dropdown-${type}`;
  let $dropdown = $(`.${dropdownClass}`);
  
  if ($dropdown.length === 0) {
    // Crear dropdown solo si no existe
    $dropdown = $(`
      <div class="dropdown-menu p-0 shadow ${dropdownClass}" 
           style="display: none; max-height: 250px; overflow-y: auto; width: 100%;">
      </div>
    `);
    console.log(`Dropdown ${dropdownClass} creado`);
  } else {
    console.log(`Dropdown ${dropdownClass} ya existe, limpiando contenido...`);
    $dropdown.empty();
  }

  // VERIFICAR SI YA EXISTE EL WRAPPER - EVITAR DUPLICADOS
  if (!$input.parent().hasClass('position-relative')) {
    // Crear un wrapper para el input y dropdown solo si no existe
    const $wrapper = $('<div class="position-relative"></div>');
    $input.wrap($wrapper);
    console.log('Wrapper creado');
  } else {
    console.log('Wrapper ya existe');
  }

  // Asegurarse de que el dropdown esté en el DOM correctamente
  if ($dropdown.parent().length === 0) {
    $input.after($dropdown);
    console.log('Dropdown agregado al DOM');
  }

  // Limpiar eventos previos para evitar duplicación
  $input.off('input.productoSearch');
  $input.off('keydown.productoSearch');
  $(window).off(`resize.${type}`);

  let timeout = null;

  // Evento de búsqueda
  $input.on('input.productoSearch', function(e) {
    console.log(`Buscando para ${type}:`, $(this).val());
    
    clearTimeout(timeout);
    const term = $(this).val().trim();
    
    if (!term || term.length < 2) {
      $dropdown.hide();
      return;
    }

    // Mostrar indicador de búsqueda
    $dropdown.html(`
      <div class="dropdown-item text-muted py-2">
        <div class="d-flex align-items-center">
          <div class="spinner-border spinner-border-sm text-primary bg-transparent me-2"></div>
          <span>Buscando...</span>
        </div>
      </div>
    `);
    positionDropdown();
    $dropdown.show();

    timeout = setTimeout(async () => {
      try {
        const { data } = await axios.post('productos', {
          opcion: 'Buscar',
          query: term
        });

        console.log(`Resultados para ${type}:`, data);

        if (data.respuesta === 'ok' && data.productos && data.productos.length > 0) {
          renderDropdown(data.productos);
          positionDropdown();
          $dropdown.show();
        } else {
          // MOSTRAR MENSAJE CUANDO NO HAY RESULTADOS
          $dropdown.html(`
            <div class="dropdown-item text-muted py-2">
              <i class="bi bi-search me-2"></i>
              No se encontraron productos
            </div>
          `);
          positionDropdown();
        }
      } catch (err) {
        console.error('Error buscando productos:', err);
        $dropdown.html(`
          <div class="dropdown-item text-danger py-2">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Error en la búsqueda
          </div>
        `);
        positionDropdown();
      }
    }, 400);
  });

  // Posicionar dropdown correctamente
  function positionDropdown() {
    const inputHeight = $input.outerHeight();
    
    $dropdown.css({
      position: 'absolute',
      top: inputHeight + 5,
      left: 0,
      width: $input.outerWidth(),
      display: 'block',
      zIndex: 1060 // Bootstrap modal tiene z-index 1055
    });
  }

  // Renderizar dropdown
  function renderDropdown(productos) {
    $dropdown.empty();
    
    // Filtrar productos que ya están seleccionados
    const selectedIds = editState[type] ? editState[type].map(p => p.id) : [];
    console.log(`IDs ya seleccionados para ${type}:`, selectedIds);
    
    const productosFiltrados = productos.filter(p => !selectedIds.includes(p.id));
    
    if (productosFiltrados.length === 0) {
      $dropdown.html(`
        <div class="dropdown-item text-muted py-2">
          <i class="bi bi-info-circle me-2"></i>
          No hay productos disponibles
        </div>
      `);
      return;
    }

    productosFiltrados.forEach(prod => {
      // Función para truncar texto largo
      function truncateText(text, maxLength = 50) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
      }

      const nombreTruncado = truncateText(prod.nombre, 45);
      const skuTexto = prod.sku ? `SKU: ${prod.sku}` : 'Sin SKU';
      
      const $item = $(`
        <button type="button" class="dropdown-item text-start py-2">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="fw-medium" title="${escapeHtml(prod.nombre)}">
                ${escapeHtml(nombreTruncado)}
              </div>
              <div class="small text-muted">
                ${skuTexto} • ID: ${prod.id}
              </div>
            </div>
            <i class="bi bi-plus-circle ms-2 text-primary bg-transparent"></i>
          </div>
        </button>
      `);
      
      $item.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log(`Agregando producto ${prod.id} a ${type}`);
        addProductTag(prod, type);
        $dropdown.hide();
      });
      
      $dropdown.append($item);
    });
  }

  // Evento para cerrar dropdown al hacer clic fuera
  $(document).off(`click.${type}`).on(`click.${type}`, function(e) {
    if (!$(e.target).closest($input).length && !$(e.target).closest($dropdown).length) {
      $dropdown.hide();
    }
  });

  // Tecla ESC para cerrar
  $input.on('keydown.productoSearch', function(e) {
    if (e.key === 'Escape') {
      $dropdown.hide();
    }
  });

  // Redimensionar dropdown cuando cambie el tamaño de la ventana
  $(window).on(`resize.${type}`, positionDropdown);

  // Cargar tags existentes si los hay
  loadExistingTags(type);
}

// Añadir producto como tag - MODIFICADO para prevenir duplicados
function addProductTag(producto, type) {
  console.log(`addProductTag llamado para ${type}:`, producto);
  
  // Verificar si ya existe en el estado
  const existingIndex = editState[type].findIndex(p => p.id === producto.id);
  
  if (existingIndex !== -1) {
    Swal.fire({
      title: 'Ya agregado',
      text: 'Este producto ya está en la lista',
      icon: 'info',
      timer: 1500,
      showConfirmButton: false
    });
    return;
  }

  // Añadir al estado
  editState[type].push({
    id: producto.id,
    nombre: producto.nombre,
    sku: producto.sku || 'N/A'
  });

  console.log(`Estado actualizado para ${type}:`, editState[type]);

  // Actualizar tags visuales
  updateTags(type);
  
  // Limpiar input
  $(`#simple_${type === 'upsells' ? 'upsells' : 'crosssells'}`).val('');
}

// Función para truncar texto en tags
function truncateTagText(text, maxLength = 25) {
  if (!text || text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
}

// Actualizar tags visuales - MODIFICADO para prevenir duplicados Y truncar texto largo
function updateTags(type) {
  const containerId = `${type}-tags-container`;
  const $container = $(`#${containerId}`);
  
  if (!$container.length) {
    console.error(`Contenedor ${containerId} no encontrado`);
    return;
  }

  // Limpiar completamente
  $container.empty();
  
  // Usar un Set para prevenir duplicados por si acaso
  const uniqueProducts = [];
  const seenIds = new Set();
  
  editState[type].forEach(producto => {
    if (!seenIds.has(producto.id)) {
      seenIds.add(producto.id);
      uniqueProducts.push(producto);
    }
  });

  // Actualizar estado con productos únicos
  editState[type] = uniqueProducts;
  
  // Renderizar tags únicos con texto truncado
  uniqueProducts.forEach(producto => {
    // Truncar nombre si es muy largo
    const nombreTruncado = truncateTagText(producto.nombre, 30);
    const skuTexto = producto.sku || 'N/A';
    
    const $tag = $(`
      <span class="badge bg-primary text-white border-0 px-3 py-2 d-inline-flex align-items-center mb-2 me-2 tag-producto" 
            data-product-id="${producto.id}"
            title="${escapeHtml(producto.nombre)} (${skuTexto})">
        <span class="me-1 text-truncate" style="max-width: 150px;">
          ${escapeHtml(nombreTruncado)}
        </span>
        <small class="me-1 opacity-75">
          (${truncateTagText(skuTexto, 10)})
        </small>
        <button type="button" class="btn-close btn-close-white btn-sm ms-1 remove-tag-btn" 
                data-id="${producto.id}" data-type="${type}"
                title="Eliminar"></button>
      </span>
    `);
    
    $container.append($tag);
  });

  // Si no hay tags, mostrar mensaje UNA sola vez
  if (uniqueProducts.length === 0) {
    $container.html('<span class="text-muted small">No hay productos seleccionados</span>');
  }
}

// Cargar tags existentes
function loadExistingTags(type) {
  console.log(`Cargando tags existentes para ${type}:`, editState[type]);
  
  // Limpiar posibles duplicados en el estado antes de cargar
  const uniqueProducts = [];
  const seenIds = new Set();
  
  editState[type].forEach(producto => {
    if (!seenIds.has(producto.id)) {
      seenIds.add(producto.id);
      uniqueProducts.push(producto);
    }
  });
  
  editState[type] = uniqueProducts;
  
  updateTags(type);
}

// Evento delegado para eliminar tags - MODIFICADO para mayor robustez
$(document).off('click.remove-tag').on('click.remove-tag', '.remove-tag-btn', function() {
  const productId = parseInt($(this).data('id'));
  const type = $(this).data('type');
  
  console.log(`Eliminando producto ${productId} de ${type}`);
  
  if (!productId || !type) {
    console.error('Datos inválidos para eliminar tag');
    return;
  }
  
  // Eliminar del estado
  const originalLength = editState[type].length;
  editState[type] = editState[type].filter(p => p.id !== productId);
  
  if (editState[type].length === originalLength) {
    console.warn(`Producto ${productId} no encontrado en ${type}`);
  }
  
  // Actualizar vista
  updateTags(type);
});

function limpiarProductoRelacionadoSearch() {
  console.log('Limpiando búsquedas de productos relacionados...');
  
  $(document).off('click.upsells');
  $(document).off('click.crosssells');
  $(window).off('resize.upsells');
  $(window).off('resize.crosssells');
}

function renderImagenesExistentes() {
  const prev = $('#simple_previewContainer');
  prev.html('');
  
  // Mostrar imágenes existentes
  editState.imagenes.forEach((img, index) => {
    const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative existing-image');
    
    preview.html(`
      <img src="/${img.imagen_path}" 
           class="rounded border" 
           style="width:80px;height:80px;object-fit:cover;cursor:pointer;"
           data-index="${index}">
      <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
              style="transform: translate(30%, -30%); padding: 0.2rem;"
              data-index="${index}" data-type="existente"></button>
    `);
    
    prev.append(preview);
  });
  
  // Mostrar imágenes nuevas (si las hay)
  editState.imagenesNuevas.forEach((imgData, index) => {
    const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative new-image');
    
    preview.html(`
      <img src="${imgData.preview}" 
           class="rounded border" 
           style="width:80px;height:80px;object-fit:cover;"
           data-index="${index}">
      <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
              style="transform: translate(30%, -30%); padding: 0.2rem;"
              data-index="${index}" data-type="nueva"></button>
    `);
    
    prev.append(preview);
  });

  // Mostrar mensaje si no hay imágenes
  if (editState.imagenes.length === 0 && editState.imagenesNuevas.length === 0) {
    prev.html('<span class="text-muted">No hay imágenes</span>');
  }

  // Actualizar contador
  actualizarContadorImagenes();
}

function renderImagenesExistentes() {
  const prev = $('#simple_previewContainer');
  prev.html('');
  
  // Mostrar imágenes existentes
  editState.imagenes.forEach((img, index) => {
    const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative existing-image');
    
    preview.html(`
      <img src="/${img.imagen_path}" 
           class="rounded border" 
           style="width:80px;height:80px;object-fit:cover;cursor:pointer;"
           data-index="${index}">
      <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
              style="transform: translate(30%, -30%); padding: 0.2rem;"
              data-index="${index}" data-type="existente"></button>
    `);
    
    prev.append(preview);
  });
  
  // Mostrar imágenes nuevas (si las hay)
  editState.imagenesNuevas.forEach((imgData, index) => {
    const preview = $('<div>').addClass('d-inline-block me-2 mb-2 position-relative new-image');
    
    preview.html(`
      <img src="${imgData.preview}" 
           class="rounded border" 
           style="width:80px;height:80px;object-fit:cover;"
           data-index="${index}">
      <button type="button" class="btn-close btn-sm position-absolute top-0 end-0 bg-white border remove-image" 
              style="transform: translate(30%, -30%); padding: 0.2rem;"
              data-index="${index}" data-type="nueva"></button>
    `);
    
    prev.append(preview);
  });

  // Mostrar mensaje si no hay imágenes
  if (editState.imagenes.length === 0 && editState.imagenesNuevas.length === 0) {
    prev.html('<span class="text-muted">No hay imágenes</span>');
  }

  // Actualizar contador
  actualizarContadorImagenes();
}

function setupEventosImagenes() {
  // Previsualización al seleccionar imágenes nuevas
  $('#simple_imagenes').off('change').on('change', function(e) {
    const files = e.target.files;
    if (!files || files.length === 0) return;
    
    // Verificar límite (máximo 6)
    const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length + files.length;
    
    if (totalImagenes > 6) {
      Swal.fire({
        title: 'Límite excedido',
        text: `Ya tienes ${editState.imagenes.length + editState.imagenesNuevas.length} imágenes. Solo puedes agregar ${6 - (editState.imagenes.length + editState.imagenesNuevas.length)} más.`,
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      $(this).val('');
      return;
    }

    // Procesar cada archivo
    Array.from(files).forEach(file => {
      if (!file.type.startsWith('image/')) {
        Swal.fire('Error', `El archivo ${file.name} no es una imagen válida`, 'error');
        return;
      }
      
      const reader = new FileReader();
      reader.onload = function(event) {
        // Agregar a imagenesNuevas
        editState.imagenesNuevas.push({
          file: file,
          preview: event.target.result,
          name: file.name
        });
        
        // Re-renderizar todas las imágenes
        renderImagenesExistentes();
      };
      reader.readAsDataURL(file);
    });

    // Limpiar el input para permitir seleccionar las mismas imágenes nuevamente si es necesario
    $(this).val('');
  });

  // Eliminar imagen (evento delegado)
  $(document).off('click', '#simple_previewContainer .remove-image').on('click', '#simple_previewContainer .remove-image', function() {
    const index = parseInt($(this).data('index'));
    const type = $(this).data('type');
    
    Swal.fire({
      title: '¿Eliminar imagen?',
      text: type === 'existente' 
        ? 'Esta imagen se eliminará al guardar el producto' 
        : 'Esta imagen será removida de la selección',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        if (type === 'existente') {
          // Es una imagen existente - marcar para eliminar
          if (!editState.imagenesAEliminar) {
            editState.imagenesAEliminar = [];
          }
          editState.imagenesAEliminar.push(editState.imagenes[index].id);
          editState.imagenes.splice(index, 1);
        } else if (type === 'nueva') {
          // Es una imagen nueva - simplemente remover
          editState.imagenesNuevas.splice(index, 1);
        }
        
        renderImagenesExistentes();
      }
    });
  });

  // Eliminar todas las imágenes
  $('#simple_removeAllImages').off('click').on('click', function() {
    const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length;
    
    if (totalImagenes === 0) return;
    
    Swal.fire({
      title: '¿Eliminar todas las imágenes?',
      text: 'Se eliminarán todas las imágenes (tanto existentes como nuevas)',
      html: `
        <div class="text-start">
          <p>Se eliminarán:</p>
          <ul>
            ${editState.imagenes.length > 0 ? `<li>${editState.imagenes.length} imágenes existentes</li>` : ''}
            ${editState.imagenesNuevas.length > 0 ? `<li>${editState.imagenesNuevas.length} imágenes nuevas</li>` : ''}
          </ul>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar todas',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545'
    }).then((result) => {
      if (result.isConfirmed) {
        // Marcar todas las imágenes existentes para eliminar
        if (editState.imagenes.length > 0) {
          if (!editState.imagenesAEliminar) {
            editState.imagenesAEliminar = [];
          }
          editState.imagenes.forEach(img => {
            if (img.id) {
              editState.imagenesAEliminar.push(img.id);
            }
          });
        }
        
        // Limpiar todo
        editState.imagenes = [];
        editState.imagenesNuevas = [];
        $('#simple_imagenes').val('');
        
        renderImagenesExistentes();
        
        Swal.fire('Eliminadas', 'Todas las imágenes han sido eliminadas', 'success');
      }
    });
  });
}
function setupEventosImagenes() {
  // Previsualización al seleccionar imágenes nuevas
  $('#simple_imagenes').off('change').on('change', function(e) {
    const files = e.target.files;
    if (!files || files.length === 0) return;
    
    // Verificar límite (máximo 6)
    const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length + files.length;
    
    if (totalImagenes > 6) {
      Swal.fire({
        title: 'Límite excedido',
        text: `Ya tienes ${editState.imagenes.length + editState.imagenesNuevas.length} imágenes. Solo puedes agregar ${6 - (editState.imagenes.length + editState.imagenesNuevas.length)} más.`,
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      $(this).val('');
      return;
    }

    // Procesar cada archivo
    Array.from(files).forEach(file => {
      if (!file.type.startsWith('image/')) {
        Swal.fire('Error', `El archivo ${file.name} no es una imagen válida`, 'error');
        return;
      }
      
      const reader = new FileReader();
      reader.onload = function(event) {
        // Agregar a imagenesNuevas
        editState.imagenesNuevas.push({
          file: file,
          preview: event.target.result,
          name: file.name
        });
        
        // Re-renderizar todas las imágenes
        renderImagenesExistentes();
      };
      reader.readAsDataURL(file);
    });

    // Limpiar el input para permitir seleccionar las mismas imágenes nuevamente si es necesario
    $(this).val('');
  });

  // Eliminar imagen (evento delegado)
  $(document).off('click', '#simple_previewContainer .remove-image').on('click', '#simple_previewContainer .remove-image', function() {
    const index = parseInt($(this).data('index'));
    const type = $(this).data('type');
    
    Swal.fire({
      title: '¿Eliminar imagen?',
      text: type === 'existente' 
        ? 'Esta imagen se eliminará al guardar el producto' 
        : 'Esta imagen será removida de la selección',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        if (type === 'existente') {
          // Es una imagen existente - marcar para eliminar
          if (!editState.imagenesAEliminar) {
            editState.imagenesAEliminar = [];
          }
          editState.imagenesAEliminar.push(editState.imagenes[index].id);
          editState.imagenes.splice(index, 1);
        } else if (type === 'nueva') {
          // Es una imagen nueva - simplemente remover
          editState.imagenesNuevas.splice(index, 1);
        }
        
        renderImagenesExistentes();
      }
    });
  });

  // Eliminar todas las imágenes
  $('#simple_removeAllImages').off('click').on('click', function() {
    const totalImagenes = editState.imagenes.length + editState.imagenesNuevas.length;
    
    if (totalImagenes === 0) return;
    
    Swal.fire({
      title: '¿Eliminar todas las imágenes?',
      text: 'Se eliminarán todas las imágenes (tanto existentes como nuevas)',
      html: `
        <div class="text-start">
          <p>Se eliminarán:</p>
          <ul>
            ${editState.imagenes.length > 0 ? `<li>${editState.imagenes.length} imágenes existentes</li>` : ''}
            ${editState.imagenesNuevas.length > 0 ? `<li>${editState.imagenesNuevas.length} imágenes nuevas</li>` : ''}
          </ul>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar todas',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545'
    }).then((result) => {
      if (result.isConfirmed) {
        // Marcar todas las imágenes existentes para eliminar
        if (editState.imagenes.length > 0) {
          if (!editState.imagenesAEliminar) {
            editState.imagenesAEliminar = [];
          }
          editState.imagenes.forEach(img => {
            if (img.id) {
              editState.imagenesAEliminar.push(img.id);
            }
          });
        }
        
        // Limpiar todo
        editState.imagenes = [];
        editState.imagenesNuevas = [];
        $('#simple_imagenes').val('');
        
        renderImagenesExistentes();
        
        Swal.fire('Eliminadas', 'Todas las imágenes han sido eliminadas', 'success');
      }
    });
  });
}

function actualizarContadorImagenes(debug = false) {
  // Calcular totales
  const totalExistentes = editState.imagenes ? editState.imagenes.length : 0;
  const totalNuevas = editState.imagenesNuevas ? editState.imagenesNuevas.length : 0;
  const total = totalExistentes + totalNuevas;
  
  if (debug) {
    console.log('=== ACTUALIZAR CONTADOR ===');
    console.log('Imágenes existentes:', editState.imagenes);
    console.log('Imágenes nuevas:', editState.imagenesNuevas);
    console.log(`Total: ${totalExistentes} + ${totalNuevas} = ${total}`);
  }
  
  // Asegurarse de que el elemento de nota exista
  let $noteSmall = $('#simple_imagenes').closest('.card-section').find('.note-small');
  if ($noteSmall.length === 0) {
    // Si no existe en el contenedor, crearlo después del input
    $noteSmall = $('<div class="note-small mt-1"></div>');
    $('#simple_imagenes').after($noteSmall);
  }
  
  // Asegurarse de que el botón eliminar todas exista
  let $removeAllBtn = $('#simple_removeAllImages');
  if ($removeAllBtn.length === 0) {
    $removeAllBtn = $(`
      <button type="button" id="simple_removeAllImages" class="btn btn-sm btn-outline-danger mt-2 d-none">
        <i class="bi bi-trash"></i> Eliminar todas las imágenes
      </button>
    `);
    $noteSmall.after($removeAllBtn);
  }
  
  // Actualizar botón eliminar todas
  if (total > 0) {
    $removeAllBtn.removeClass('d-none');
  } else {
    $removeAllBtn.addClass('d-none');
  }
  
  // Actualizar input y mensaje
  const $input = $('#simple_imagenes');
  const maxImagenes = 6;
  
  if (total >= maxImagenes) {
    // Límite alcanzado
    $input.prop('disabled', true);
    $noteSmall
      .removeClass('text-muted')
      .addClass('text-danger fw-bold bg-transparent')
      .html(`<i class="bi bi-exclamation-triangle me-1"></i> Límite de ${maxImagenes} imágenes alcanzado`);
    
    if (debug) console.log('Límite alcanzado - input deshabilitado');
  } else {
    // Aún hay espacio
    const espacioRestante = maxImagenes - total;
    $input.prop('disabled', false);
    
    // Actualizar atributo multiple para permitir selección múltiple hasta el límite
    $input.attr('multiple', espacioRestante > 1);
    
    $noteSmall
      .removeClass('text-danger fw-bold bg-transparent')
      .addClass('text-muted')
      .html(`
        <i class="bi bi-info-circle me-1"></i>
        Máximo ${maxImagenes} imágenes (${total}/${maxImagenes})
        ${espacioRestante > 0 ? `- Puedes agregar ${espacioRestante} más` : ''}
      `);
    
    if (debug) console.log(`Espacio disponible: ${espacioRestante} imágenes`);
  }
  return total;
}

function setupMiniaturaEvents() {
  const $miniPreview = $('#simple_miniPreview');
  const $miniInput = $('#simple_miniatura');
  const $miniImg = $('#simple_miniImg');
  const $miniPlaceholder = $('#simple_miniPlaceholder');
  const $removeBtn = $('#simple_removeMini');
  const $miniImgSrc = $miniImg.attr('src');

  // Click en el área para seleccionar archivo
  $miniPreview.off('click').on('click', function() {
    $miniInput.click();
  });

  // Cambio en el input de archivo
  $miniInput.off('change').on('change', function(e) {
    const file = e.target.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = function(event) {
      $miniImg.attr('src', event.target.result).show();
      $miniPlaceholder.hide();
      $removeBtn.removeClass('d-none');
      
      // Guardar referencia para enviar
      editState.nuevaMiniatura = file;
    };
    reader.readAsDataURL(file);
  });

  // Eliminar miniatura
  $removeBtn.off('click').on('click', function(e) {
    e.stopPropagation();
    
    Swal.fire({
      title: '¿Eliminar miniatura?',
      text: 'La imagen se eliminará al guardar el producto',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $miniInput.val('');
        $miniImg.hide().attr('src', '');
        $miniPlaceholder.show();
        $removeBtn.addClass('d-none');
        
        // Marcar para eliminar la existente
        editState.eliminarMiniatura = true;
        delete editState.nuevaMiniatura;
      }
    });
  });

  // Si hay imagen existente, configurar para reemplazo
  if ($miniImgSrc) {
    editState.miniaturaOriginal = $miniImgSrc;
  }
}
function setupProgramarRebaja(producto) {
  const $check = $('#simple_programar_rebaja');
  const $fechasDiv = $('#simple_fechas_rebaja');
  const $fechaInicio = $('#simple_fecha_inicio_rebaja');
  const $fechaFin = $('#simple_fecha_fin_rebaja');
  
  // Función para convertir fecha ISO a YYYY-MM-DD
  function formatDateForInput(dateString) {
    if (!dateString) return '';
    
    // Si ya está en formato YYYY-MM-DD, retornar igual
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
      return dateString;
    }
    
    // Si es formato ISO (2025-10-08T00:00:00.000000Z)
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
  }
  
  // Verificar si ya hay fechas programadas
  const fechaInicioFormateada = formatDateForInput(producto.fecha_inicio_rebaja);
  const fechaFinFormateada = formatDateForInput(producto.fecha_fin_rebaja);
  
  const tieneFechaInicio = fechaInicioFormateada !== '';
  const tieneFechaFin = fechaFinFormateada !== '';
  const tieneProgramacionCompleta = tieneFechaInicio && tieneFechaFin;
  
  if (tieneProgramacionCompleta) {
    // Si tiene ambas fechas, activar checkbox y mostrar
    $check.prop('checked', true);
    $fechasDiv.show();
    
    // Establecer valores formateados
    $fechaInicio.val(fechaInicioFormateada);
    $fechaFin.val(fechaFinFormateada);
  } else {
    $check.prop('checked', false);
    $fechasDiv.hide();
    
    $fechaInicio.val('');
    $fechaFin.val('');
    
    producto.fecha_inicio_rebaja = null;
    producto.fecha_fin_rebaja = null;
  }
  
  // Evento para mostrar/ocultar fechas
  $check.off('change').on('change', function() {
    if (this.checked) {
      $fechasDiv.slideDown();
      if (!$fechaInicio.val()) {
        const hoy = new Date();
        const sieteDiasDespues = new Date();
        sieteDiasDespues.setDate(hoy.getDate() + 7);
        
        $fechaInicio.val(formatDateForInput(hoy.toISOString()));
        $fechaFin.val(formatDateForInput(sieteDiasDespues.toISOString()));
      }
    } else {
      $fechasDiv.slideUp();
      // Limpiar fechas al desmarcar
      $fechaInicio.val('');
      $fechaFin.val('');
    }
  });
  
  // Validación: fecha fin no puede ser menor a fecha inicio
  $fechaInicio.off('change').on('change', function() {
    const inicio = new Date(this.value);
    const fin = new Date($fechaFin.val());
    
    if (this.value && $fechaFin.val() && inicio > fin) {
      Swal.fire({
        title: 'Error',
        text: 'La fecha fin no puede ser anterior a la fecha inicio',
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      this.value = '';
    }
  });
  
  $fechaFin.off('change').on('change', function() {
    const inicio = new Date($fechaInicio.val());
    const fin = new Date(this.value);
    
    if (this.value && $fechaInicio.val() && fin < inicio) {
      Swal.fire({
        title: 'Error',
        text: 'La fecha fin no puede ser anterior a la fecha inicio',
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      this.value = '';
    }
  });
}
// Renderiza categorías y subcategorías
function renderCategoriasSimple(categoriaId, subcategoriaId) {
  const catSelect = $('#simple_categoria');
  const subcatDiv = $('#simple_subcategorias');

  // Llenar categorías
  catSelect.html('<option value="">-- Seleccione --</option>');
  _CATEGORIAS.forEach(cat => {
    catSelect.append(`<option value="${cat.id}" ${cat.id == categoriaId ? 'selected' : ''}>${cat.nombre}</option>`);
  });

  // Mostrar subcategorías
  function loadSubcategorias(catId, selectedSubId) {
    subcatDiv.html('');
    const categoria = _CATEGORIAS.find(c => c.id == catId);
    if (!categoria || !categoria.subcategorias || categoria.subcategorias.length === 0) {
      subcatDiv.html('<span class="text-muted">Sin subcategorías</span>');
      return;
    }

    categoria.subcategorias.forEach(sc => {
      const isChecked = sc.id == selectedSubId ? 'checked' : '';
      subcatDiv.append(`
        <div class="form-check">
          <input class="form-check-input" type="radio" name="subcategoria_id" value="${sc.id}" id="subcat_${sc.id}" ${isChecked}>
          <label class="form-check-label" for="subcat_${sc.id}">${sc.nombre}</label>
        </div>
      `);
    });
  }

  // Cargar subcategorías iniciales
  if (categoriaId) {
    loadSubcategorias(categoriaId, subcategoriaId);
  } else {
    subcatDiv.html('<span class="text-muted">Seleccione una categoría primero</span>');
  }

  // Evento cambio de categoría
  catSelect.off('change').on('change', function () {
    loadSubcategorias(this.value, null);
  });
}

// --- VARIABLE ---
function abrirModalVariable(producto) {
  const cont = document.getElementById('variable_variaciones_container');
  cont.innerHTML = '';

  producto.variaciones.forEach(v => {
    cont.innerHTML += `
      <div class="border rounded p-3 mb-3">
        <h6>Variación SKU: ${v.sku || '-'}</h6>
        <label>Precio Regular</label>
        <input type="number" class="form-control mb-2" value="${v.precio_regular}">
        <label>Precio Rebajado</label>
        <input type="number" class="form-control mb-2" value="${v.precio_rebajado || ''}">
      </div>
    `;
  });

  const modal = new bootstrap.Modal(document.getElementById('modalProductoVariable'));
  modal.show();
}

// --- AGRUPADO ---
function abrirModalAgrupado(producto) {
  const list = document.getElementById('agrupado_hijos');
  list.innerHTML = '';

  producto.productos_agrupados.forEach(ag => {
    list.innerHTML += `<li>${ag.producto_hijo.nombre} - Precio: ${ag.producto_hijo.precio_regular}</li>`;
  });

  const modal = new bootstrap.Modal(document.getElementById('modalProductoAgrupado'));
  modal.show();
}