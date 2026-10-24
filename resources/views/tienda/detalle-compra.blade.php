<div class="pedido-detalle-page">
    <div class="pedido-detalle-header">
        <div class="pedido-detalle-header-left">
            <h1><i class="bi bi-bag-check"></i> Detalle del Pedido</h1>
            <div class="pedido-codigo-grande">{{ $pedido->codigo }}</div>
            <div class="pedido-fecha-grande">
                <i class="bi bi-calendar-event"></i>
                {{ $pedido->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
        <div class="pedido-detalle-header-right">
            @php
                $estadoConfig = [
                    'pendiente'          => ['label' => 'Pendiente',         'class' => 'warning', 'icon' => 'bi-clock'],
                    'pago_en_revision'   => ['label' => 'Pago en revisión',  'class' => 'info',    'icon' => 'bi-hourglass-split'],
                    'pagado'             => ['label' => 'Pagado',            'class' => 'success', 'icon' => 'bi-check-circle'],
                    'en_preparacion'     => ['label' => 'En preparación',    'class' => 'info',    'icon' => 'bi-box-seam'],
                    'enviado'            => ['label' => 'Enviado',           'class' => 'primary', 'icon' => 'bi-truck'],
                    'entregado'          => ['label' => 'Entregado',         'class' => 'success', 'icon' => 'bi-check-circle-fill'],
                    'cancelado'          => ['label' => 'Cancelado',         'class' => 'danger',  'icon' => 'bi-x-circle'],
                    'reembolsado'        => ['label' => 'Reembolsado',       'class' => 'secondary','icon' => 'bi-arrow-counterclockwise'],
                    'rechazado'          => ['label' => 'Rechazado',         'class' => 'danger',  'icon' => 'bi-x-octagon'],
                ];
                $estadoInfo = $estadoConfig[$pedido->estado] ?? ['label' => $pedido->estado, 'class' => 'secondary', 'icon' => 'bi-question-circle'];
            @endphp
            <span class="badge-estado-grande badge-{{ $estadoInfo['class'] }}">
                <i class="bi {{ $estadoInfo['icon'] }}"></i> {{ $estadoInfo['label'] }}
            </span>
        </div>
    </div>
    <div class="pedido-detalle-grid">
        <div class="pedido-detalle-col">
            <div class="detalle-card">
                <h3 class="detalle-card-titulo"><i class="bi bi-box-seam"></i> Productos</h3>
                <div class="detalle-items-lista">
                    @foreach($pedido->items as $item)
                        @php
                            $cantidadReembolsada = 0;
                            
                            if ($pedido->reembolsos && $pedido->reembolsos->isNotEmpty()) {
                                foreach ($pedido->reembolsos as $reembolso) {
                                    $itemsArray = $reembolso->items_reembolsados;
                                    
                                    // Si por alguna razón viene como string JSON, lo decodificamos
                                    if (is_string($itemsArray)) {
                                        $itemsArray = json_decode($itemsArray, true);
                                    }
                                    
                                    if (is_array($itemsArray)) {
                                        foreach ($itemsArray as $ri) {
                                            // Validamos que el item_id coincida
                                            if (isset($ri['item_id']) && $ri['item_id'] == $item->id) {
                                                $cantidadReembolsada += (int) ($ri['cantidad'] ?? 0);
                                            }
                                        }
                                    }
                                }
                            }
                            
                            $esReembolsoTotal = ($cantidadReembolsada >= $item->cantidad);
                            $esReembolsoParcial = ($cantidadReembolsada > 0 && $cantidadReembolsada < $item->cantidad);
                        @endphp

                        <div class="detalle-item" style="{{ $esReembolsoTotal ? 'opacity: 0.6; background: #f8f9fa;' : '' }}">
                            <div class="detalle-item-img">
                                @if($item->imagen_path)
                                    <img src="{{ asset($item->imagen_path) }}" alt="{{ $item->nombre_producto }}">
                                @else
                                    <i class="bi bi-box-seam"></i>
                                @endif
                            </div>
                            <div class="detalle-item-info">
                                <div class="detalle-item-nombre" style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    {{ $item->nombre_producto }}
                                    @if($esReembolsoTotal)
                                        <span class="badge-reembolso badge-reembolso-total">
                                            Reembolsado
                                        </span>
                                    @elseif($esReembolsoParcial)
                                        <span class="badge-reembolso badge-reembolso-parcial">
                                            Reembolso Parcial ({{ $cantidadReembolsada }} de {{ $item->cantidad }})
                                        </span>
                                    @endif
                                </div>
                                @if($item->sku)
                                    <div class="detalle-item-sku"><i class="bi bi-upc"></i> {{ $item->sku }}</div>
                                @endif
                                @if($item->atributos->isNotEmpty())
                                    <div class="detalle-item-atributos">
                                        @foreach($item->atributos as $attr)
                                            <span class="chip-atributo">
                                                {{ $attr->atributo_nombre }}: <strong>{{ $attr->termino_nombre }}</strong>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="detalle-item-cantidad">
                                    Cantidad: <strong>{{ $item->cantidad }}</strong>
                                </div>
                                
                                @if($item->descuento_porcentaje > 0 && $item->precio_unitario_rebajado)
                                    <div class="detalle-item-ahorro" style="font-size: 0.8rem; color: #28a745; margin-top: 4px;">
                                        <i class="bi bi-tag-fill"></i> Ahorraste S/ {{ number_format(($item->precio_unitario_regular - $item->precio_unitario_final) * $item->cantidad, 2) }} 
                                        ({{ round($item->descuento_porcentaje) }}% dcto.)
                                    </div>
                                @endif
                            </div>
                            <div class="detalle-item-precios" style="display: flex; flex-direction: column; align-items: flex-end;">
                                
                                @if($item->descuento_porcentaje > 0 && $item->precio_unitario_rebajado)
                                    <div class="detalle-item-precio-regular" style="font-size: 0.75rem; text-decoration: line-through; color: #999; margin-bottom: 2px;">
                                        S/ {{ number_format($item->precio_unitario_regular, 2) }} c/u
                                    </div>
                                @endif

                                <div class="detalle-item-precio-unit">
                                    S/ {{ number_format($item->precio_unitario_final, 2) }} c/u
                                </div>
                                <div class="detalle-item-subtotal" style="font-weight: 700; color: #f4ab27; margin-top: 2px;">
                                    S/ {{ number_format($item->subtotal_linea, 2) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($pedido->historial->isNotEmpty())
                <div class="detalle-card">
                    <h3 class="detalle-card-titulo"><i class="bi bi-clock-history"></i> Historial del pedido</h3>
                    <div class="timeline">
                        @foreach($pedido->historial as $h)
                            @php
                                $mostrarEvento = true;
                                if ($h->tipo_cambio === 'evidencia') {
                                    $evidenciaId = $h->metadata['evidencia_id'] ?? null;
                                    $evidencia = $evidenciaId ? $pedido->evidencias->find($evidenciaId) : null;
                                    
                                    // Si la evidencia no existe o no es visible, ocultamos el evento
                                    if (!$evidencia || !$evidencia->visible_cliente) {
                                        $mostrarEvento = false;
                                    }
                                }

                                if ($h->tipo_cambio === 'nota' || $h->estado_nuevo === 'nota') {
                                    $mostrarEvento = false;
                                }

                                $icono = 'bi-arrow-repeat';
                                $titulo = 'Actualización';
                                $colorClass = '';

                                if ($h->tipo_cambio === 'estado_pago') {
                                    $icono = 'bi-credit-card';
                                    $titulo = 'Estado de Pago:';
                                    $colorClass = 'text-success';
                                } elseif ($h->tipo_cambio === 'estado_envio') {
                                    $icono = 'bi-truck';
                                    $titulo = 'Estado de Envío:';
                                    $colorClass = 'text-primary';
                                } elseif ($h->tipo_cambio === 'evidencia') {
                                    $icono = 'bi-file-earmark-image';
                                    $titulo = 'Evidencia Subida:';
                                } elseif ($h->tipo_cambio === 'reembolso') {
                                    $icono = 'bi-cash-coin';
                                    $titulo = 'Reembolso:';
                                    $colorClass = 'text-danger';
                                } elseif ($h->tipo_cambio === 'estado') {
                                    $icono = 'bi-info-circle';
                                    $titulo = 'Cambio de Estado:';
                                }
                            @endphp

                            @if($mostrarEvento)
                                <div class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="bi {{ $icono }}"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">                    
                                            <span class="{{ $colorClass }}">{{ $titulo }}</span>
                                            <span class="timeline-estado">{{ ucfirst(str_replace('_', ' ', $h->estado_nuevo)) }}</span>
                                        </div>
                                        @if($h->motivo)
                                            <div class="timeline-motivo">{{ $h->motivo }}</div>
                                        @endif
                                        <div class="timeline-fecha">
                                            <i class="bi bi-clock"></i>
                                            {{ $h->created_at->format('d/m/Y H:i') }}
                                            @if($h->user)
                                                <span class="timeline-user">
                                                    <i class="bi bi-person"></i> {{ $h->user->nombres }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- EVIDENCIAS DE ENVÍO --}}
            @if($pedido->evidencias->where('visible_cliente', true)->isNotEmpty())
                <div class="detalle-card">
                    <h3 class="detalle-card-titulo"><i class="bi bi-images"></i> Evidencias de envío</h3>
                    <div class="evidencias-grid">
                        @foreach($pedido->evidencias->where('visible_cliente', true) as $ev)
                            <div class="evidencia-item">
                                <div class="evidencia-img"
                                    @if(str_starts_with($ev->mime_type ?? '', 'image/'))
                                        data-bs-toggle="modal"
                                        data-bs-target="#evidenciaZoomModal"
                                        data-img-src="{{ asset($ev->archivo_path) }}"
                                        data-img-title="{{ ucfirst(str_replace('_', ' ', $ev->tipo_evidencia)) }}"
                                    @endif
                                >
                                    @if(str_starts_with($ev->mime_type ?? '', 'image/'))
                                        <img src="{{ asset($ev->archivo_path) }}" alt="Evidencia" loading="lazy">
                                    @else
                                        <a href="{{ asset($ev->archivo_path) }}" target="_blank" rel="noopener">
                                            <i class="bi bi-file-earmark"></i> Ver archivo
                                        </a>
                                    @endif
                                </div>
                                <div class="evidencia-info">
                                    <div class="evidencia-tipo">
                                        <i class="bi bi-tag"></i> {{ ucfirst(str_replace('_', ' ', $ev->tipo_evidencia)) }}
                                    </div>
                                    @if($ev->descripcion)
                                        <div class="evidencia-desc">{{ $ev->descripcion }}</div>
                                    @endif
                                    <div class="evidencia-fecha">
                                        {{ $ev->capturada_en ? $ev->capturada_en->format('d/m/Y') : $ev->created_at->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="pedido-detalle-col-sidebar">
            <div class="detalle-card">
                <h3 class="detalle-card-titulo"><i class="bi bi-receipt"></i> Resumen de montos</h3>
                <div class="resumen-montos">
                    <div class="resumen-monto-line">
                        <span>Subtotal</span>
                        <span>S/ {{ number_format($pedido->subtotal, 2) }}</span>
                    </div>
                    @if($pedido->costo_envio > 0)
                        <div class="resumen-monto-line">
                            <span>Envío</span>
                            <span>S/ {{ number_format($pedido->costo_envio, 2) }}</span>
                        </div>
                    @endif
                    @if($pedido->cargo_metodo_pago > 0)
                        <div class="resumen-monto-line">
                            <span>Cargo por método de pago</span>
                            <span>S/ {{ number_format($pedido->cargo_metodo_pago, 2) }}</span>
                        </div>
                    @endif
                    @if($pedido->impuesto_cargo > 0)
                        <div class="resumen-monto-line">
                            <span>Impuesto del cargo</span>
                            <span>S/ {{ number_format($pedido->impuesto_cargo, 2) }}</span>
                        </div>
                    @endif
                    @if($pedido->descuento_total > 0)
                        <div class="resumen-monto-line resumen-descuento">
                            <span><i class="bi bi-tag-fill"></i> Descuento</span>
                            <span>- S/ {{ number_format($pedido->descuento_total, 2) }}</span>
                        </div>
                    @endif

                    @if($pedido->reembolsos->isNotEmpty())
                        @php $totalReembolsado = $pedido->reembolsos->sum('monto_total'); @endphp
                        <div class="resumen-monto-line resumen-reembolso" style="color: #0d6efd; font-weight: 600;">
                            <span><i class="bi bi-arrow-counterclockwise"></i> Reembolso</span>
                            <span>- S/ {{ number_format($totalReembolsado, 2) }}</span>
                        </div>
                    @endif

                    <div class="resumen-monto-total">
                        <span>Total</span>
                        <span>S/ {{ number_format($pedido->total, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="detalle-card">
                <h3 class="detalle-card-titulo"><i class="bi bi-credit-card"></i> Método de pago</h3>
                <div class="info-pago">
                    <div class="info-pago-nombre">
                        <i class="bi bi-wallet2"></i>
                        {{ $pedido->metodo_pago_nombre }}
                    </div>
                    
                    @php
                        $ultimoPago = $pedido->historial->where('tipo_cambio', 'estado_pago')->first();
                    @endphp
                    @if($ultimoPago)
                        <div class="info-pago-evento" style="font-size: 0.8rem; color: #666; margin-bottom: 0.5rem;">
                            <i class="bi bi-clock-history"></i> Último movimiento: 
                            <strong>{{ ucfirst(str_replace('_', ' ', $ultimoPago->estado_nuevo)) }}</strong>
                            <br><small>{{ $ultimoPago->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                    @endif

                    <div class="info-pago-estado">
                        @php
                            $pagoConfig = [
                                'no_pagado'                => ['label' => 'No pagado',              'class' => 'danger'],
                                'pendiente_verificacion'   => ['label' => 'Pendiente de verificación', 'class' => 'warning'],
                                'pagado'                   => ['label' => 'Pagado',                'class' => 'success'],
                                'parcial'                  => ['label' => 'Pago parcial',          'class' => 'info'],
                                'reembolsado'              => ['label' => 'Reembolsado',           'class' => 'secondary'],
                            ];
                            $pagoInfo = $pagoConfig[$pedido->estado_pago] ?? ['label' => $pedido->estado_pago, 'class' => 'secondary'];
                        @endphp
                        <span class="badge-pago badge-{{ $pagoInfo['class'] }}">
                            {{ $pagoInfo['label'] }}
                        </span>
                    </div>
                    @if($pedido->voucher_path)
                        <div class="info-pago-voucher">
                            <a href="{{ asset('vouchers/' . $pedido->voucher_path) }}" target="_blank" class="btn-ver-voucher">
                                <i class="bi bi-receipt"></i> Ver comprobante subido
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="detalle-card">
                <h3 class="detalle-card-titulo">
                    <i class="bi {{ $pedido->tipo_entrega === 'delivery' ? 'bi-truck' : 'bi-shop' }}"></i>
                    {{ $pedido->tipo_entrega === 'delivery' ? 'Envío a domicilio' : 'Recojo en tienda' }}
                </h3>
                <div class="info-entrega">
                    @if($pedido->tipo_entrega === 'delivery')
                        <div class="info-entrega-linea">
                            <i class="bi bi-geo-alt"></i>
                            <span>{{ $pedido->direccion_entrega }}</span>
                        </div>
                        <div class="info-entrega-linea">
                            <i class="bi bi-map"></i>
                            <span>
                                {{ $pedido->distrito_nombre }}, 
                                {{ $pedido->provincia_nombre }}, 
                                {{ $pedido->departamento_nombre }}
                            </span>
                        </div>
                        @if($pedido->referencia_entrega)
                            <div class="info-entrega-linea">
                                <i class="bi bi-info-circle"></i>
                                <span>{{ $pedido->referencia_entrega }}</span>
                            </div>
                        @endif
                        @php
                            $ultimoEstadoEnvio = $pedido->historial
                                ->where('tipo_cambio', 'estado_envio')
                                ->first();
                        @endphp

                        @if($ultimoEstadoEnvio)
                            @php
                                // Asignamos texto, color de fondo e ícono según el estado
                                $envioConfig = [
                                    'en_preparacion' => ['label' => 'En preparación', 'bg' => '#cff4fc', 'text' => '#055160', 'icon' => 'bi-box-seam'],
                                    'enviado'        => ['label' => 'Enviado',        'bg' => '#cfe2ff', 'text' => '#084298', 'icon' => 'bi-truck'],
                                    'en_transito'    => ['label' => 'En tránsito',    'bg' => '#cfe2ff', 'text' => '#084298', 'icon' => 'bi-truck'],
                                    'entregado'      => ['label' => 'Entregado',      'bg' => '#d1e7dd', 'text' => '#0a3622', 'icon' => 'bi-check-circle-fill'],
                                ];
                                
                                $infoEnvio = $envioConfig[$ultimoEstadoEnvio->estado_nuevo] ?? [
                                    'label' => ucfirst(str_replace('_', ' ', $ultimoEstadoEnvio->estado_nuevo)), 
                                    'bg' => '#e2e3e5', 
                                    'text' => '#383d41', 
                                    'icon' => 'bi-question-circle'
                                ];
                            @endphp
                            <div class="info-entrega-linea" style="margin-top: 10px; align-items: center;">
                                <i class="bi bi-truck" style="font-size: 1.2rem;"></i>
                                <span>
                                    Estado del envío: 
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background-color: {{ $infoEnvio['bg'] }}; color: {{ $infoEnvio['text'] }}; font-size: 0.85rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; margin-left: 5px; white-space: nowrap;">
                                        <i class="bi {{ $infoEnvio['icon'] }}"></i> {{ $infoEnvio['label'] }}
                                    </span>
                                </span>
                            </div>
                        @endif
                        @if($pedido->codigo_tracking)
                            <div class="info-entrega-linea">
                                <i class="bi bi-upc-scan"></i>
                                <span>Tracking: <strong>{{ $pedido->codigo_tracking }}</strong></span>
                            </div>
                        @endif
                    @else
                        <div class="info-entrega-linea">
                            <i class="bi bi-shop"></i>
                            <span>{{ $pedido->punto_retiro }}</span>
                        </div>
                        <div class="info-entrega-linea">
                            <i class="bi bi-info-circle"></i>
                            <span>Presenta tu código de pedido al momento de recoger.</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="detalle-card">
                <h3 class="detalle-card-titulo"><i class="bi bi-person"></i> Datos del cliente</h3>
                <div class="info-cliente">
                    <div class="info-cliente-linea">
                        <i class="bi bi-person-circle"></i>
                        <span>{{ $pedido->cliente_nombres }} {{ $pedido->cliente_apellidos }}</span>
                    </div>
                    <div class="info-cliente-linea">
                        <i class="bi bi-envelope"></i>
                        <span>{{ $pedido->cliente_email }}</span>
                    </div>
                    @if($pedido->cliente_celular)
                        <div class="info-cliente-linea">
                            <i class="bi bi-telephone"></i>
                            <span>{{ $pedido->cliente_celular }}</span>
                        </div>
                    @endif
                    @if($pedido->cliente_documento_numero)
                        <div class="info-cliente-linea">
                            <i class="bi bi-card-text"></i>
                            <span>{{ $pedido->cliente_documento_tipo }}: {{ $pedido->cliente_documento_numero }}</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="detalle-acciones">
                <a href="{{ route('perfil.mis-compras') }}" class="btn-volver-pedidos">
                    <i class="bi bi-arrow-left"></i> Volver a mis compras
                </a>
            </div>

        </div>
    </div>
</div>
<div class="modal fade evidencia-zoom-modal" id="evidenciaZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <button type="button"
                    class="evidencia-zoom-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>

            <div class="modal-body">
                <img id="evidenciaZoomImg" src="" alt="Evidencia ampliada">
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('evidenciaZoomModal');
        if (!modal) return;

        const imgEl = document.getElementById('evidenciaZoomImg');

        modal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;

            imgEl.src = trigger.getAttribute('data-img-src');
            imgEl.classList.remove('zoomed');
        });

        imgEl.addEventListener('click', function () {
            this.classList.toggle('zoomed');
        });

        modal.addEventListener('hidden.bs.modal', function () {
            imgEl.src = '';
            imgEl.classList.remove('zoomed');
        });
    });
</script>