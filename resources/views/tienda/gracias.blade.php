<div class="container py-5 text-center">
    <div class="mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
    </div>
    <h1 class="mb-3">¡Gracias por tu compra!</h1>
    <p class="lead mb-4">Tu pedido <strong>{{ $pedido->codigo }}</strong> ha sido registrado exitosamente.</p>
    
    <div class="card mx-auto mb-4" style="max-width: 600px; border-radius: 15px;">
        <div class="card-body text-start">
            <h5 class="card-title border-bottom pb-2">Resumen del Pedido</h5>
            <p><strong>Total pagado:</strong> S/ {{ number_format($pedido->total, 2) }}</p>
            <p><strong>Método de pago:</strong> {{ $pedido->metodo_pago_nombre }}</p>
            <p><strong>Tipo de entrega:</strong> {{ ucfirst($pedido->tipo_entrega) }}</p>
            
            @if($pedido->voucher_path)
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i> Hemos recibido tu comprobante. Está en revisión.
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('tienda.home') }}" class="btn btn-primary" style="background:#f4ab27; border:none; padding: 0.8rem 2rem; border-radius: 30px;">
        Volver a la tienda
    </a>
</div>