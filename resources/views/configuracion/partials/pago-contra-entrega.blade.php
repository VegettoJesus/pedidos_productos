<template id="template-contra_entrega">
    <form id="formMetodo" data-slug="contra_entrega">
        @csrf
        <input type="hidden" name="id" value="">
        <input type="hidden" name="opcion" value="Actualizar">

        <div class="row g-3">

            {{-- ============================================
                 HEADER DEL MÉTODO
            ============================================ --}}
            <div class="col-12">
                <div class="config-header-contra-entrega">
                    <div class="config-header-row">
                        <div class="config-header-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="config-header-text">
                            <h5 class="mb-0 text-white">Pago contra entrega</h5>
                            <small class="text-white-50">Configura cómo el cliente pagará al recibir su pedido</small>
                        </div>
                        <div class="config-header-switch">
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" name="activo" id="activoCE">
                                <label class="form-check-label text-white" for="activoCE">
                                    <span class="estado-badge">Inactivo</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================
                 SECCIÓN 1: INFORMACIÓN BÁSICA
            ============================================ --}}
            <div class="col-12">
                <div class="config-section">
                    <div class="config-section-header">
                        <div class="config-section-icon bg-primary-soft">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Información básica</h6>
                            <small class="text-muted">Datos que verá el cliente al elegir este método</small>
                        </div>
                    </div>
                    <div class="config-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Título
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Nombre que verá el cliente al elegir el método de pago"></i>
                                </label>
                                <input type="text" class="form-control" name="titulo" required 
                                       placeholder="Ej: Pago contra entrega">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Desactivar si el importe es mayor o igual a
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Si el total del carrito supera este monto, el método se desactiva automáticamente"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="desactivar_si_importe_mayor" placeholder="0.00">
                                </div>
                                <small class="text-muted">Dejar vacío para no aplicar límite</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Descripción
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Texto breve que se muestra debajo del título"></i>
                                </label>
                                <textarea class="form-control" name="descripcion_cfg" rows="2" 
                                          placeholder="Ej: Paga en efectivo al recibir tu pedido en la puerta de tu casa"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Instrucciones
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Indicaciones adicionales que verá el cliente después de confirmar el pedido"></i>
                                </label>
                                <textarea class="form-control" name="instrucciones" rows="3" 
                                          placeholder="Ej: Ten el monto exacto preparado, el repartidor no maneja cambio"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Mensaje cuando no está disponible
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Mensaje que verá el cliente si este método no aplica para su carrito"></i>
                                </label>
                                <input type="text" class="form-control" name="mensaje_no_disponible" 
                                       placeholder="Este método de pago no está disponible para tu pedido.">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================
                 SECCIÓN 2: RESTRICCIÓN POR CATEGORÍA
            ============================================ --}}
            <div class="col-12">
                <div class="config-section">
                    <div class="config-section-header">
                        <div class="config-section-icon bg-warning-soft">
                            <i class="bi bi-tags"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Restricción por categoría</h6>
                            <small class="text-muted">Desactiva este método según las categorías del carrito</small>
                        </div>
                    </div>
                    <div class="config-section-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label mb-2">
                                    Modo de restricción
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                    data-bs-toggle="tooltip" 
                                    title="Define cuándo se debe aplicar la restricción por categoría"></i>
                                </label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="restriccion_categoria_modo" 
                                            id="catModoNinguno" value="ninguno">
                                        <label class="modo-card" for="catModoNinguno">
                                            <i class="bi bi-unlock-fill text-success"></i>
                                            <div class="modo-titulo">Sin restricción</div>
                                            <div class="modo-desc">El método siempre estará disponible</div>
                                        </label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="restriccion_categoria_modo" 
                                            id="catModoAlMenosUno" value="al_menos_uno">
                                        <label class="modo-card" for="catModoAlMenosUno">
                                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                            <div class="modo-titulo">Al menos uno</div>
                                            <div class="modo-desc">Se desactiva si algún producto está en las categorías</div>
                                        </label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="restriccion_categoria_modo" 
                                            id="catModoTodos" value="todos">
                                        <label class="modo-card" for="catModoTodos">
                                            <i class="bi bi-x-octagon-fill text-danger"></i>
                                            <div class="modo-titulo">Todos</div>
                                            <div class="modo-desc">Se desactiva solo si TODOS están en las categorías</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="buscador-wrapper disabled" data-tipo="categoria">
                                    <label class="form-label">
                                        Categorías a restringir
                                        <i class="bi bi-question-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           title="Busca y selecciona las categorías. Solo se activa en modo 'Al menos un producto'"></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control buscador-input" 
                                               placeholder="Busca una categoría por nombre..." 
                                               autocomplete="off"
                                               disabled>
                                    </div>
                                    <div class="buscador-resultados"></div>
                                    <div class="chips-container mt-2"></div>
                                    <input type="hidden" name="categorias_desactivar" class="hidden-ids" value="[]">
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-lightbulb"></i>
                                        Escribe al menos 2 letras para buscar.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================
                 SECCIÓN 3: RESTRICCIÓN POR PRODUCTO
            ============================================ --}}
            <div class="col-12">
                <div class="config-section">
                    <div class="config-section-header">
                        <div class="config-section-icon bg-info-soft">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Restricción por producto</h6>
                            <small class="text-muted">Desactiva este método según productos específicos</small>
                        </div>
                    </div>
                    <div class="config-section-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label mb-2">
                                    Modo de restricción
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                    data-bs-toggle="tooltip" 
                                    title="Define cuándo se debe aplicar la restricción por producto"></i>
                                </label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="restriccion_producto_modo" 
                                            id="prodModoNinguno" value="ninguno">
                                        <label class="modo-card" for="prodModoNinguno">
                                            <i class="bi bi-unlock-fill text-success"></i>
                                            <div class="modo-titulo">Sin restricción</div>
                                            <div class="modo-desc">El método siempre estará disponible</div>
                                        </label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="restriccion_producto_modo" 
                                            id="prodModoAlMenosUno" value="al_menos_uno">
                                        <label class="modo-card" for="prodModoAlMenosUno">
                                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                            <div class="modo-titulo">Al menos uno</div>
                                            <div class="modo-desc">Se desactiva si algún producto está en la lista</div>
                                        </label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="restriccion_producto_modo" 
                                            id="prodModoTodos" value="todos">
                                        <label class="modo-card" for="prodModoTodos">
                                            <i class="bi bi-x-octagon-fill text-danger"></i>
                                            <div class="modo-titulo">Todos</div>
                                            <div class="modo-desc">Se desactiva solo si TODOS están en la lista</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="buscador-wrapper disabled" data-tipo="producto">
                                    <label class="form-label">
                                        Productos a restringir
                                        <i class="bi bi-question-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           title="Busca por nombre o SKU. Solo se activa en modo 'Al menos un producto'"></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control buscador-input" 
                                               placeholder="Busca por nombre o SKU..." 
                                               autocomplete="off"
                                               disabled>
                                    </div>
                                    <div class="buscador-resultados"></div>
                                    <div class="chips-container mt-2"></div>
                                    <input type="hidden" name="productos_desactivar" class="hidden-ids" value="[]">
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-lightbulb"></i>
                                        Escribe al menos 2 letras para buscar.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================
                 SECCIÓN 4: CARGO ADICIONAL
            ============================================ --}}
            <div class="col-12">
                <div class="config-section">
                    <div class="config-section-header">
                        <div class="config-section-icon bg-success-soft">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Cargo adicional</h6>
                            <small class="text-muted">Cobra un monto extra cuando se elija este método</small>
                        </div>
                    </div>
                    <div class="config-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Tipo de cargo
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Porcentaje: % del subtotal. Importe fijo: monto exacto en soles"></i>
                                </label>
                                <select class="form-select" name="cargo_tipo">
                                    <option value="porcentaje">Porcentaje (%)</option>
                                    <option value="fijo">Importe fijo (S/)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Valor del cargo
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Monto que se sumará al total del pedido"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text cargo-prefix">%</span>
                                    <input type="number" step="0.01" class="form-control" name="cargo_valor" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Desactivar cargo si importe ≥ a
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Si el total supera este monto, no se aplicará el cargo adicional"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" class="form-control" 
                                           name="cargo_desactivar_si_importe_mayor" placeholder="0.00">
                                </div>
                                <small class="text-muted">Dejar vacío para aplicar siempre</small>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="impuesto_activo" id="impuesto_activo">
                                    <label class="form-check-label" for="impuesto_activo">
                                        Aplicar impuesto sobre el cargo adicional
                                        <i class="bi bi-question-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           title="Si se activa, se sumará IGV u otro impuesto al cargo"></i>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Porcentaje de impuesto
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Ej: 18 para IGV"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">%</span>
                                    <input type="number" step="0.01" class="form-control" name="impuesto_porcentaje" value="0">
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">
                                    Incluir en el cálculo del cargo
                                    <i class="bi bi-question-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       title="Define qué se suma al total antes de calcular el cargo adicional"></i>
                                </label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="incluir_impuestos" id="incluir_impuestos">
                                        <label class="form-check-label" for="incluir_impuestos">
                                            <i class="bi bi-receipt me-1"></i>Impuestos
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="incluir_envio" id="incluir_envio">
                                        <label class="form-check-label" for="incluir_envio">
                                            <i class="bi bi-truck me-1"></i>Gastos de envío
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================
                 BOTÓN GUARDAR (sticky)
            ============================================ --}}
            <div class="col-12">
                <div class="config-footer-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-lg me-1"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </form>
</template>