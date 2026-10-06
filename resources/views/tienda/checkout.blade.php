{{-- resources/views/tienda/checkout.blade.php --}}
<div class="carrito-page">
    <div class="carrito-header-page">
        <h1><i class="bi bi-credit-card-2-front"></i> Finalizar compra</h1>
        <p>Completa los datos para tu pedido</p>
    </div>

    <form id="checkoutForm" enctype="multipart/form-data">
        @csrf

        {{-- Datos que el JS necesita --}}
        <input type="hidden" id="ubicacionValida" value="{{ $ubicacionValida ? '1' : '0' }}">
        <input type="hidden" id="clienteDepartamento" value="{{ $ubicacionCliente['departamento_id'] }}">
        <input type="hidden" id="clienteProvincia" value="{{ $ubicacionCliente['provincia_id'] }}">
        <input type="hidden" id="clienteDistrito" value="{{ $ubicacionCliente['distrito_id'] }}">
        <input type="hidden" id="clienteDireccion" value="{{ $ubicacionCliente['direccion_completa'] }}">

        {{-- Punto de retiro (desde empresa) --}}
        <input type="hidden" id="empresaDireccion"
            value="{{ $empresa ? ($empresa->direccion ?? '') . ' - ' . ($empresa->nombre_comercial ?? $empresa->razon_social ?? '') : 'Tienda principal' }}">

        <div class="carrito-grid">
            <div>
                {{-- ========================================== --}}
                {{-- TIPO DE ENTREGA --}}
                {{-- ========================================== --}}
                <div class="carrito-items-wrapper" style="padding:1.5rem;margin-bottom:1.5rem;">
                    <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem;">Tipo de entrega</h3>

                    <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                        <label class="filter-radio" style="padding:1rem;border:2px solid #eee;border-radius:12px;flex:1;cursor:pointer;">
                            <input type="radio" name="tipo_entrega" value="delivery" checked>
                            <i class="bi bi-truck"></i> Delivery
                        </label>
                        <label class="filter-radio" style="padding:1rem;border:2px solid #eee;border-radius:12px;flex:1;cursor:pointer;">
                            <input type="radio" name="tipo_entrega" value="retiro">
                            <i class="bi bi-shop"></i> Recojo en tienda
                        </label>
                    </div>

                    {{-- DELIVERY --}}
                    <div id="deliveryFields" style="margin-top:1.5rem;">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label>Departamento</label>
                                <select name="departamento_id" id="departamentoSelect" class="form-control">
                                    <option value="">Seleccione</option>
                                    @foreach($departamentos as $d)
                                        <option value="{{ $d->id }}" {{ $ubicacionCliente['departamento_id'] == $d->id ? 'selected' : '' }}>
                                            {{ $d->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Provincia</label>
                                <select name="provincia_id" id="provinciaSelect" class="form-control" disabled>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Distrito</label>
                                <select name="id_distrito" id="distritoSelect" class="form-control" disabled>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label>Dirección</label>
                                <input type="text" name="direccion_entrega" id="direccionEntrega" class="form-control"
                                    placeholder="Calle, número, referencia"
                                    value="{{ $ubicacionCliente['direccion_completa'] }}">
                            </div>
                        </div>
                    </div>

                    {{-- RECOJO EN TIENDA --}}
                    <div id="retiroFields" style="margin-top:1.5rem;display:none;">
                        <div class="alert alert-info" style="border-radius:12px;">
                            <i class="bi bi-geo-alt-fill"></i>
                            <strong>Punto de retiro:</strong>
                            <span id="puntoRetiroTexto">{{ $empresa?->direccion ?? 'Tienda principal' }}</span>
                        </div>
                        <input type="hidden" name="punto_retiro" id="puntoRetiroInput"
                            value="{{ $empresa?->direccion ?? 'Tienda principal' }}">
                        @if($empresa && $empresa->telefono)
                            <small class="text-muted d-block">
                                <i class="bi bi-telephone"></i> Teléfono: {{ $empresa->telefono }}
                            </small>
                        @endif
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- MÉTODO DE PAGO --}}
                {{-- ========================================== --}}
                <div class="carrito-items-wrapper" style="padding:1.5rem;">
                    <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem;">Método de pago</h3>

                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        @php $primerDisponible = null; @endphp

                        @foreach($metodosEvaluados as $ev)
                            @php
                                $mp = $ev['metodo'];
                                $disponible = $ev['disponible'];
                                $config = $mp->configuracion ?? [];
                                $titulo = $config['titulo'] ?? $mp->nombre;
                                $iconoMetodo = $config['icono_imagen'] ?? null;
                            @endphp

                            <label class="metodo-pago-label {{ !$disponible ? 'metodo-deshabilitado' : '' }}" 
                                data-slug="{{ $mp->slug }}"
                                data-disponible="{{ $disponible ? '1' : '0' }}"
                                data-cargo-base="{{ $ev['cargo_adicional'] }}"
                                data-impuesto-cargo="{{ $ev['impuesto_cargo'] }}"
                                data-total-cargo="{{ $ev['total_cargo'] }}"
                                style="padding:1rem;border:2px solid #eee;border-radius:12px;
                                        {{ !$disponible ? 'cursor:not-allowed;opacity:0.6;' : 'cursor:pointer;' }}
                                        transition:all 0.2s;">

                                <div class="metodo-pago-header" style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;">
                                    <input type="radio" name="tipo_pago" 
                                        value="{{ $mp->slug }}" 
                                        {{ !$disponible ? 'disabled' : '' }}
                                        {{ $disponible && $primerDisponible === null ? 'checked' : '' }}
                                        data-disponible="{{ $disponible ? '1' : '0' }}">

                                    @if($iconoMetodo)
                                        <img src="/metodos_pago/{{ $iconoMetodo }}" alt="" style="width:24px;height:24px;object-fit:contain;">
                                    @else
                                        <i class="{{ $mp->icono }}"></i>
                                    @endif

                                    <strong>{{ $titulo }}</strong>

                                    @if($disponible && $ev['total_cargo'] > 0)
                                        <span class="badge-cargo" style="margin-left:auto;background:#f4ab27;color:#fff;font-size:0.75rem;padding:0.2rem 0.5rem;border-radius:20px;">
                                            + S/ {{ number_format($ev['total_cargo'], 2) }}
                                        </span>
                                    @endif
                                </div>

                                @if(!$disponible)
                                    <div style="margin-top:0.75rem;padding:0.6rem;background:#fff3cd;border-left:3px solid #ffc107;border-radius:6px;font-size:0.85rem;color:#856404;">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $ev['mensaje'] }}
                                    </div>
                                @endif

                                <div class="metodo-pago-info" 
                                    data-slug="{{ $mp->slug }}" 
                                    style="display:none;margin-top:1rem;padding-top:1rem;border-top:1px dashed #ddd;">
                                </div>
                            </label>

                            @if($disponible && $primerDisponible === null)
                                @php $primerDisponible = $mp->slug; @endphp
                            @endif
                        @endforeach
                    </div>

                    <div style="margin-top:1.5rem;">
                        <label>Subir voucher</label>
                        <input type="file" name="imagen" accept="image/*" class="form-control">
                    </div>
                </div>
            </div>

            {{-- ========================================== --}}
            {{-- RESUMEN --}}
            {{-- ========================================== --}}
            <div class="carrito-resumen">
                <h3><i class="bi bi-receipt"></i> Resumen</h3>

                @foreach($carrito->items as $item)
                    <div class="resumen-linea">
                        <span>{{ $item->producto->nombre }} × {{ $item->cantidad }}</span>
                        <span>S/ {{ number_format($item->cantidad * $item->precio_unitario, 2) }}</span>
                    </div>
                @endforeach

                <div class="resumen-linea">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay" data-subtotal="{{ $carrito->total }}">
                        S/ {{ number_format($carrito->total, 2) }}
                    </span>
                </div>

                {{-- Envío --}}
                {{-- Envío base --}}
                <div class="resumen-linea" id="envioLinea">
                    <span>Envío</span>
                    <span id="envioDisplay">S/ 0.00</span>
                </div>

                {{-- Impuesto del envío --}}
                <div class="resumen-linea" id="impuestoEnvioLinea" style="display:none;">
                    <span>
                        Impuesto del envío
                        <small id="tasaImpuestoEnvio" style="color:#999;"></small>
                    </span>
                    <span id="impuestoEnvioDisplay">S/ 0.00</span>
                </div>

                {{-- Cargo base --}}
                <div class="resumen-linea" id="cargoLinea" style="display:none;">
                    <span>Cargo por método de pago</span>
                    <span id="cargoDisplay">S/ 0.00</span>
                </div>

                {{-- Impuesto del cargo --}}
                <div class="resumen-linea" id="impuestoCargoLinea" style="display:none;">
                    <span>
                        Impuesto del cargo
                        <small id="tasaImpuestoCargo" style="color:#999;"></small>
                    </span>
                    <span id="impuestoCargoDisplay">S/ 0.00</span>
                </div>

                <div class="resumen-linea total">
                    <span>Total</span>
                    <span id="totalDisplay">S/ {{ number_format($carrito->total, 2) }}</span>
                </div>

                <button type="submit" class="btn-checkout" id="btnConfirmar">
                    <i class="bi bi-check2-circle"></i> Confirmar pedido
                </button>
            </div>
        </div>
    </form>
    <div class="modal fade qr-zoom-modal" id="qrZoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-qr-code"></i> Código QR de pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="qrZoomImage" src="" alt="QR ampliado">
                    <p class="text-muted mt-3 mb-0" id="qrZoomHint">
                        <i class="bi bi-info-circle"></i> Escanea este código con tu app de pagos
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script id="metodosPagoData" type="application/json">
    {!! json_encode(collect($metodosEvaluados)->map(function($ev) {
        return [
            'slug' => $ev['metodo']->slug,
            'nombre' => $ev['metodo']->nombre,
            'icono' => $ev['metodo']->icono,
            'descripcion' => $ev['metodo']->descripcion,
            'configuracion' => $ev['metodo']->configuracion,
            'disponible' => $ev['disponible'],
            'mensaje' => $ev['mensaje'],
            'cargo_adicional' => $ev['cargo_adicional'],
            'impuesto_cargo' => $ev['impuesto_cargo'],
            'total_cargo' => $ev['total_cargo'],
            'base_calculo' => $ev['base_calculo'] ?? 0,
            'impuesto_envio' => $ev['impuesto_envio'] ?? 0,
            'total_envio' => $ev['total_envio'] ?? 0,
            'impuesto_envio_tasa' => $ev['impuesto_envio_tasa'] ?? 0,
            'impuesto_envio_activo' => $ev['impuesto_envio_activo'] ?? false,
        ];
    })) !!}
</script>