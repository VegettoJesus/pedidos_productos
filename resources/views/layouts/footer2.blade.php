<footer class="footer">
    <div class="footer-container">
        @php
            $footerColumns = \App\Helpers\ConfiguracionHelper::getFooterColumns();
        @endphp

        @forelse($footerColumns as $column)
            @php
                $columnId = $column->id;
                $type = $column->column_type;
                $iconHtml = \App\Helpers\ConfiguracionHelper::renderFooterIcon($column->icon);
                $title = e($column->title);
            @endphp

            <div>
                <h3 class="footer-title">
                    {!! $iconHtml !!} {{ $title }}
                </h3>

                {{-- Columna de solo enlaces --}}
                @if($type === 'links')
                    @php $links = \App\Helpers\ConfiguracionHelper::getFooterLinks($columnId); @endphp
                    @if($links->isNotEmpty())
                        <ul class="footer-links">
                           @foreach($links as $link)
                                <li>
                                    {!! \App\Helpers\ConfiguracionHelper::renderFooterIcon($link->icon) !!}
                                    <a href="{{ \App\Helpers\ConfiguracionHelper::normalizeUrl($link->url) }}" 
                                    class="footer-link" 
                                    target="_blank" 
                                    rel="noopener noreferrer">
                                        {{ e($link->text) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small">Sin enlaces configurados.</p>
                    @endif

                {{-- Columna mixta: contacto + redes sociales --}}
                @elseif($type === 'mixed')
                    @php
                        $contact = \App\Helpers\ConfiguracionHelper::getFooterContact($columnId);
                        $socials = \App\Helpers\ConfiguracionHelper::getFooterSocialNetworks($columnId);
                    @endphp

                    @if($contact && ($contact->phone || $contact->email || $contact->address))
                        <ul class="footer-links">
                            @if($contact->phone)
                                <li>
                                    {!! \App\Helpers\ConfiguracionHelper::renderFooterIcon($contact->phone_icon) !!}
                                    <a href="tel:{{ e($contact->phone) }}" class="footer-link">{{ e($contact->phone) }}</a>
                                </li>
                            @endif
                            @if($contact->email)
                                <li>
                                    {!! \App\Helpers\ConfiguracionHelper::renderFooterIcon($contact->email_icon) !!}
                                    <a href="mailto:{{ e($contact->email) }}" class="footer-link">{{ e($contact->email) }}</a>
                                </li>
                            @endif
                            @if($contact->address)
                                <li>
                                    {!! \App\Helpers\ConfiguracionHelper::renderFooterIcon($contact->address_icon) !!}
                                    <span class="footer-link">{{ e($contact->address) }}</span>
                                </li>
                            @endif
                        </ul>
                    @endif

                    @if($socials->isNotEmpty())
                        <div class="footer-social-links mt-2">
                            @foreach($socials as $social)
                                <a href="{{ e($social->url) }}" class="footer-social-link me-2" target="_blank" rel="noopener noreferrer" title="{{ e($social->name) }}">
                                    {!! \App\Helpers\ConfiguracionHelper::renderFooterIcon($social->icon) !!}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if((!$contact || (!$contact->phone && !$contact->email && !$contact->address)) && $socials->isEmpty())
                        <p class="text-muted small">Sin contenido configurado.</p>
                    @endif
                @endif
            </div>
        @empty
            {{-- Si no hay columnas, puedes mostrar algo por defecto o simplemente nada --}}
            <div>
                <h3 class="footer-title">Mi Empresa</h3>
                <p class="text-muted small">Contenido del footer en construcción.</p>
            </div>
        @endforelse
    </div>

    <div class="copyright">
        &copy; {{ date('Y') }} {{ e(\App\Helpers\ConfiguracionHelper::getCompanyName()) }}.
        {{ e(\App\Helpers\ConfiguracionHelper::getFooterText()) }}
    </div>

    {{-- Scripts que ya tenías --}}
    <script src="{{ asset('js/tiendaMain.js') }}"></script>
    @isset($script)
        <script src="{{ asset($script) }}"></script>
    @endisset
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</footer>