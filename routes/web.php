<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdministracionDelSistema;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\PerfilController;
use App\Http\Middleware\VerificarPermisoMenu;
use App\Services\MenuService;
use App\Models\User;
use App\Models\Producto;
use App\Models\ProductoVariacion;
use App\Models\ProductoValoracion;
use App\Models\Departamento;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\NotificacionPanelController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\PasswordResetController;

Route::get('/', [TiendaController::class, 'home'])->name('tienda.home');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/buscar', [BusquedaController::class, 'buscar'])->name('buscar');
Route::get('/buscar/sugerencias', [BusquedaController::class, 'sugerencias'])->name('buscar.sugerencias');
Route::get('/ofertas', [TiendaController::class, 'ofertas'])->name('ofertas');
Route::get('/nosotros', [TiendaController::class, 'nosotros'])->name('nosotros');
Route::get('/contacto', [TiendaController::class, 'contacto'])->name('contacto');
Route::get('/productos/categoria/{id}', [TiendaController::class, 'productosPorCategoria'])->name('productos.categoria');
Route::get('/productos/subcategoria/{id}', [TiendaController::class, 'productosPorSubcategoria'])->name('productos.subcategoria');
Route::get('/producto/{id}', [TiendaController::class, 'detalleProducto'])->name('producto.detalle');
Route::get('/todos-productos', [TiendaController::class, 'todosProductos'])->name('tienda.todos-productos');
Route::get('/categorias', [TiendaController::class, 'todasCategorias'])->name('categorias.todas');
Route::post('/registro-cliente', [TiendaController::class, 'registroCliente'])->name('registro.cliente');
Route::post('/login-cliente', [TiendaController::class, 'loginCliente'])->name('login.cliente');
Route::post('/logout-cliente', [TiendaController::class, 'logoutCliente'])->name('logout.cliente');
Route::get('/categoria/{id}/productos', [TiendaController::class, 'productosCategoriaCompleta'])->name('categoria.productos.completa');
Route::post('/producto/valorar', [TiendaController::class, 'valorarProducto'])->middleware('auth');
Route::post('/toggle-dark-mode', [LoginController::class, 'toggleDarkMode'])->name('toggle-dark-mode');
Route::prefix('password')->group(function () {
    Route::post('/solicitar-codigo', [PasswordResetController::class, 'solicitarCodigo'])
        ->name('password.solicitar-codigo');
    
    Route::post('/verificar-codigo-activo', [PasswordResetController::class, 'verificarCodigoActivo'])
        ->name('password.verificar-codigo-activo');
    
    Route::post('/validar-codigo', [PasswordResetController::class, 'validarCodigo'])
        ->name('password.validar-codigo');
    
    Route::post('/cambiar-password', [PasswordResetController::class, 'cambiarPassword'])
        ->name('password.cambiar-password');
});
Route::get('/api/productos/{productoId}/variacion/{variacionId}/imagenes', function ($productoId, $variacionId) {
    try {
        $variacion = ProductoVariacion::with('imagenes')
            ->where('producto_padre_id', $productoId)
            ->where('id', $variacionId)
            ->where('activo', true)
            ->first();
        
        if (!$variacion) {
            return response()->json(['success' => false, 'message' => 'Variación no encontrada'], 404);
        }
        
        $imagenes = [];
        if ($variacion->imagenes->isNotEmpty()) {
            foreach ($variacion->imagenes as $img) {
                $imagenes[] = $img->imagen_path;
            }
        }
        
        if (empty($imagenes)) {
            $producto = Producto::find($productoId);
            if ($producto && $producto->imagen_miniatura) {
                $imagenes[] = $producto->imagen_miniatura;
            }
            if ($producto && $producto->imagenes->isNotEmpty()) {
                foreach ($producto->imagenes as $img) {
                    if (!in_array($img->imagen_path, $imagenes)) {
                        $imagenes[] = $img->imagen_path;
                    }
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'imagenes' => $imagenes
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener imágenes: ' . $e->getMessage()
        ], 500);
    }
});

// =============================================
// RUTAS PARA AUTENTICACIÓN Y REGISTRO
// =============================================

Route::get('/api/auth/check', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return response()->json([
            'authenticated' => true,
            'rol' => $user->rol->name ?? null,
            'user' => [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'rol_id' => $user->id_rol, 
            ]
        ]);
    }
    return response()->json(['authenticated' => false]);
})->name('api.auth.check');

Route::get('/api/producto/{id}/valoracion-usuario', function ($id) {
    if (!Auth::check()) {
        return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
    }
    
    $user = Auth::user();
    
    if ($user->id_rol != 2) {
        return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
    }
    
    $valoracion = ProductoValoracion::where('producto_id', $id)
        ->where('user_id', $user->id)
        ->first();
    
    return response()->json([
        'success' => true,
        'user_rating' => $valoracion ? $valoracion->puntuacion : 0,
        'comentario' => $valoracion ? $valoracion->comentario : null
    ]);
})->middleware('auth');

Route::get('/api/auth/check-email', function (Request $request) {
    $email = $request->input('email');
    $exists = User::where('email', $email)->exists();
    return response()->json(['exists' => $exists]);
})->name('api.auth.check-email');


Route::get('/api/mis-valoraciones/count', function() {
        $user = Auth::user();
        
        $count = ProductoValoracion::where('user_id', $user->id)
            ->where('aprobado', true)
            ->count();
        
        return response()->json(['success' => true, 'count' => $count]);
    })->name('api.mis-valoraciones.count');
Route::get('/mis-valoraciones', [PerfilController::class, 'misValoraciones'])->name('perfil.mis-valoraciones');
Route::get('/mis-valoraciones/data', [PerfilController::class, 'misValoraciones'])->name('perfil.mis-valoraciones.data');
Route::middleware(['auth'])->group(function () {
    Route::get('/notificaciones/usuario', [NotificacionController::class, 'usuarioNotificaciones'])
        ->name('notificaciones.usuario');
    
    Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida'])
        ->name('notificaciones.marcar-leida');
    
    Route::post('/notificaciones/marcar-todas-leidas', [NotificacionController::class, 'marcarTodasLeidas'])
        ->name('notificaciones.marcar-todas');
    
    Route::get('/notificaciones/no-leidas/count', [NotificacionController::class, 'countNoLeidas'])
        ->name('notificaciones.count');
});

// =============================================
// RUTAS PARA UBICACIÓN (Departamentos, Provincias, Distritos)
// =============================================

Route::get('/get-departamentos', fn() => Departamento::get(['id', 'nombre']))
    ->name('get.departamentos');
Route::get('/get-provincias/{id}', [AdministracionDelSistema::class, 'getProvincias']);
Route::get('/get-distritos/{id}', [AdministracionDelSistema::class, 'getDistritos']);

// =============================================
// API PRODUCTOS
// =============================================

Route::prefix('api/productos')->group(function () {
    Route::post('/variacion-detalle', [TiendaController::class, 'getVariacionDetalle'])
        ->name('api.variacion.detalle');
    Route::get('/{id}/variaciones', [TiendaController::class, 'getVariacionesProducto'])
        ->name('api.producto.variaciones');
    Route::post('/terminos-disponibles', [TiendaController::class, 'getTerminosDisponibles'])
        ->name('api.producto.terminos-disponibles');
    Route::post('/orden-atributos', [TiendaController::class, 'getOrdenAtributos'])
        ->name('api.producto.orden-atributos');
});

// =============================================
// OTRAS RUTAS
// =============================================

Route::get('/get-iconos', function () {
    $path = storage_path('app/iconos.csv');
    if (!file_exists($path)) {
        abort(404, 'Archivo no encontrado');
    }
    return response(file_get_contents($path), 200)
        ->header('Content-Type', 'text/plain'); 
});

Route::get('/main', [LoginController::class, 'main'])
    ->name('main')
    ->middleware('auth');

Route::get('/carrito', [CarritoController::class, 'vista'])->name('carrito.vista');

Route::middleware('auth')->prefix('carrito')->group(function () {
    Route::get('/data', [CarritoController::class, 'index'])->name('carrito.index');
    Route::post('/agregar', [CarritoController::class, 'agregar'])->name('carrito.agregar');
    Route::put('/item/{itemId}', [CarritoController::class, 'actualizar'])->name('carrito.actualizar');
    Route::delete('/item/{itemId}', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');
    Route::delete('/vaciar', [CarritoController::class, 'vaciar'])->name('carrito.vaciar');
    Route::get('/count', [CarritoController::class, 'count'])->name('carrito.count');
    Route::get('/cross-sells', [CarritoController::class, 'crossSellsHtml'])->name('carrito.cross-sells');
});

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/confirmar', [CheckoutController::class, 'confirmar'])->name('checkout.confirmar');
});

// =============================================
// RUTAS CON MIDDLEWARE AUTH
// =============================================

Route::middleware(['auth'])->group(function () {
    Route::get('/configuracion', [PerfilController::class, 'index'])->name('perfil.configuracion');
    Route::post('/perfil/obtener', [PerfilController::class, 'obtenerDatos']);
    Route::post('/perfil/actualizar', [PerfilController::class, 'actualizar']);
    Route::get('/notificaciones', [NotificacionPanelController::class, 'vista'])
        ->name('notificaciones.panel.vista');
    
    Route::prefix('notificaciones/panel')->group(function () {
        Route::get('no-leidas/count', [NotificacionPanelController::class, 'countNoLeidas'])
            ->name('notificaciones.panel.count');
        Route::post('marcar-todas-leidas', [NotificacionPanelController::class, 'marcarTodasLeidas'])
            ->name('notificaciones.panel.marcar-todas');
        Route::get('/', [NotificacionPanelController::class, 'index'])
            ->name('notificaciones.panel.index');
        Route::get('no-leidas', [NotificacionPanelController::class, 'noLeidas'])
            ->name('notificaciones.panel.no-leidas');
        Route::post('{id}/leer', [NotificacionPanelController::class, 'marcarLeida'])
            ->name('notificaciones.panel.leer');
    });
    Route::get('/main', [LoginController::class, 'main'])->name('main');
    Route::get('{controlador}/{metodo}', function ($controlador, $metodo) {
        $controllerClass = 'App\\Http\\Controllers\\' . ucfirst($controlador);

        if (class_exists($controllerClass) && method_exists($controllerClass, $metodo)) {
            if (!app(MenuService::class)::tienePermiso("{$controlador}/{$metodo}")) {
                abort(403, 'No tienes permiso para acceder a esta página');
            }
            return app()->call("$controllerClass@$metodo");
        }

        abort(404);
    })->middleware('permiso:ver');

    Route::post('{controlador}/{metodo}', function ($controlador, $metodo) {
        $controllerClass = 'App\\Http\\Controllers\\' . ucfirst($controlador);

        if (class_exists($controllerClass) && method_exists($controllerClass, $metodo)) {
            if (!app(MenuService::class)::tienePermiso("{$controlador}/{$metodo}")) {
                abort(403, 'No tienes permiso para acceder a esta función');
            }
            return app()->call("$controllerClass@$metodo");
        }

        abort(404);
    })->middleware('permiso:ver');

    Route::get('administracion/menus/editar/{id}', [AdministracionDelSistema::class, 'editarMenu'])
        ->middleware('permiso:editar');
        
    Route::post('administracion/menus/crear', [AdministracionDelSistema::class, 'crearMenu'])
        ->middleware('permiso:crear');
        
    Route::delete('administracion/menus/eliminar/{id}', [AdministracionDelSistema::class, 'eliminarMenu'])
        ->middleware('permiso:eliminar');
});