<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.head')
    <!-- CSS dinámico del tema activo -->
    <style id="tema-css-dinamico">
        @if(isset($temaCss) && $temaCss)
            {!! $temaCss !!}
        @else
            {{ App\Models\ConfiguracionColor::generarCssTemaActivo() }}
        @endif
    </style>
</head>
<body class="{{ $darkMode ? 'dark-mode' : '' }}">
    @include('layouts.header')
    @include('layouts.sidebar')

    <main id="main">
        @isset($contenido)
            @include($contenido)
        @endisset
    </main>

    @include('layouts.footer')
</body>
</html>