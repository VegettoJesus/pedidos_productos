<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.head')
</head>
<body class="{{ $darkMode ? 'dark-mode' : '' }}">
    @include('layouts.header')
    @include('layouts.sidebar')

    <main id="main">
        @yield('contenido')
    </main>

    @include('layouts.footer')
</body>
</html>