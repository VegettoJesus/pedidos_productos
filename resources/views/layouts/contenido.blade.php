<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.head')
</head>
<body>
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
