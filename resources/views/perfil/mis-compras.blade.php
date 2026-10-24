<div class="mis-compras-page">
    <div class="mis-compras-header">
        <h1><i class="bi bi-bag-check"></i> Mis pedidos</h1>
        <p>Revisa el estado de tus compras y descarga tus comprobantes</p>
    </div>

    @if($pedidos->isEmpty())
        <div class="empty-state">
            <i class="bi bi-bag-x"></i>
            <h3>Aún no tienes pedidos</h3>
            <p>Cuando realices tu primera compra aparecerá aquí.</p>
            <a href="{{ route('tienda.home') }}" class="btn-primary-custom">
                <i class="bi bi-shop"></i> Ir a la tienda
            </a>
        </div>
    @else
        <div class="pedidos-list">
            @foreach($pedidos as $pedido)
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div>
                            <div class="pedido-codigo">{{ $pedido->codigo }}</div>
                            <div class="pedido-fecha">
                                <i class="bi bi-calendar-event"></i>
                                {{ $pedido->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="pedido-estado">
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
                            <span class="badge-estado badge-{{ $estadoInfo['class'] }}">
                                <i class="bi {{ $estadoInfo['icon'] }}"></i> {{ $estadoInfo['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="pedido-body">
                        <div class="pedido-items">
                            @foreach($pedido->items->take(3) as $item)
                                <div class="pedido-item-mini">
                                    <div class="pedido-item-mini-img">
                                        @if($item->imagen_path)
                                            <img src="{{ asset($item->imagen_path) }}" alt="{{ $item->nombre_producto }}">
                                        @else
                                            <i class="bi bi-box-seam"></i>
                                        @endif
                                    </div>
                                    <div class="pedido-item-mini-info">
                                        <div class="pedido-item-mini-nombre">{{ $item->nombre_producto }}</div>
                                        <div class="pedido-item-mini-cant">x{{ $item->cantidad }}</div>
                                        
                                        {{-- 🔥 MOSTRAR EL AHORRO POR PRODUCTO --}}
                                        @if($item->descuento_porcentaje > 0 && $item->precio_unitario_rebajado)
                                            <div class="pedido-item-mini-ahorro" style="font-size: 0.75rem; color: #28a745; margin-top: 2px;">
                                                <i class="bi bi-tag-fill"></i> Ahorraste S/ {{ number_format(($item->precio_unitario_regular - $item->precio_unitario_final) * $item->cantidad, 2) }} 
                                                ({{ round($item->descuento_porcentaje) }}% dcto.)
                                            </div>
                                        @endif
                                    </div>
                                    <div class="pedido-item-mini-subtotal" style="text-align: right;">
                                        {{-- 🔥 MOSTRAR PRECIO TACHADO SI HUBO DESCUENTO --}}
                                        @if($item->descuento_porcentaje > 0 && $item->precio_unitario_rebajado)
                                            <div style="font-size: 0.75rem; text-decoration: line-through; color: #999;">
                                                S/ {{ number_format($item->subtotal_linea + (($item->precio_unitario_regular - $item->precio_unitario_final) * $item->cantidad), 2) }}
                                            </div>
                                        @endif
                                        <div>S/ {{ number_format($item->subtotal_linea, 2) }}</div>
                                    </div>
                                </div>
                            @endforeach

                            @if($pedido->items->count() > 3)
                                <div class="pedido-item-more">
                                    +{{ $pedido->items->count() - 3 }} producto(s) más
                                </div>
                            @endif
                        </div>

                        <div class="pedido-totales">
                            <div class="pedido-total-line">
                                <span>Subtotal</span>
                                <span>S/ {{ number_format($pedido->subtotal, 2) }}</span>
                            </div>
                            @if($pedido->costo_envio > 0)
                                <div class="pedido-total-line">
                                    <span>Envío</span>
                                    <span>S/ {{ number_format($pedido->costo_envio, 2) }}</span>
                                </div>
                            @endif
                            @if($pedido->cargo_metodo_pago > 0)
                                <div class="pedido-total-line">
                                    <span>Cargo por pago</span>
                                    <span>S/ {{ number_format($pedido->cargo_metodo_pago, 2) }}</span>
                                </div>
                            @endif
                            @if($pedido->impuesto_cargo > 0)
                                <div class="pedido-total-line">
                                    <span>Impuesto cargo</span>
                                    <span>S/ {{ number_format($pedido->impuesto_cargo, 2) }}</span>
                                </div>
                            @endif
                            @if($pedido->descuento_total > 0)
                                <div class="pedido-total-line pedido-descuento text-success">
                                    <span><i class="bi bi-tag-fill"></i> Descuento</span>
                                    <span>- S/ {{ number_format($pedido->descuento_total, 2) }}</span>
                                </div>
                            @endif
                            @if($pedido->estado === 'reembolsado' && $pedido->reembolsos->isNotEmpty())
                                @php $totalReembolsado = $pedido->reembolsos->sum('monto_total'); @endphp
                                <div class="pedido-total-line pedido-reembolso">
                                    <span><i class="bi bi-arrow-counterclockwise"></i> Reembolso</span>
                                    <span>- S/ {{ number_format($totalReembolsado, 2) }}</span>
                                </div>
                            @endif
                            <div class="pedido-total-final">
                                <span>Total</span>
                                <span>S/ {{ number_format($pedido->total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pedido-footer">
                        <div class="pedido-info-pago">
                            <i class="bi bi-credit-card"></i>
                            {{ $pedido->metodo_pago_nombre }}
                        </div>
                        <div class="pedido-info-entrega">
                            <i class="bi bi-truck"></i>
                            {{ ucfirst($pedido->tipo_entrega) }}
                        </div>
                        <a href="{{ route('pedido.detalle', $pedido->id) }}" class="btn-pedido-detalle">
                            Ver detalle <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pagination-wrapper">
            {{ $pedidos->links('vendor.pagination.bootstrap-5-simple') }}
        </div>
    @endif
</div>