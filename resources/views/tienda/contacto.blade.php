<div class="contacto-page">
    <section class="contacto-hero">
        <div class="contacto-hero-content">
            <span class="contacto-hero-eyebrow">
                <i class="bi bi-headset"></i> Estamos para ayudarte
            </span>
            <h1>Contáctanos</h1>
            <p>
                ¿Tienes alguna duda, consulta o sugerencia? Escríbenos y te responderemos
                lo antes posible. Tu opinión es muy importante para nosotros.
            </p>
        </div>
    </section>

    <section class="contacto-grid">
        <div class="contacto-form-card">
            <h2 class="contacto-form-title">
                <i class="bi bi-chat-left-text"></i> Envíanos un mensaje
            </h2>
            <p class="contacto-form-subtitle">
                Completa el formulario y nos pondremos en contacto contigo.
            </p>

            <form id="contactoForm" action="{{ route('contacto.enviar') }}" method="POST" novalidate>
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="contacto_nombre" class="form-label">
                            Nombre completo <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text"
                                   class="form-control"
                                   id="contacto_nombre"
                                   name="nombre"
                                   placeholder="Tu nombre"
                                   maxlength="120"
                                   required>
                        </div>
                        <div class="invalid-feedback" id="err_nombre"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="contacto_email" class="form-label">
                            Correo electrónico <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email"
                                   class="form-control"
                                   id="contacto_email"
                                   name="email"
                                   placeholder="tucorreo@ejemplo.com"
                                   maxlength="150"
                                   required>
                        </div>
                        <div class="invalid-feedback" id="err_email"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="contacto_telefono" class="form-label">
                            Teléfono <small class="text-muted">(opcional)</small>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel"
                                   class="form-control"
                                   id="contacto_telefono"
                                   name="telefono"
                                   placeholder="+51 999 999 999"
                                   maxlength="30">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="contacto_asunto" class="form-label">
                            Asunto <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                            <select class="form-select" id="contacto_asunto" name="asunto" required>
                                <option value="">Selecciona un asunto</option>
                                <option value="Consulta general">Consulta general</option>
                                <option value="Consulta sobre producto">Consulta sobre producto</option>
                                <option value="Estado de pedido">Estado de pedido</option>
                                <option value="Devoluciones y garantías">Devoluciones y garantías</option>
                                <option value="Sugerencias">Sugerencias</option>
                                <option value="Reclamos">Reclamos</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="invalid-feedback" id="err_asunto"></div>
                    </div>

                    <div class="col-12">
                        <label for="contacto_mensaje" class="form-label">
                            Mensaje <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control"
                                  id="contacto_mensaje"
                                  name="mensaje"
                                  rows="6"
                                  placeholder="Cuéntanos en qué podemos ayudarte..."
                                  minlength="10"
                                  maxlength="2000"
                                  required></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <div class="invalid-feedback d-block" id="err_mensaje"></div>
                            <small class="text-muted">
                                <span id="contadorMensaje">0</span>/2000
                            </small>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mt-2">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i>
                            Tus datos están protegidos y no serán compartidos.
                        </small>
                        <button type="submit" class="btn-contacto-enviar" id="btnEnviarContacto">
                            <span class="btn-text">Enviar mensaje</span>
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="contacto-info-col">

            {{-- Info cards --}}
            <div class="contacto-info-card">
                <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="info-body">
                    <h3>Nuestra dirección</h3>
                    <p>
                        @if($empresa)
                            {{ $empresa->direccion ?? 'Dirección no disponible' }}
                            @if($empresa->distrito || $empresa->provincia || $empresa->departamento)
                                <br>
                                @if($empresa->distrito)
                                    {{ $empresa->distrito->nombre }}
                                @endif
                                @if($empresa->provincia)
                                    , {{ $empresa->provincia->nombre }}
                                @endif
                                @if($empresa->departamento)
                                    , {{ $empresa->departamento->nombre }}
                                @endif
                            @endif
                        @else
                            Dirección no configurada
                        @endif
                    </p>
                    @if($empresa && $empresa->maps_url)
                        <a href="{{ $empresa->maps_url }}" target="_blank" rel="noopener" class="info-link">
                            Ver en el mapa <i class="bi bi-arrow-up-right"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="contacto-info-card">
                <div class="info-icon"><i class="bi bi-telephone-fill"></i></div>
                <div class="info-body">
                    <h3>Teléfonos</h3>
                    @if($empresa && $empresa->celular)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $empresa->celular) }}" class="info-link-strong">
                            <i class="bi bi-phone"></i> {{ $empresa->celular }}
                        </a>
                    @endif
                    @if($empresa && $empresa->telefono)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $empresa->telefono) }}" class="info-link-strong">
                            <i class="bi bi-telephone"></i> {{ $empresa->telefono }}
                        </a>
                    @endif
                    @if($empresa && $empresa->whatsapp_link)
                        <a href="{{ $empresa->whatsapp_link }}" target="_blank" rel="noopener" class="info-link-strong wa">
                            <i class="bi bi-whatsapp"></i> Escríbenos por WhatsApp
                        </a>
                    @endif
                </div>
            </div>

            <div class="contacto-info-card">
                <div class="info-icon"><i class="bi bi-envelope-fill"></i></div>
                <div class="info-body">
                    <h3>Correo electrónico</h3>
                    <a href="mailto:{{ \App\Helpers\ConfiguracionHelper::getEmail() }}" class="info-link-strong">
                        {{ \App\Helpers\ConfiguracionHelper::getEmail() }}
                    </a>
                    <p class="mt-2 small text-muted mb-0">
                        Respondemos en un plazo máximo de 24 horas hábiles.
                    </p>
                </div>
            </div>

            <div class="contacto-info-card">
                <div class="info-icon"><i class="bi bi-clock-fill"></i></div>
                <div class="info-body">
                    <h3>Horario de atención</h3>
                    <ul class="horario-lista">
                        <li><span>Lunes a Viernes</span><strong>9:00 - 18:00</strong></li>
                        <li><span>Sábados</span><strong>9:00 - 13:00</strong></li>
                        <li><span>Domingos y feriados</span><strong>Cerrado</strong></li>
                    </ul>
                </div>
            </div>

        </div>
    </section>

    @if($empresa)
    <section class="contacto-mapa-wrapper">
        <h2 class="contacto-mapa-title">
            <i class="bi bi-map"></i> Ubícanos
        </h2>
        <div class="contacto-mapa">
            @php
                // 1.- Construir dirección legible usando las RELACIONES (no el atributo crudo)
                $partesDir = array_filter([
                    trim(($empresa->direccion ?? '') . ' ' . ($empresa->numero ?? '')),
                    $empresa->distrito?->nombre,
                    $empresa->provincia?->nombre,
                    $empresa->departamento?->nombre,
                ]);
                $direccionCompleta = implode(', ', $partesDir) ?: 'Lima, Perú';

                // 2.- Determinar la URL del embed
                $mapsUrl  = $empresa->maps_url;
                $embedUrl = null;

                if ($mapsUrl) {
                    // Caso A: es un embed válido (contiene "/maps/embed")
                    if (str_contains($mapsUrl, '/maps/embed')) {
                        $embedUrl = $mapsUrl;
                    }
                    // Caso B: es un link normal de google.com/maps con coordenadas o query
                    elseif (str_contains($mapsUrl, 'google.com/maps')) {
                        // Si tiene "q=" ya es un embed directo
                        if (str_contains($mapsUrl, 'q=')) {
                            $embedUrl = $mapsUrl . (str_contains($mapsUrl, '?') ? '&' : '?') . 'output=embed';
                        } else {
                            // Extraer coordenadas si existen (@lat,lng)
                            if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $mapsUrl, $m)) {
                                $embedUrl = "https://www.google.com/maps?q={$m[1]},{$m[2]}&output=embed";
                            } else {
                                // Fallback: usar la dirección
                                $embedUrl = 'https://www.google.com/maps?q=' . urlencode($direccionCompleta) . '&output=embed';
                            }
                        }
                    }
                    // Caso C: link corto (maps.app.goo.gl / goo.gl/maps) → NO se puede embeber
                    //          → construimos el embed con la dirección de la empresa
                    elseif (str_contains($mapsUrl, 'maps.app.goo.gl') || str_contains($mapsUrl, 'goo.gl/maps')) {
                        $embedUrl = 'https://www.google.com/maps?q=' . urlencode($direccionCompleta) . '&output=embed';
                    }
                    // Caso D: otras URLs → intentar usarla tal cual (por si ya es un embed)
                    else {
                        $embedUrl = $mapsUrl;
                    }
                }

                // 3.- Si no hay maps_url, construir embed con la dirección igual
                if (!$embedUrl) {
                    $embedUrl = 'https://www.google.com/maps?q=' . urlencode($direccionCompleta) . '&output=embed';
                }
            @endphp

            <iframe
                src="{{ $embedUrl }}"
                width="100%"
                height="400"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

        @if($mapsUrl)
        <div class="contacto-mapa-acciones">
            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="btn-ver-maps">
                <i class="bi bi-geo-alt"></i> Abrir en Google Maps
            </a>
        </div>
        @endif
    </section>
    @endif

    @php
        $redesFooter = \App\Helpers\ConfiguracionHelper::getFooterColumns()
            ->where('column_type', 'mixed')
            ->first();
        $redesSociales = $redesFooter
            ? \App\Helpers\ConfiguracionHelper::getFooterSocialNetworks($redesFooter->id)
            : collect();
    @endphp

    @if($redesSociales->count())
    <section class="contacto-redes">
        <h3>También puedes encontrarnos en</h3>
        <div class="contacto-redes-lista">
            @foreach($redesSociales as $red)
                <a href="{{ $red->url }}" target="_blank" rel="noopener noreferrer" class="contacto-red-item" title="{{ $red->name }}">
                    {!! \App\Helpers\ConfiguracionHelper::renderFooterIcon($red->icon) !!}
                    <span>{{ $red->name }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contactoForm');
    if (!form) return;

    const btn      = document.getElementById('btnEnviarContacto');
    const btnText  = btn.querySelector('.btn-text');
    const btnIcon  = btn.querySelector('i');
    const mensaje  = document.getElementById('contacto_mensaje');
    const contador = document.getElementById('contadorMensaje');

    if (mensaje && contador) {
        const actualizarContador = () => {
            contador.textContent = mensaje.value.length;
            if (mensaje.value.length > 2000) {
                mensaje.value = mensaje.value.slice(0, 2000);
                contador.textContent = 2000;
            }
        };
        actualizarContador(); 
        mensaje.addEventListener('input', actualizarContador);
    }

    form.querySelectorAll('.form-control, .form-select').forEach(el => {
        el.addEventListener('input', () => {
            el.classList.remove('is-invalid');
            const err = el.closest('.col-12, .col-md-6')?.querySelector('.invalid-feedback');
            if (err) err.textContent = '';
        });
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

        const nombre  = form.nombre.value.trim();
        const email   = form.email.value.trim();
        const asunto  = form.asunto.value.trim();
        const msgText = form.mensaje.value.trim();

        let hasError = false;
        const marcarError = (inputId, feedbackId, msg) => {
            const input = document.getElementById(inputId);
            const feedback = document.getElementById(feedbackId);
            if (input) input.classList.add('is-invalid');
            if (feedback) feedback.textContent = msg;
        };

        if (!nombre || nombre.length < 2) {
            marcarError('contacto_nombre', 'err_nombre', 'Ingresa tu nombre.');
            hasError = true;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            marcarError('contacto_email', 'err_email', 'Ingresa un correo válido.');
            hasError = true;
        }
        if (!asunto) {
            marcarError('contacto_asunto', 'err_asunto', 'Selecciona un asunto.');
            hasError = true;
        }
        if (msgText.length < 10) {
            marcarError('contacto_mensaje', 'err_mensaje', 'El mensaje debe tener al menos 10 caracteres.');
            hasError = true;
        }

        if (hasError) {
            Swal.fire({
                icon: 'warning',
                title: 'Revisa el formulario',
                text: 'Completa correctamente todos los campos obligatorios.',
                confirmButtonColor: '#f4ab27'
            });
            return;
        }

        btn.disabled = true;
        btnText.textContent = 'Enviando...';
        btnIcon.className = 'spinner-border spinner-border-sm';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            });

            const data = await res.json();

            if (data.success) {
                form.reset();
                if (contador) contador.textContent = '0';

                Swal.fire({
                    icon: 'success',
                    title: '¡Mensaje enviado!',
                    text: data.message || 'Gracias por escribirnos. Te responderemos pronto.',
                    confirmButtonColor: '#f4ab27',
                    confirmButtonText: 'Entendido'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: data.message || 'No pudimos enviar tu mensaje. Intenta de nuevo.',
                    confirmButtonColor: '#e74c3c'
                });
            }
        } catch (err) {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor.',
                confirmButtonColor: '#e74c3c'
            });
        } finally {
            btn.disabled = false;
            btnText.textContent = 'Enviar mensaje';
            btnIcon.className = 'bi bi-send';
        }
    });
});
</script>