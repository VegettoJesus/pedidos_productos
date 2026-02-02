<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.head2')
</head>
<body>
    @include('layouts.header2')
    @include('layouts.sidebar2')

    <main class="content">
        @isset($contenido2)
            @include($contenido2)
        @endisset
    </main>
    @include('layouts.footer2')
</body>
</html>