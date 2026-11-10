<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracionSistema;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Producto;
use App\Models\ProductoValoracion;
use App\Models\ProductoAtributo;
use App\Models\AtributoTerm;
use App\Models\ProductoVariacion;
use App\Models\User;
use App\Models\Rol;
use App\Models\Atributo;
use App\Models\AtributoPrioridad;
use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\EmpresaInformacion;
use App\Models\Carrito;
use App\Models\ContactoMensaje;
use App\Helpers\ConfiguracionHelper;
use App\Models\HomeConfiguracion;
use App\Models\PermisoNotificacion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
class TiendaController extends Controller
{
    
    private function getBaseConfig()
    {
        $config = ConfiguracionSistema::first();
        $authUser = null;
        if (Auth::check()) {
            $user = Auth::user();
            // Solo si el rol es 'client' lo pasamos; de lo contrario, null
            if ($user->rol && $user->rol->name === 'client') {
                $authUser = [
                    'nombres'   => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email'     => $user->email,
                    'foto'      => asset('img/user.png'),
                ];
            }
        }
        return [
            'titulo_site' => $config ? $config->titulo_site : null,
            'descripcion_corta' => $config ? $config->descripcion_corta : null,
            'authUser' => $authUser,
        ];
    }

    public function home()
    {
        $config  = ConfiguracionSistema::first();
        $maxHome = (int) ($config->max_entradas_home ?? 12);

        $seccionesConfig = HomeConfiguracion::where('mostrar', true)
            ->orderBy('orden', 'asc')
            ->get();

        $secciones = [];
        $yaIncluidos = [];   
        $restante = $maxHome;

        foreach ($seccionesConfig as $sec) {
            if ($restante <= 0) break;

            $config   = $sec->configuracion_json ?? [];
            $porTomar = min($sec->numero_elementos, $restante);

            $productos = $this->obtenerProductosSeccion(
                $sec->seccion,
                $config,
                $porTomar,
                $yaIncluidos
            );

            if ($productos->isEmpty()) continue;

            foreach ($productos as $p) {
                $yaIncluidos[] = $p->id;
            }

            $restante -= $productos->count();

            $secciones[] = [
                'seccion'       => $sec->seccion,
                'nombre'        => $this->nombreLegibleSeccion($sec->seccion),
                'icono'         => $this->iconoSeccion($sec->seccion),
                'configuracion' => $config,
                'productos'     => $this->procesarVariacionesTarjeta($productos),
            ];
        }

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2'   => 'tienda.main',
            'secciones'    => $secciones,
            'max_home'     => $maxHome,
            'config_site'  => $config,
        ]));
    }

    /**
     * Secciones que NO deben repetir productos ya mostrados arriba
     */
    private array $seccionesSinRepetir = ['destacados', 'recomendados'];

    /**
     * Secciones que SÍ pueden repetir productos de otras secciones
     */
    private array $seccionesConRepeticion = ['ofertas', 'mas_vendidos', 'nuevos'];

    /**
     * Obtiene los productos según el tipo de sección
     */
    private function obtenerProductosSeccion(string $seccion, array $config, int $limite, array $excluirIds)
    {
        if ($limite <= 0) return collect();

        $base = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado');

        $debeExcluir = in_array($seccion, $this->seccionesSinRepetir, true);
        if ($debeExcluir && !empty($excluirIds)) {
            $base->whereNotIn('id', $excluirIds);
        }

        switch ($seccion) {

            case 'destacados':
                $criterio       = $config['criterio'] ?? 'manual';
                $mostrarPrecio  = $config['mostrar_precio'] ?? true;

                $q = clone $base;

                if ($criterio === 'manual') {
                    $ids = (array) ($config['productos_ids'] ?? []);
                    if (empty($ids)) return collect();
                    // Respetar orden manual
                    $productos = $q->whereIn('id', $ids)->get();
                    return $productos->sortBy(function ($p) use ($ids) {
                        return array_search($p->id, $ids);
                    })->take($limite);
                }

                if ($criterio === 'rating') {
                    return $q
                        ->withAvg(['valoraciones as avg_rating' => function ($v) {
                            $v->where('aprobado', true);
                        }], 'puntuacion')
                        ->having('avg_rating', '>', 0)
                        ->orderByDesc('avg_rating')
                        ->take($limite)
                        ->get();
                }

                if ($criterio === 'ventas') {
                    return $q
                        ->withCount(['ventas as total_vendido'])
                        ->having('total_vendido', '>', 0)
                        ->orderByDesc('total_vendido')
                        ->take($limite)
                        ->get();
                }

                return $q->orderByDesc('created_at')->take($limite)->get();

            case 'nuevos':
                $dias = (int) ($config['dias_nuevo'] ?? 7);
                return $base
                    ->where('created_at', '>=', now()->subDays($dias))
                    ->orderByDesc('created_at')
                    ->take($limite)
                    ->get();

            case 'ofertas':
                $descuentoMin = (float) ($config['descuento_minimo'] ?? 0);
                $ordenarPor   = $config['ordenar_por'] ?? 'porcentaje_descuento';

                $queryOfertas = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
                    ->where('estado', 'publicado')
                    ->where(function ($q) {
                        $q->where(function ($s) {
                            $s->where('tipo_producto', 'simple')
                            ->whereNotNull('precio_rebajado')
                            ->where('precio_rebajado', '>', 0)
                            ->where(fn($f) => $f->whereNull('fecha_fin_rebaja')
                                                ->orWhere('fecha_fin_rebaja', '>=', now()));
                        })
                        ->orWhere(function ($s) {
                            $s->where('tipo_producto', 'variable')
                            ->whereHas('variaciones', function ($v) {
                                $v->where('activo', true)
                                    ->whereNotNull('precio_rebajado')
                                    ->where('precio_rebajado', '>', 0)
                                    ->where(fn($f) => $f->whereNull('fecha_fin_rebaja')
                                                        ->orWhere('fecha_fin_rebaja', '>=', now()));
                            });
                        });
                    });

                if ($descuentoMin > 0) {
                    $queryOfertas->where(function ($q) use ($descuentoMin) {
                        $q->whereRaw("
                            (1 - precio_rebajado / NULLIF(precio_regular,0)) * 100 >= ?
                        ", [$descuentoMin])
                        ->orWhereHas('variaciones', function ($v) use ($descuentoMin) {
                            $v->whereRaw("
                                (1 - precio_rebajado / NULLIF(precio_regular,0)) * 100 >= ?
                            ", [$descuentoMin]);
                        });
                    });
                }

                switch ($ordenarPor) {
                    case 'precio_final':
                        $queryOfertas->orderByRaw("
                            COALESCE(precio_rebajado, precio_regular) ASC
                        ");
                        break;
                    case 'fecha_fin':
                        $queryOfertas->orderByRaw("
                            CASE WHEN fecha_fin_rebaja IS NULL THEN 1 ELSE 0 END,
                            fecha_fin_rebaja ASC
                        ");
                        break;
                    default: 
                        $queryOfertas->orderByRaw("
                            CASE
                                WHEN tipo_producto='simple' AND precio_regular > 0
                                THEN (1 - precio_rebajado/precio_regular)*100
                                ELSE 0
                            END DESC
                        ");
                }

                return $queryOfertas->take($limite)->get();

            case 'mas_vendidos':
                return $base
                    ->withCount(['ventas as total_vendido'])
                    ->having('total_vendido', '>', 0)
                    ->orderByDesc('total_vendido')
                    ->take($limite)
                    ->get();

            case 'recomendados':
                if (!Auth::check()) return collect();  

                $userId = Auth::id();
                $idsBase = DB::table('pedido_items as pd')
                    ->join('pedidos as p', 'p.id', '=', 'pd.pedido_id')
                    ->where('p.user_id', $userId)
                    ->pluck('pd.producto_id')
                    ->merge(
                        DB::table('producto_valoraciones')
                            ->where('user_id', $userId)
                            ->pluck('producto_id')
                    )
                    ->unique()
                    ->toArray();

                if (empty($idsBase)) {
                    return $base
                        ->withAvg(['valoraciones as avg_rating' => function ($v) {
                            $v->where('aprobado', true);
                        }], 'puntuacion')
                        ->having('avg_rating', '>', 0)
                        ->orderByDesc('avg_rating')
                        ->take($limite)
                        ->get();
                }

                $productosBase = Producto::with(['etiquetas', 'atributos'])
                    ->whereIn('id', $idsBase)
                    ->get();

                $atributoIds   = collect();
                $etiquetaIds   = collect();
                $subcategoriaIds = collect();

                foreach ($productosBase as $pb) {
                    $atributoIds      = $atributoIds->merge($pb->atributos->pluck('id'));
                    $etiquetaIds      = $etiquetaIds->merge($pb->etiquetas->pluck('id'));
                    if ($pb->id_subCategorias) $subcategoriaIds->push($pb->id_subCategorias);
                }

                $atributoIds     = $atributoIds->unique()->toArray();
                $etiquetaIds     = $etiquetaIds->unique()->toArray();
                $subcategoriaIds = $subcategoriaIds->unique()->toArray();

                // Buscar productos similares (mismo atributo, etiqueta o subcategoría)
                return $base
                    ->where(function ($q) use ($atributoIds, $etiquetaIds, $subcategoriaIds) {
                        if (!empty($subcategoriaIds)) {
                            $q->orWhereIn('id_subCategorias', $subcategoriaIds);
                        }
                        if (!empty($etiquetaIds)) {
                            $q->orWhereHas('etiquetas', fn($e) => $e->whereIn('etiquetas.id', $etiquetaIds));
                        }
                        if (!empty($atributoIds)) {
                            $q->orWhereHas('atributos', fn($a) => $a->whereIn('atributos.id', $atributoIds));
                        }
                    })
                    ->withAvg(['valoraciones as avg_rating' => function ($v) {
                        $v->where('aprobado', true);
                    }], 'puntuacion')
                    ->orderByDesc('avg_rating')
                    ->orderByDesc('created_at')
                    ->take($limite)
                    ->get();
        }

        return collect();
    }

    /**
     * Nombre legible de la sección
     */
    private function nombreLegibleSeccion(string $seccion): string
    {
        return match($seccion) {
            'destacados'   => 'Productos Destacados',
            'nuevos'       => 'Nuevos Productos',
            'ofertas'      => 'Ofertas Especiales',
            'mas_vendidos' => 'Más Vendidos',
            'recomendados' => 'Recomendados para ti',
            default        => ucfirst(str_replace('_', ' ', $seccion)),
        };
    }

    private function iconoSeccion(string $seccion): string
    {
        return match($seccion) {
            'destacados'   => 'bi-star-fill',
            'nuevos'       => 'bi-badge-ad',
            'ofertas'      => 'bi-tag-fill',
            'mas_vendidos' => 'bi-trophy-fill',
            'recomendados' => 'bi-person-fill',
            default        => 'bi-grid-fill',
        };
    }

    public function productos()
    {
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.productos',
        ]));
    }

    public function contacto()
    {
        $base    = $this->getBaseConfig();
        $empresa = EmpresaInformacion::first();

        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.contacto',
            'empresa'    => $empresa,
        ]));
    }

    private function obtenerCorreosPorTipoNotificacion(string $slugTipo): array
    {
        $tipo = TipoNotificacion::where('slug', $slugTipo)->first();
        if (!$tipo) {
            return [];
        }

        $rolesConPermiso = PermisoNotificacion::where('tipo_notificacion_id', $tipo->id)
            ->where('puede_ver', true)
            ->pluck('rol_id')
            ->toArray();

        if (empty($rolesConPermiso)) {
            return [];
        }

        return User::query()
            ->whereIn('id_rol', $rolesConPermiso)
            ->where('estado', true)
            ->whereNotNull('email')
            ->whereDoesntHave('excepcionesNotificaciones', function ($q) use ($tipo) {
                $q->where('tipo_notificacion_id', $tipo->id)
                ->where('tipo_excepcion', 'denegar');
            })
            ->pluck('email')
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }

    private function obtenerConfigSmtp(): ?array
    {
        $config = \App\Models\ConfiguracionCorreo::getActiva();
        if (!$config) {
            return null;
        }

        $errores = $config->validarConfiguracion();
        if (!empty($errores)) {
            \Log::warning('Configuración SMTP incompleta: ' . implode(', ', $errores));
            return null;
        }

        $password = $config->contraseña; 
        if (empty($password)) {
            \Log::warning('No se pudo desencriptar la contraseña SMTP.');
            return null;
        }

        return [
            'transport'  => 'smtp',
            'host'       => $config->servidor_correo,
            'port'       => (int) $config->puerto,
            'encryption' => $config->seguridad === 'ninguna' ? null : $config->seguridad,
            'username'   => $config->nombre_acceso,
            'password'   => $password,
            'timeout'    => 30,
        ];
    }

    private function enviarEmailContacto(ContactoMensaje $contacto, array $destinatarios): bool
    {
        $smtp = $this->obtenerConfigSmtp();
        if (!$smtp) {
            \Log::warning('No se envía email de contacto: configuración SMTP no disponible.');
            return false;
        }

        config(['mail.mailers.contacto_sistema' => $smtp]);

        $fromEmail = $smtp['username'];
        $fromName  = ConfiguracionHelper::getCompanyName() ?: 'Contacto';

        $asunto = 'Contacto: ' . $contacto->asunto;
        $html = $this->buildEmailContactoHtml($contacto, $fromName);

        try {
            Mail::mailer('contacto_sistema')->html($html, function ($msg) use (
                $destinatarios, $fromEmail, $fromName, $asunto, $contacto
            ) {
                $msg->to($destinatarios)
                    ->from($fromEmail, $fromName)
                    ->subject($asunto)
                    ->replyTo($contacto->email, $contacto->nombre);
            });

            return true;
        } catch (\Throwable $e) {
            \Log::error('Error al enviar email de contacto: ' . $e->getMessage(), [
                'contacto_id' => $contacto->id,
                'destinatarios' => $destinatarios,
            ]);
            return false;
        }
    }

    private function buildEmailContactoHtml(ContactoMensaje $contacto, string $empresaNombre): string
    {
        $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $nombre    = $e($contacto->nombre);
        $email     = $e($contacto->email);
        $telefono  = $e($contacto->telefono ?: '—');
        $asuntoTxt = $e($contacto->asunto);
        $mensaje   = nl2br($e($contacto->mensaje));
        $fecha     = now()->format('d/m/Y H:i');
        $empresa   = $e($empresaNombre);
        $anio      = date('Y');
        $contactoId = (int) $contacto->id;
        $ip        = $e($contacto->ip ?? '—');

        $primary   = '#f4ab27';
        $secondary = '#222222';
        $dark      = '#222222';
        $gray      = '#f5f5f5';
        $grayText  = '#666666';
        $light     = '#ffffff';
        $border    = '#eeeeee';
        $warning   = '#f39c12';

        return <<<HTML
    <!DOCTYPE html>
    <html lang="es">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo mensaje de contacto</title>
    </head>
    <body style="margin:0;padding:0;background-color:{$gray};font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:{$dark};line-height:1.6;-webkit-font-smoothing:antialiased;">
    <div style="display:none;font-size:1px;color:{$gray};line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        Nuevo mensaje de {$nombre} — {$asuntoTxt}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{$gray};padding:24px 12px;">
        <tr>
        <td align="center">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background-color:{$light};border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.08);">

            <tr>
                <td style="background:linear-gradient(135deg,{$secondary} 0%,#111111 100%);padding:28px 32px;text-align:center;">
                <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{$primary};font-weight:700;margin-bottom:6px;">
                    Nuevo mensaje de contacto
                </div>
                <div style="font-size:22px;font-weight:700;color:{$light};margin:0;">
                    {$empresa}
                </div>
                </td>
            </tr>

            <tr>
                <td style="padding:24px 32px 0 32px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                        style="background-color:rgba(244,171,39,0.10);border-left:4px solid {$primary};border-radius:8px;">
                    <tr>
                    <td style="padding:14px 18px;">
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:{$grayText};font-weight:600;margin-bottom:4px;">
                        Asunto
                        </div>
                        <div style="font-size:16px;font-weight:700;color:{$dark};">
                        {$asuntoTxt}
                        </div>
                    </td>
                    </tr>
                </table>
                </td>
            </tr>

            <tr>
                <td style="padding:24px 32px 0 32px;">
                <div style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:{$grayText};font-weight:700;margin-bottom:12px;">
                    Datos del remitente
                </div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                    <tr>
                    <td style="padding:10px 0;border-bottom:1px solid {$border};width:110px;font-size:13px;color:{$grayText};font-weight:600;">Nombre</td>
                    <td style="padding:10px 0;border-bottom:1px solid {$border};font-size:14px;color:{$dark};font-weight:500;">{$nombre}</td>
                    </tr>
                    <tr>
                    <td style="padding:10px 0;border-bottom:1px solid {$border};font-size:13px;color:{$grayText};font-weight:600;">Email</td>
                    <td style="padding:10px 0;border-bottom:1px solid {$border};font-size:14px;">
                        <a href="mailto:{$email}" style="color:{$primary};text-decoration:none;font-weight:500;">{$email}</a>
                    </td>
                    </tr>
                    <tr>
                    <td style="padding:10px 0;border-bottom:1px solid {$border};font-size:13px;color:{$grayText};font-weight:600;">Teléfono</td>
                    <td style="padding:10px 0;border-bottom:1px solid {$border};font-size:14px;color:{$dark};">{$telefono}</td>
                    </tr>
                    <tr>
                    <td style="padding:10px 0;border-bottom:1px solid {$border};font-size:13px;color:{$grayText};font-weight:600;">Fecha</td>
                    <td style="padding:10px 0;border-bottom:1px solid {$border};font-size:14px;color:{$dark};">{$fecha}</td>
                    </tr>
                    <tr>
                    <td style="padding:10px 0;font-size:13px;color:{$grayText};font-weight:600;">IP</td>
                    <td style="padding:10px 0;font-size:14px;color:{$dark};">{$ip}</td>
                    </tr>
                </table>
                </td>
            </tr>

            <tr>
                <td style="padding:24px 32px 0 32px;">
                <div style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:{$grayText};font-weight:700;margin-bottom:12px;">
                    Mensaje
                </div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                        style="background-color:{$gray};border-radius:12px;border:1px solid {$border};">
                    <tr>
                    <td style="padding:20px;font-size:14px;line-height:1.7;color:{$dark};">
                        {$mensaje}
                    </td>
                    </tr>
                </table>
                </td>
            </tr>

            <tr>
                <td style="padding:28px 32px 8px 32px;text-align:center;">
                <a href="mailto:{$email}?subject=Re: {$asuntoTxt}"
                    style="display:inline-block;background-color:{$primary};color:{$secondary};font-size:14px;font-weight:700;padding:12px 28px;border-radius:40px;text-decoration:none;">
                    Responder a {$nombre}
                </a>
                </td>
            </tr>

            <tr>
                <td style="padding:24px 32px 28px 32px;">
                <div style="border-top:1px solid {$border};padding-top:16px;text-align:center;">
                    <div style="font-size:12px;color:{$grayText};line-height:1.6;">
                    Este correo se generó automáticamente desde el formulario de contacto de<br>
                    <strong style="color:{$dark};">{$empresa}</strong>
                    </div>
                    <div style="font-size:11px;color:#999999;margin-top:10px;">
                    Ref. contacto #{$contactoId} · {$fecha} · &copy; {$anio} {$empresa}
                    </div>
                </div>
                </td>
            </tr>

            </table>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">
            <tr>
                <td style="padding:16px 20px;text-align:center;font-size:11px;color:#999999;line-height:1.5;">
                Si no reconoces este mensaje, puedes ignorarlo.
                </td>
            </tr>
            </table>

        </td>
        </tr>
    </table>

    </body>
    </html>
    HTML;
    }

    public function enviarContacto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string|max:120',
            'email'    => 'required|email|max:150',
            'telefono' => 'nullable|string|max:30',
            'asunto'   => 'required|string|max:150',
            'mensaje'  => 'required|string|min:10|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Verifica los datos ingresados.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $contacto = ContactoMensaje::create([
                'nombre'     => $request->nombre,
                'email'      => $request->email,
                'telefono'   => $request->telefono,
                'asunto'     => $request->asunto,
                'mensaje'    => $request->mensaje,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'estado'     => 'nuevo',
            ]);

            $this->notificarContactoAAdmins($contacto);
            $destinatarios = $this->obtenerCorreosPorTipoNotificacion('contacto');

            if (empty($destinatarios)) {
                $emailSistema = ConfiguracionHelper::getEmail();
                if ($emailSistema) {
                    $destinatarios = [$emailSistema];
                }
            }

            $emailEnviado = false;
            if (!empty($destinatarios)) {
                $emailEnviado = $this->enviarEmailContacto($contacto, $destinatarios);
            }

            return response()->json([
                'success'     => true,
                'message'     => '¡Mensaje enviado! Te responderemos a la brevedad.',
                'data'        => [
                    'id'            => $contacto->id,
                    'estado'        => $contacto->estado,
                    'email_enviado' => $emailEnviado,
                    'destinatarios' => count($destinatarios),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en formulario de contacto: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar tu mensaje. Intenta más tarde.',
            ], 500);
        }
    }

    private function notificarContactoAAdmins(ContactoMensaje $contacto): void
    {
        $tipo = TipoNotificacion::where('slug', 'contacto')->first();
        if (!$tipo) {
            \Log::warning('No existe el tipo de notificación "contacto"');
            return;
        }

        $rolesConPermiso = PermisoNotificacion::where('tipo_notificacion_id', $tipo->id)
            ->where('puede_ver', true)
            ->pluck('rol_id')
            ->toArray();

        if (empty($rolesConPermiso)) {
            \Log::warning("Ningún rol tiene permiso para el tipo 'contacto'");
            return;
        }

        $usuarios = User::query()
            ->whereIn('id_rol', $rolesConPermiso)
            ->where('estado', true) 
            ->whereDoesntHave('excepcionesNotificaciones', function ($q) use ($tipo) {
                $q->where('tipo_notificacion_id', $tipo->id)
                ->where('tipo_excepcion', 'denegar');
            })
            ->pluck('id')
            ->toArray();

        if (empty($usuarios)) {
            return;
        }

        $notificacion = Notificacion::create([
            'tipo_notificacion_id' => $tipo->id,
            'creado_por'           => null,
            'titulo'               => 'Nuevo mensaje de contacto: ' . $contacto->asunto,
            'mensaje'              => "De: {$contacto->nombre} ({$contacto->email})\n\n"
                                    . $contacto->mensaje,
            'mensaje_corto'        => "{$contacto->nombre} — {$contacto->asunto}",
            'data_extra'           => [
                'contacto_id' => $contacto->id,
                'email'       => $contacto->email,
                'telefono'    => $contacto->telefono,
                'asunto'      => $contacto->asunto,
            ],
            'url'                  => route('administracionDelSistema.contacto-mensajes.index'),
            'boton_texto'          => 'Ver mensaje',
            'prioridad'            => 'media',
            'fecha_inicio'         => now(),
            'fecha_fin'            => null,
            'usuario_id'           => null,
            'rol_id'               => null,   
            'visible'              => true,
            'eliminada'            => false,
        ]);

        $notificacion->usuarios()->syncWithoutDetaching($usuarios);
    }

    public function productosPorCategoria($id)
    {
        $categoria = Categoria::with('subcategorias')->findOrFail($id);
        $subcategoriaIds = $categoria->subcategorias->pluck('id')->toArray();

        $productos = Producto::with([
                'imagenes',
                'valoraciones' => function($q) {
                    $q->where('aprobado', true);
                },
                'variaciones' 
            ])
            ->whereIn('id_subCategorias', $subcategoriaIds)
            ->where('estado', 'publicado')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();
        
        $productos = $this->procesarVariacionesTarjeta($productos);

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.categoria',
            'categoria'  => $categoria,
            'productos'  => $productos,
        ]));
    }

    public function productosPorSubcategoria(Request $request, $id)
    {
        $subcategoria = Subcategoria::with('categoria')->findOrFail($id);
        $categoria = $subcategoria->categoria;
        $subcategoriaId = $subcategoria->id;

        // ---------- FILTRO PRINCIPAL DE PRODUCTOS (solo en esta subcategoría) ----------
        $query = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado')
            ->where(function ($q) use ($subcategoriaId) {
                $q->where('id_subCategorias', $subcategoriaId)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaId) {
                    $sub->where('id_subCategorias', $subcategoriaId)
                        ->where('estado', 'publicado');
                });
            });

        // Filtro por atributo_termino (igual que en categoría)
        if ($request->filled('atributo_termino')) {
            $terminos = (array)$request->atributo_termino;
            $query->where(function ($mainQuery) use ($terminos, $subcategoriaId) {
                foreach ($terminos as $terminoId) {
                    $mainQuery->orWhereExists(function ($existsQuery) use ($terminoId, $subcategoriaId) {
                        $existsQuery->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($catQuery) use ($subcategoriaId) {
                                $catQuery->where('p2.id_subCategorias', $subcategoriaId)
                                    ->orWhereExists(function ($subQuery) use ($subcategoriaId) {
                                        $subQuery->select(DB::raw(1))
                                            ->from('producto_agrupado as pa')
                                            ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                            ->whereColumn('pa.producto_padre_id', 'p2.id')
                                            ->where('hijo.id_subCategorias', $subcategoriaId)
                                            ->where('hijo.estado', 'publicado');
                                    });
                            })
                            ->where(function ($attrQuery) use ($terminoId) {
                                // Los 4 caminos para que un producto tenga el término (igual que antes)
                                $attrQuery->whereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                            });
                    });
                }
            });
        }

        // Filtro rating mínimo
        if ($request->filled('rating_min') && $request->rating_min >= 1) {
            $ratingMin = (int)$request->rating_min;
            $query->whereHas('valoraciones', function ($q) use ($ratingMin) {
                $q->select('producto_id', \DB::raw('AVG(puntuacion) as avg_rating'))
                ->groupBy('producto_id')
                ->havingRaw('AVG(puntuacion) >= ?', [$ratingMin]);
            });
        }

        // Orden
        switch ($request->get('orden', 'novedad')) {
            case 'precio_asc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL AND precio_rebajado > 0 AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE(
                                (SELECT MIN(CASE WHEN pv.precio_rebajado IS NOT NULL AND pv.precio_rebajado > 0 AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW()) THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1),
                                999999999
                            )
                        ELSE 999999999
                    END ASC
                ");
                break;
            case 'precio_desc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL AND precio_rebajado > 0 AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE(
                                (SELECT MIN(CASE WHEN pv.precio_rebajado IS NOT NULL AND pv.precio_rebajado > 0 AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW()) THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1),
                                0
                            )
                        ELSE 0
                    END DESC
                ");
                break;
            case 'nombre': $query->orderBy('nombre', 'asc'); break;
            default: $query->orderBy('created_at', 'desc');
        }

        $productos = $query->paginate(32)->appends($request->query());

        $productos->getCollection()->transform(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });

        // ---------- OBTENER ATRIBUTOS Y TÉRMINOS CON CONTADORES REALES (para esta subcategoría) ----------
        $atributosConTerminos = [];

        // Obtener IDs de atributos que aparecen en la subcategoría (directa o mediante hijos agrupados)
        $atributosIds = Producto::where('estado', 'publicado')
            ->where(function ($q) use ($subcategoriaId) {
                $q->where('id_subCategorias', $subcategoriaId)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaId) {
                    $sub->where('id_subCategorias', $subcategoriaId)
                        ->where('estado', 'publicado');
                });
            })
            ->where(function ($q) {
                $q->whereHas('valoresAtributos')
                ->orWhereHas('productosHijos.valoresAtributos')
                ->orWhereHas('variaciones.atributos')
                ->orWhereHas('productosHijos.variaciones.atributos');
            })
            ->join('producto_atributo', 'productos.id', '=', 'producto_atributo.producto_id')
            ->distinct()
            ->pluck('producto_atributo.atributo_id');

        foreach ($atributosIds as $attrId) {
            $atributo = Atributo::find($attrId);
            if (!$atributo) continue;

            $terminosConConteo = $atributo->terminos->map(function ($termino) use ($subcategoriaId) {
                $count = Producto::where('estado', 'publicado')
                    ->where(function ($q) use ($subcategoriaId) {
                        $q->where('id_subCategorias', $subcategoriaId)
                        ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaId) {
                            $sub->where('id_subCategorias', $subcategoriaId)
                                ->where('estado', 'publicado');
                        });
                    })
                    ->whereExists(function ($exists) use ($termino, $subcategoriaId) {
                        $exists->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($cat) use ($subcategoriaId) {
                                $cat->where('p2.id_subCategorias', $subcategoriaId)
                                    ->orWhereExists(function ($sub) use ($subcategoriaId) {
                                        $sub->select(DB::raw(1))
                                            ->from('producto_agrupado as pa')
                                            ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                            ->whereColumn('pa.producto_padre_id', 'p2.id')
                                            ->where('hijo.id_subCategorias', $subcategoriaId)
                                            ->where('hijo.estado', 'publicado');
                                    });
                            })
                            ->where(function ($attr) use ($termino) {
                                // Los 4 caminos para que el término esté presente en el producto
                                $attr->whereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                            });
                    })->count();

                $termino->producto_atributos_count = $count;
                return $termino;
            })->filter(fn($t) => $t->producto_atributos_count > 0)
            ->sortByDesc('producto_atributos_count');

            if ($terminosConConteo->isNotEmpty()) {
                $atributo->terminos = $terminosConConteo;
                $atributosConTerminos[] = $atributo;
            }
        }

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2'          => 'tienda.subcategoria',
            'subcategoria'        => $subcategoria,
            'categoria'           => $categoria,
            'productos'           => $productos,
            'atributosConTerminos'=> $atributosConTerminos,
            'filtros'             => $request->all()
        ]));
    }

    public function todosProductos(Request $request)
    {
        // ---------- QUERY BASE: todos los productos publicados ----------
        $query = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado');

        // ---------- FILTRO POR CATEGORÍA (si se envía) ----------
        $categoriasSeleccionadas = null;
        if ($request->filled('categoria')) {
            $categoriaIds = (array)$request->categoria;
            $categoriasSeleccionadas = Categoria::whereIn('id', $categoriaIds)->get();
            
            if ($categoriasSeleccionadas->isNotEmpty()) {
                // Obtener todos los IDs de subcategorías de las categorías seleccionadas
                $subcategoriaIds = collect();
                foreach ($categoriasSeleccionadas as $categoria) {
                    $subcategoriaIds = $subcategoriaIds->merge($categoria->subcategorias->pluck('id'));
                }
                $subcategoriaIds = $subcategoriaIds->unique()->toArray();
                
                $query->where(function ($q) use ($subcategoriaIds) {
                    $q->whereIn('id_subCategorias', $subcategoriaIds)
                    ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                        $sub->whereIn('id_subCategorias', $subcategoriaIds)
                            ->where('estado', 'publicado');
                    });
                });
            }
        }

        // ---------- FILTRO POR SUBCATEGORÍA (si se envía) ----------
        if ($request->filled('subcategoria')) {
            $subcategoriaIds = (array)$request->subcategoria;
            $query->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds);
                });
            });
        }

        // ---------- FILTRO POR ATRIBUTO_TÉRMINO ----------
        if ($request->filled('atributo_termino')) {
            $terminos = (array)$request->atributo_termino;
            $query->where(function ($mainQuery) use ($terminos) {
                foreach ($terminos as $terminoId) {
                    $mainQuery->orWhereExists(function ($existsQuery) use ($terminoId) {
                        $existsQuery->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($attrQuery) use ($terminoId) {
                                $attrQuery->whereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                            });
                    });
                }
            });
        }

        // ---------- FILTRO RATING MÍNIMO ----------
        if ($request->filled('rating_min') && $request->rating_min >= 1) {
            $ratingMin = (int)$request->rating_min;
            $query->whereHas('valoraciones', function ($q) use ($ratingMin) {
                $q->select('producto_id', DB::raw('AVG(puntuacion) as avg_rating'))
                ->groupBy('producto_id')
                ->havingRaw('AVG(puntuacion) >= ?', [$ratingMin]);
            });
        }

        // ---------- ORDEN ----------
        switch ($request->get('orden', 'novedad')) {
            case 'precio_asc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL AND precio_rebajado > 0 
                                    AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE(
                                (SELECT MIN(CASE WHEN pv.precio_rebajado IS NOT NULL AND pv.precio_rebajado > 0 
                                                AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW()) 
                                            THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1),
                                999999999
                            )
                        ELSE 999999999
                    END ASC
                ");
                break;
            case 'precio_desc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL AND precio_rebajado > 0 
                                    AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE(
                                (SELECT MIN(CASE WHEN pv.precio_rebajado IS NOT NULL AND pv.precio_rebajado > 0 
                                                AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW()) 
                                            THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1),
                                0
                            )
                        ELSE 0
                    END DESC
                ");
                break;
            case 'nombre':
                $query->orderBy('nombre', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $productos = $query->paginate(32)->appends($request->query());
            $productos->getCollection()->transform(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });

        // ---------- OBTENER CATEGORÍAS (para el select de filtro) ----------
        $categorias = Categoria::with(['subcategorias' => function($q) {
            $q->withCount(['productos' => function($p) {
                $p->where('estado', 'publicado');
            }]);
        }])->withCount(['productos' => function($p) {
            $p->where('estado', 'publicado');
        }])->get();

        // ---------- OBTENER SUBCATEGORÍAS DISPONIBLES (para el filtro) ----------
        $subcategoriasDisponibles = collect();
        if ($request->filled('categoria')) {
            $categoriaIds = (array)$request->categoria;
            $subcategoriasDisponibles = Subcategoria::whereIn('id_categoria', $categoriaIds)
                ->withCount(['productos' => function($q) {
                    $q->where('estado', 'publicado');
                }])
                ->get();
        }

        // ---------- OBTENER ATRIBUTOS Y TÉRMINOS CON CONTADORES REALES ----------
        $atributosConTerminos = [];

        // Subconsulta para obtener los productos que cumplen los filtros actuales
        $productosFiltradosQuery = Producto::where('estado', 'publicado');
        
        if ($request->filled('categoria') && isset($categoriasSeleccionadas) && $categoriasSeleccionadas->isNotEmpty()) {
            $subcategoriaIds = collect();
            foreach ($categoriasSeleccionadas as $categoria) {
                $subcategoriaIds = $subcategoriaIds->merge($categoria->subcategorias->pluck('id'));
            }
            $subcategoriaIds = $subcategoriaIds->unique()->toArray();
            
            $productosFiltradosQuery->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds);
                });
            });
        }
        
        if ($request->filled('subcategoria')) {
            $subcategoriaIds = (array)$request->subcategoria;
            $productosFiltradosQuery->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds);
                });
            });
        }
        
        if ($request->filled('atributo_termino')) {
            $terminos = (array)$request->atributo_termino;
            $productosFiltradosQuery->where(function ($mainQuery) use ($terminos) {
                foreach ($terminos as $terminoId) {
                    $mainQuery->orWhereExists(function ($existsQuery) use ($terminoId) {
                        $existsQuery->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($attrQuery) use ($terminoId) {
                                $attrQuery->whereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                                $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $terminoId);
                                });
                            });
                    });
                }
            });
        }

        // Extraer los atributos y términos con contadores
        $atributosIds = (clone $productosFiltradosQuery)
            ->where(function ($q) {
                $q->whereHas('valoresAtributos')
                ->orWhereHas('productosHijos.valoresAtributos')
                ->orWhereHas('variaciones.atributos')
                ->orWhereHas('productosHijos.variaciones.atributos');
            })
            ->join('producto_atributo', 'productos.id', '=', 'producto_atributo.producto_id')
            ->distinct()
            ->pluck('producto_atributo.atributo_id');

        foreach ($atributosIds as $attrId) {
            $atributo = Atributo::find($attrId);
            if (!$atributo) continue;

            $terminosConConteo = $atributo->terminos->map(function ($termino) use ($productosFiltradosQuery) {
                $count = (clone $productosFiltradosQuery)
                    ->whereExists(function ($exists) use ($termino) {
                        $exists->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($attr) use ($termino) {
                                $attr->whereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                            });
                    })->count();

                $termino->producto_atributos_count = $count;
                return $termino;
            })->filter(fn($t) => $t->producto_atributos_count > 0)
            ->sortByDesc('producto_atributos_count');

            if ($terminosConConteo->isNotEmpty()) {
                $atributo->terminos = $terminosConConteo;
                $atributosConTerminos[] = $atributo;
            }
        }

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.todos-productos',
            'productos' => $productos,
            'categorias' => $categorias,
            'subcategoriasDisponibles' => $subcategoriasDisponibles, 
            'atributosConTerminos' => $atributosConTerminos,
            'filtros' => $request->all()
        ]));
    }

    public function ofertas(Request $request)
    {
        $query = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado')
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('tipo_producto', 'simple')
                        ->whereNotNull('precio_rebajado')
                        ->where('precio_rebajado', '>', 0)
                        ->where(function ($f) {
                            $f->whereNull('fecha_fin_rebaja')
                            ->orWhere('fecha_fin_rebaja', '>=', now());
                        });
                })
                ->orWhere(function ($sub) {
                    $sub->where('tipo_producto', 'variable')
                        ->whereHas('variaciones', function ($v) {
                            $v->where('activo', true)
                            ->whereNotNull('precio_rebajado')
                            ->where('precio_rebajado', '>', 0)
                            ->where(function ($f) {
                                $f->whereNull('fecha_fin_rebaja')
                                    ->orWhere('fecha_fin_rebaja', '>=', now());
                            });
                        });
                })
                ->orWhere(function ($sub) {
                    $sub->where('tipo_producto', 'agrupado')
                        ->whereHas('productosHijos', function ($h) {
                            $h->where('estado', 'publicado')
                            ->where(function ($f) {
                                $f->where(function ($s) {
                                    $s->where('tipo_producto', 'simple')
                                        ->whereNotNull('precio_rebajado')
                                        ->where('precio_rebajado', '>', 0)
                                        ->where(function ($ff) {
                                            $ff->whereNull('fecha_fin_rebaja')
                                            ->orWhere('fecha_fin_rebaja', '>=', now());
                                        });
                                })
                                ->orWhere(function ($v) {
                                    $v->where('tipo_producto', 'variable')
                                        ->whereHas('variaciones', function ($vv) {
                                            $vv->where('activo', true)
                                            ->whereNotNull('precio_rebajado')
                                            ->where('precio_rebajado', '>', 0)
                                            ->where(function ($ff) {
                                                $ff->whereNull('fecha_fin_rebaja')
                                                    ->orWhere('fecha_fin_rebaja', '>=', now());
                                            });
                                        });
                                });
                            });
                        });
                });
            });

        $categoriasSeleccionadas = null;
        if ($request->filled('categoria')) {
            $categoriaIds = (array) $request->categoria;
            $categoriasSeleccionadas = Categoria::whereIn('id', $categoriaIds)->get();
            if ($categoriasSeleccionadas->isNotEmpty()) {
                $subIds = collect();
                foreach ($categoriasSeleccionadas as $cat) {
                    $subIds = $subIds->merge($cat->subcategorias->pluck('id'));
                }
                $subIds = $subIds->unique()->toArray();

                $query->where(function ($q) use ($subIds) {
                    $q->whereIn('id_subCategorias', $subIds)
                    ->orWhereHas('productosHijos', fn($h) => $h->whereIn('id_subCategorias', $subIds));
                });
            }
        }

        $subcategoriasDisponibles = collect();
        if ($request->filled('categoria') && $categoriasSeleccionadas) {
            $categoriaIds = (array) $request->categoria;
            $subcategoriasDisponibles = Subcategoria::whereIn('id_categoria', $categoriaIds)
                ->withCount(['productos' => fn($q) => $q->where('estado', 'publicado')])
                ->get();
        }

        if ($request->filled('subcategoria')) {
            $subIds = (array) $request->subcategoria;
            $query->where(function ($q) use ($subIds) {
                $q->whereIn('id_subCategorias', $subIds)
                ->orWhereHas('productosHijos', fn($h) => $h->whereIn('id_subCategorias', $subIds));
            });
        }

        if ($request->filled('rating_min') && $request->rating_min >= 1) {
            $ratingMin = (int) $request->rating_min;
            $query->whereHas('valoraciones', function ($q) use ($ratingMin) {
                $q->select('producto_id', DB::raw('AVG(puntuacion) as avg_rating'))
                ->groupBy('producto_id')
                ->havingRaw('AVG(puntuacion) >= ?', [$ratingMin]);
            });
        }

        switch ($request->get('orden', 'descuento')) {
            case 'precio_asc':
                $query->orderByRaw("
                    CASE
                        WHEN tipo_producto = 'simple' AND precio_rebajado > 0 THEN precio_rebajado
                        WHEN tipo_producto = 'simple' THEN precio_regular
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE((SELECT MIN(CASE WHEN pv.precio_rebajado > 0 THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                    FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1), 999999999)
                        ELSE 999999999
                    END ASC
                ");
                break;
            case 'precio_desc':
                $query->orderByRaw("
                    CASE
                        WHEN tipo_producto = 'simple' AND precio_rebajado > 0 THEN precio_rebajado
                        WHEN tipo_producto = 'simple' THEN precio_regular
                        WHEN tipo_producto IN ('variable','agrupado') THEN
                            COALESCE((SELECT MIN(CASE WHEN pv.precio_rebajado > 0 THEN pv.precio_rebajado ELSE pv.precio_regular END)
                                    FROM producto_variaciones pv WHERE pv.producto_padre_id = productos.id AND pv.activo = 1), 0)
                        ELSE 0
                    END DESC
                ");
                break;
            case 'nombre':
                $query->orderBy('nombre', 'asc');
                break;
            case 'novedad':
                $query->orderBy('created_at', 'desc');
                break;
            default: // descuento
                $query->orderByRaw("
                    CASE
                        WHEN tipo_producto = 'simple' AND precio_regular > 0 AND precio_rebajado > 0
                            THEN (1 - precio_rebajado / precio_regular) * 100
                        ELSE 0
                    END DESC
                ");
        }

        $productos = $query->paginate(24)->appends($request->query());

        $productos->getCollection()->transform(function ($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });

        $categorias = Categoria::with(['subcategorias' => function ($q) {
            $q->withCount(['productos' => fn($p) => $p->where('estado', 'publicado')]);
        }])->withCount(['productos' => fn($p) => $p->where('estado', 'publicado')])->get();

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2'              => 'tienda.ofertas',
            'productos'               => $productos,
            'categorias'              => $categorias,
            'subcategoriasDisponibles'=> $subcategoriasDisponibles,
            'filtros'                 => $request->all(),
        ]));
    }

    public function nosotros()
    {
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.nosotros',
        ]));
    }

    public function detalleProducto($id)
    {
        $producto = Producto::with([
            'imagenes',
            'variaciones' => function($q) {
                $q->where('activo', true)
                ->with(['atributos.atributo', 'imagenes']);
            },
            'subcategoria.categoria',
            'etiquetas',
            'valoraciones' => function($q) {
                $q->where('aprobado', true)->with('usuario');
            },
            'productosRelacionados' => function($q) {
                $q->with(['imagenes', 'valoraciones']);
            },
            'atributos' => function($q) {
                $q->withPivot('visible', 'variacion', 'tipo', 'shape');
            }
        ])->findOrFail($id);
        
        $valorExtraMap = [];
        $productoAtributos = ProductoAtributo::where('producto_id', $producto->id)
            ->with(['valores' => function($q) {
                $q->withPivot('valor_extra');
            }])
            ->get();
            
        foreach ($productoAtributos as $pa) {
            foreach ($pa->valores as $valor) {
                $key = $pa->atributo_id . '_' . $valor->id;
                $valorExtraMap[$key] = $valor->pivot->valor_extra;
            }
        }
        
        if ($producto->productosRelacionados->isNotEmpty()) {
            $producto->productosRelacionados = $this->procesarVariacionesTarjeta($producto->productosRelacionados);
        }
        
        $variacionesAgrupadas = $this->getVariacionesAgrupadas($producto);
        
        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2' => 'tienda.producto-detalle',
            'producto' => $producto,
            'variacionesAgrupadas' => $variacionesAgrupadas,
            'valorExtraMap' => $valorExtraMap,
            'script' => 'js/tienda-producto-detalle.js',
        ]));
    }

    public function getOrdenAtributos(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id'
        ]);

        try {
            $productoId = $request->producto_id;

            // 🔥 Obtener orden desde atributo_prioridad
            $ordenAtributos = \App\Models\AtributoPrioridad::where('producto_id', $productoId)
                ->orderBy('prioridad', 'asc')
                ->pluck('atributo_id')
                ->toArray();

            // Si no hay prioridades definidas, obtener los atributos del producto
            if (empty($ordenAtributos)) {
                $ordenAtributos = ProductoAtributo::where('producto_id', $productoId)
                    ->where('variacion', 1)
                    ->pluck('atributo_id')
                    ->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => $ordenAtributos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el orden de atributos: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getVariacionesAgrupadas($producto)
{
    $variaciones = $producto->variaciones()
        ->where('activo', true)
        ->with(['atributos.atributo', 'imagenes'])
        ->get();
        
    $agrupado = [];
    
    // 🔥 PASO 1: Identificar qué atributos tiene cada variación (para detectar "Cualquier")
    $variacionesConAtributos = [];
    foreach ($variaciones as $variacion) {
        $variacionesConAtributos[$variacion->id] = [];
        foreach ($variacion->atributos as $termino) {
            $variacionesConAtributos[$variacion->id][] = $termino->atributo_id;
        }
        $variacionesConAtributos[$variacion->id] = array_unique($variacionesConAtributos[$variacion->id]);
    }
    
    // 🔥 PASO 2: Obtener términos registrados en producto_atributo_valores
    $terminosRegistrados = [];
    $productoAtributos = ProductoAtributo::where('producto_id', $producto->id)
        ->where('variacion', 1)
        ->with(['valores' => function($q) {
            // 🔥 CORREGIDO: No hacer join adicional, solo seleccionar los campos necesarios
            $q->select('atributo_terminos.id', 'atributo_terminos.nombre', 'atributo_terminos.atributo_id', 'producto_atributo_valores.valor_extra');
        }])
        ->get();
    
    foreach ($productoAtributos as $pa) {
        $atributoId = $pa->atributo_id;
        $terminosRegistrados[$atributoId] = [];
        foreach ($pa->valores as $valor) {
            $terminosRegistrados[$atributoId][] = [
                'id' => $valor->id,
                'nombre' => $valor->nombre,
                'valor_extra' => $valor->pivot->valor_extra ?? null
            ];
        }
    }
    
    // 🔥 PASO 3: Agrupar variaciones por atributo
    foreach ($variaciones as $variacion) {
        foreach ($variacion->atributos as $termino) {
            $atributoId = $termino->atributo_id;
            
            if (!isset($agrupado[$atributoId])) {
                $atributo = Atributo::find($atributoId);
                $productoAtributo = ProductoAtributo::where('producto_id', $producto->id)
                    ->where('atributo_id', $atributoId)
                    ->first();
                    
                $agrupado[$atributoId] = (object) [
                    'atributo' => $atributo,
                    'tipo' => $productoAtributo->tipo ?? 'Default',
                    'shape' => $productoAtributo->shape ?? 'Default',
                    'variaciones' => [],
                    'terminos_disponibles' => [],
                    'tiene_cualquier' => false,
                    'terminos_registrados' => $terminosRegistrados[$atributoId] ?? []
                ];
            }
            
            // Guardar términos que aparecen en variaciones
            if (!in_array($termino->id, $agrupado[$atributoId]->terminos_disponibles)) {
                $agrupado[$atributoId]->terminos_disponibles[] = $termino->id;
            }
            
            $existe = false;
            foreach ($agrupado[$atributoId]->variaciones as $v) {
                if ($v->id == $variacion->id) {
                    $existe = true;
                    break;
                }
            }
            
            if (!$existe) {
                $agrupado[$atributoId]->variaciones[] = $variacion;
            }
        }
    }
    
    // 🔥 PASO 4: Verificar si algún atributo tiene "Cualquier"
    foreach ($agrupado as $atributoId => $data) {
        foreach ($variaciones as $variacion) {
            $tieneAtributo = in_array($atributoId, $variacionesConAtributos[$variacion->id] ?? []);
            if (!$tieneAtributo) {
                $data->tiene_cualquier = true;
                break;
            }
        }
    }
    
    // 🔥 PASO 5: Obtener orden de atributos desde atributo_prioridad
    $ordenAtributos = AtributoPrioridad::where('producto_id', $producto->id)
        ->orderBy('prioridad', 'asc')
        ->pluck('atributo_id')
        ->toArray();
    
    if (!empty($ordenAtributos)) {
        $agrupadoReordenado = [];
        foreach ($ordenAtributos as $atributoId) {
            if (isset($agrupado[$atributoId])) {
                $agrupadoReordenado[$atributoId] = $agrupado[$atributoId];
            }
        }
        foreach ($agrupado as $atributoId => $data) {
            if (!isset($agrupadoReordenado[$atributoId])) {
                $agrupadoReordenado[$atributoId] = $data;
            }
        }
        $agrupado = $agrupadoReordenado;
    }
    
    // 🔥 PASO 6: Determinar qué términos mostrar
    foreach ($agrupado as $atributoId => $data) {
        // Si tiene "Cualquier", mostrar TODOS los términos registrados en producto_atributo_valores
        if ($data->tiene_cualquier) {
            $data->terminos_a_mostrar = collect($data->terminos_registrados)
                ->map(function($termino) {
                    return (object) [
                        'id' => $termino['id'],
                        'nombre' => $termino['nombre'],
                        'valor_extra' => $termino['valor_extra'] ?? null
                    ];
                })
                ->toArray();
            $data->termino_cualquier_id = null;
        } else {
            // Si NO tiene "Cualquier", mostrar SOLO los términos que aparecen en variaciones
            // y que están registrados en producto_atributo_valores
            $terminosRegistradosIds = collect($data->terminos_registrados)->pluck('id')->toArray();
            $terminosDisponiblesFiltrados = array_intersect($data->terminos_disponibles, $terminosRegistradosIds);
            
            $data->terminos_a_mostrar = collect($data->terminos_registrados)
                ->filter(function($termino) use ($terminosDisponiblesFiltrados) {
                    return in_array($termino['id'], $terminosDisponiblesFiltrados);
                })
                ->map(function($termino) {
                    return (object) [
                        'id' => $termino['id'],
                        'nombre' => $termino['nombre'],
                        'valor_extra' => $termino['valor_extra'] ?? null
                    ];
                })
                ->toArray();
            $data->termino_cualquier_id = null;
        }
    }
    
    return $agrupado;
}

    /**
     * Obtiene los detalles de una variación específica usando el procedimiento almacenado
     */
    public function getVariacionDetalle(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'variacion_id' => 'required|exists:producto_variaciones,id'
        ]);

        try {
            $result = DB::select(
                'CALL GetVariacionSeleccionada(?, ?)',
                [$request->producto_id, $request->variacion_id]
            );

            if (empty($result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la variación'
                ], 404);
            }

            $data = $result[0];
            
            // Procesar atributos combinados
            $atributos = [];
            if (!empty($data->atributos_combinados)) {
                $atributosRaw = explode('||', $data->atributos_combinados);
                foreach ($atributosRaw as $item) {
                    $parts = explode('|', $item);
                    if (count($parts) >= 4) {
                        $atributos[] = [
                            'atributo_id' => $parts[0],
                            'atributo_nombre' => $parts[1],
                            'termino_id' => !empty($parts[2]) ? $parts[2] : null,
                            'termino_nombre' => !empty($parts[3]) ? $parts[3] : 'Cualquier',
                        ];
                    }
                }
            }

            // Construir array de imágenes
            $imagenes = [];
            for ($i = 1; $i <= 6; $i++) {
                $imgField = 'img' . $i;
                if (!empty($data->$imgField)) {
                    $imagenes[] = $data->$imgField;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'variacion_id' => $data->variacion_id,
                    'sku' => $data->variacion_sku,
                    'precio_regular' => floatval($data->precio_regular),
                    'precio_rebajado' => $data->precio_rebajado ? floatval($data->precio_rebajado) : null,
                    'precio_final' => floatval($data->precio_final),
                    'descuento_porcentaje' => intval($data->descuento_porcentaje),
                    'stock' => intval($data->stock),
                    'gestion_inventario' => boolval($data->gestion_inventario),
                    'estado_inventario' => $data->estado_inventario,
                    'backorders' => boolval($data->backorders),
                    'vendido_individualmente' => boolval($data->vendido_individualmente),
                    'estado_actual' => $data->estado_actual,
                    'peso' => $data->peso ? floatval($data->peso) : null,
                    'peso_unidad' => $data->peso_unidad,
                    'longitud' => $data->longitud ? floatval($data->longitud) : null,
                    'anchura' => $data->anchura ? floatval($data->anchura) : null,
                    'altura' => $data->altura ? floatval($data->altura) : null,
                    'descripcion' => $data->variacion_descripcion,
                    'imagenes' => $imagenes,
                    'variacion_origen_id' => $data->variacion_origen_id ? intval($data->variacion_origen_id) : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles de la variación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los términos disponibles usando el procedimiento almacenado
     */
    public function getTerminosDisponibles(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'terminos' => 'required|array',
            'terminos.*' => 'exists:atributo_terminos,id'
        ]);

        try {
            $productoId = $request->producto_id;
            $terminosCsv = implode(',', $request->terminos);

            // Ejecutar el procedimiento almacenado
            $result = DB::select(
                'CALL GetTerminosDisponibles(?, ?)',
                [$productoId, $terminosCsv]
            );

            // 🔥 Agrupar resultados por atributo
            $atributos = [];
            foreach ($result as $row) {
                $atributoId = $row->atributo_id;
                if (!isset($atributos[$atributoId])) {
                    $atributos[$atributoId] = [
                        'atributo_id' => $atributoId,
                        'atributo_nombre' => $row->atributo_nombre,
                        'terminos' => []
                    ];
                }
                $atributos[$atributoId]['terminos'][] = [
                    'id' => $row->termino_id,
                    'nombre' => $row->termino_nombre,
                    'tiene_stock' => (bool)$row->tiene_stock
                ];
            }

            return response()->json([
                'success' => true,
                'data' => array_values($atributos)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function valorarProducto(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->id_rol != 2) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'puntuacion'  => 'required|integer|min:1|max:5',
            'comentario'  => 'nullable|string|max:1000'
        ]);

        $valoracion = ProductoValoracion::where([
            'producto_id' => $request->producto_id,
            'user_id'     => auth()->id()
        ])->first();

        if ($valoracion) {
            $valoracion->update([
                'puntuacion' => $request->puntuacion,
                'comentario' => $request->comentario,
                'aprobado'   => true,
                'updated_at' => now() 
            ]);
        } else {
            // Si no existe, crear nueva
            $valoracion = ProductoValoracion::create([
                'producto_id' => $request->producto_id,
                'user_id'     => auth()->id(),
                'puntuacion'  => $request->puntuacion,
                'comentario'  => $request->comentario,
                'aprobado'    => true
            ]);
        }

        $producto = Producto::find($request->producto_id);
        $avg = $producto->valoraciones()->where('aprobado', true)->avg('puntuacion');
        $count = $producto->valoraciones()->where('aprobado', true)->count();

        return response()->json([
            'success' => true,
            'rating'  => round($avg, 1),
            'count'   => $count,
            'user_rating' => $valoracion->puntuacion
        ]);
    }

    public function todasCategorias()
    {
        $categorias = Categoria::with(['subcategorias' => function($q) {
            $q->withCount(['productos' => function($p) {
                $p->where('estado', 'publicado');
            }]);
        }])->get();

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2'  => 'tienda.categorias',
            'categorias'  => $categorias,
        ]));
    }

    public function registroCliente(Request $request)
    {
        $request->validate([
            'nombres'      => 'required|string|max:150',
            'apellidos'    => 'required|string|max:150',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:6',
            'tipoDoc'      => 'nullable|string|max:50',
            'numeroDoc'    => 'nullable|string|max:20|unique:usuarios_datos,numeroDoc',
            'celular'      => 'nullable|string|max:15',
            'fecha_nacimiento' => 'nullable|date',
            'nacionalidad' => 'nullable|string|max:100',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'provincia_id' => 'nullable|exists:provincias,id',
            'distrito_id'  => 'nullable|exists:distritos,id',
            'calle'        => 'nullable|string|max:255',
            'numero'       => 'nullable|string|max:50',
            'dir_otros'    => 'nullable|string|max:255',
            'cod_postal'   => 'nullable|string|max:20',
        ]);

        // 🔥 Forzar rol client (ID 2)
        $rol = Rol::find(2); // client
        
        if (!$rol) {
            return response()->json(['success' => false, 'message' => 'Rol de cliente no configurado'], 500);
        }

        // Crear usuario
        $user = User::create([
            'nombres'    => $request->nombres,
            'apellidos'  => $request->apellidos,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'id_rol'     => $rol->id, // Siempre 2 (client)
            'estado'     => true,
            'conectado'  => false,
            'dark_mode'  => false,
        ]);

        // 🔥 CREAR DATOS DEL USUARIO EN TABLA usuarios_datos
        $usuarioDato = \App\Models\UsuarioDato::create([
            'id_usuario'      => $user->id,
            'tipoDoc'         => $request->tipoDoc,
            'numeroDoc'       => $request->numeroDoc,
            'celular'         => $request->celular,
            'fecha_nacimiento'=> $request->fecha_nacimiento,
            'nacionalidad'    => $request->nacionalidad,
            'departamento'    => $request->departamento_id,
            'provincia'       => $request->provincia_id,
            'distrito'        => $request->distrito_id,
            'calle'           => $request->calle,
            'numero'          => $request->numero,
            'dir_otros'       => $request->dir_otros,
            'cod_postal'      => $request->cod_postal,
        ]);
        $this->crearNotificacionBienvenida($user);
        Auth::login($user);
        $user->update(['conectado' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada exitosamente',
            'user' => [
                'id'        => $user->id,
                'nombres'   => $user->nombres,
                'apellidos' => $user->apellidos,
                'email'     => $user->email,
                'foto'      => asset('img/user.png'),
                'rol_id'    => $user->id_rol,
            ]
        ]);
    }

    public function loginCliente(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            if ($user->id_rol != 2) {
                Auth::logout();
                return response()->json([
                    'success' => false, 
                    'message' => 'Este usuario no tiene los permisos para acceder'
                ], 403);
            }
            
            $user->update(['conectado' => true]);
            return response()->json([
                'success' => true,
                'user' => [
                    'nombres'   => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email'     => $user->email,
                    'foto'      => asset('img/user.png'),
                    'rol_id'    => $user->id_rol,
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }

    public function logoutCliente(Request $request)
    {
        if (Auth::check()) {
            $carrito = Carrito::where('user_id', Auth::id())->first();
            if ($carrito) {
                $carrito->vaciar(true);
                $carrito->delete();
            }

            Auth::user()->update(['conectado' => false]);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return response()->json(['success' => true]);
    }

    public function productosCategoriaCompleta(Request $request, $id)
    {
        $categoria = Categoria::with('subcategorias')->findOrFail($id);
        $subcategoriaIds = $categoria->subcategorias->pluck('id')->toArray();

        // ---------- FILTRO PRINCIPAL DE PRODUCTOS (CONSULTA SQL EQUIVALENTE) ----------
        $query = Producto::with(['imagenes', 'valoraciones', 'variaciones'])
            ->where('estado', 'publicado')
            ->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds)
                        ->where('estado', 'publicado');
                });
            });

        // Filtro subcategorías
        if ($request->filled('subcategoria')) {
            $subs = (array)$request->subcategoria;
            $query->where(function ($q) use ($subs) {
                $q->whereIn('id_subCategorias', $subs)
                ->orWhereHas('productosHijos', function ($sub) use ($subs) {
                    $sub->whereIn('id_subCategorias', $subs);
                });
            });
        }

        // Filtro por atributo_termino (incluye directos y vía variaciones)
        if ($request->filled('atributo_termino')) {
        $terminos = (array)$request->atributo_termino;
        
        $query->where(function ($mainQuery) use ($terminos, $subcategoriaIds) {
            // Construimos una condición OR para cada término (por si se seleccionan varios)
            foreach ($terminos as $terminoId) {
                $mainQuery->orWhereExists(function ($existsQuery) use ($terminoId, $subcategoriaIds) {
                    $existsQuery->select(DB::raw(1))
                        ->from('productos as p2')
                        ->whereColumn('p2.id', 'productos.id')
                        ->where('p2.estado', 'publicado')
                        ->where(function ($catQuery) use ($subcategoriaIds) {
                            // Condición de categoría (producto pertenece a la categoría actual)
                            $catQuery->whereIn('p2.id_subCategorias', $subcategoriaIds)
                                ->orWhereExists(function ($subQuery) use ($subcategoriaIds) {
                                    $subQuery->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->whereIn('hijo.id_subCategorias', $subcategoriaIds)
                                        ->where('hijo.estado', 'publicado');
                                });
                        })
                        ->where(function ($attrQuery) use ($terminoId) {
                            // Los 4 caminos para que un producto tenga el término
                            $attrQuery->whereExists(function ($q) use ($terminoId) {
                                $q->select(DB::raw(1))
                                    ->from('producto_atributo as pa')
                                    ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                    ->whereColumn('pa.producto_id', 'p2.id')
                                    ->where('pav.termino_id', $terminoId);
                            });
                            $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                $q->select(DB::raw(1))
                                    ->from('producto_agrupado as pa')
                                    ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                    ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                    ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                    ->whereColumn('pa.producto_padre_id', 'p2.id')
                                    ->where('hijo.estado', 'publicado')
                                    ->where('pav2.termino_id', $terminoId);
                            });
                            $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                $q->select(DB::raw(1))
                                    ->from('producto_variaciones as pv')
                                    ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                    ->whereColumn('pv.producto_padre_id', 'p2.id')
                                    ->where('vat.atributo_termino_id', $terminoId);
                            });
                            $attrQuery->orWhereExists(function ($q) use ($terminoId) {
                                $q->select(DB::raw(1))
                                    ->from('producto_agrupado as pa')
                                    ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                    ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                    ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                    ->whereColumn('pa.producto_padre_id', 'p2.id')
                                    ->where('hijo.estado', 'publicado')
                                    ->where('vat.atributo_termino_id', $terminoId);
                            });
                        });
                });
            }
        });
    }

        // Filtro rating mínimo (igual)
        if ($request->filled('rating_min') && $request->rating_min >= 1) {
            $ratingMin = (int)$request->rating_min;
            $query->whereHas('valoraciones', function ($q) use ($ratingMin) {
                $q->select('producto_id', \DB::raw('AVG(puntuacion) as avg_rating'))
                ->groupBy('producto_id')
                ->havingRaw('AVG(puntuacion) >= ?', [$ratingMin]);
            });
        }

        // Orden
        switch ($request->get('orden', 'novedad')) {
            case 'precio_asc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL 
                                    AND precio_rebajado > 0
                                    AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado
                                ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable', 'agrupado') THEN
                            COALESCE(
                                (SELECT MIN(
                                    CASE 
                                        WHEN pv.precio_rebajado IS NOT NULL 
                                            AND pv.precio_rebajado > 0
                                            AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW())
                                        THEN pv.precio_rebajado
                                        ELSE pv.precio_regular
                                    END
                                ) FROM producto_variaciones pv 
                                WHERE pv.producto_padre_id = productos.id AND pv.activo = 1
                                ),
                                999999999
                            )
                        ELSE 999999999
                    END ASC
                ");
                break;
            case 'precio_desc':
                $query->orderByRaw("
                    CASE 
                        WHEN tipo_producto = 'simple' THEN
                            CASE 
                                WHEN precio_rebajado IS NOT NULL 
                                    AND precio_rebajado > 0
                                    AND (fecha_fin_rebaja IS NULL OR fecha_fin_rebaja >= NOW())
                                THEN precio_rebajado
                                ELSE precio_regular
                            END
                        WHEN tipo_producto IN ('variable', 'agrupado') THEN
                            COALESCE(
                                (SELECT MIN(
                                    CASE 
                                        WHEN pv.precio_rebajado IS NOT NULL 
                                            AND pv.precio_rebajado > 0
                                            AND (pv.fecha_fin_rebaja IS NULL OR pv.fecha_fin_rebaja >= NOW())
                                        THEN pv.precio_rebajado
                                        ELSE pv.precio_regular
                                    END
                                ) FROM producto_variaciones pv 
                                WHERE pv.producto_padre_id = productos.id AND pv.activo = 1
                                ),
                                0
                            )
                        ELSE 0
                    END DESC
                ");
                break;
            case 'nombre':
                $query->orderBy('nombre', 'asc');
                break;
            default: // novedad
                $query->orderBy('created_at', 'desc');
        }

        $productos = $query->paginate(32)->appends($request->query());
            $productos->getCollection()->transform(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });

        // ---------- CONTADOR DE SUBCATEGORÍAS (incluye agrupados) ----------
        $subcategorias = $categoria->subcategorias->map(function ($sub) {
            $count = Producto::where('estado', 'publicado')
                ->where(function ($q) use ($sub) {
                    $q->where('id_subCategorias', $sub->id)
                    ->orWhereHas('productosHijos', function ($hijos) use ($sub) {
                        $hijos->where('id_subCategorias', $sub->id);
                    });
                })->count();
            $sub->productos_count = $count;
            return $sub;
        });

        // ---------- OBTENER ATRIBUTOS Y TÉRMINOS CON CONTADORES REALES (usando la misma subconsulta que el filtro) ----------
        $atributosConTerminos = [];

        // Obtener todos los atributos que tienen al menos un término asociado a productos de la categoría (siguiendo la misma lógica)
        $atributosIds = Producto::where('estado', 'publicado')
            ->where(function ($q) use ($subcategoriaIds) {
                $q->whereIn('id_subCategorias', $subcategoriaIds)
                ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                    $sub->whereIn('id_subCategorias', $subcategoriaIds)
                        ->where('estado', 'publicado');
                });
            })
            ->where(function ($q) {
                // Restringir a productos que tengan alguna forma de atributo (para obtener solo atributos relevantes)
                $q->whereHas('valoresAtributos')
                ->orWhereHas('productosHijos.valoresAtributos')
                ->orWhereHas('variaciones.atributos')
                ->orWhereHas('productosHijos.variaciones.atributos');
            })
            ->join('producto_atributo', 'productos.id', '=', 'producto_atributo.producto_id')
            ->distinct()
            ->pluck('producto_atributo.atributo_id');

        foreach ($atributosIds as $attrId) {
            $atributo = Atributo::find($attrId);
            if (!$atributo) continue;

            // Para cada término del atributo, calcular cuántos productos de la categoría lo poseen (exactamente como en el filtro)
            $terminosConConteo = $atributo->terminos->map(function ($termino) use ($subcategoriaIds) {
                // Subconsulta que replica exactamente la condición del filtro (sin la restricción del término)
                $count = Producto::where('estado', 'publicado')
                    ->where(function ($q) use ($subcategoriaIds) {
                        // Condición de categoría (producto directo o agrupado con hijo en categoría)
                        $q->whereIn('id_subCategorias', $subcategoriaIds)
                        ->orWhereHas('productosHijos', function ($sub) use ($subcategoriaIds) {
                            $sub->whereIn('id_subCategorias', $subcategoriaIds)
                                ->where('estado', 'publicado');
                        });
                    })
                    ->whereExists(function ($exists) use ($termino, $subcategoriaIds) {
                        // Replicamos la subconsulta del filtro pero con el término específico
                        $exists->select(DB::raw(1))
                            ->from('productos as p2')
                            ->whereColumn('p2.id', 'productos.id')
                            ->where('p2.estado', 'publicado')
                            ->where(function ($cat) use ($subcategoriaIds) {
                                $cat->whereIn('p2.id_subCategorias', $subcategoriaIds)
                                    ->orWhereExists(function ($sub) use ($subcategoriaIds) {
                                        $sub->select(DB::raw(1))
                                            ->from('producto_agrupado as pa')
                                            ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                            ->whereColumn('pa.producto_padre_id', 'p2.id')
                                            ->whereIn('hijo.id_subCategorias', $subcategoriaIds)
                                            ->where('hijo.estado', 'publicado');
                                    });
                            })
                            ->where(function ($attr) use ($termino) {
                                // Los 4 caminos para que el término esté presente en el producto
                                $attr->whereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_atributo as pa')
                                        ->join('producto_atributo_valores as pav', 'pav.producto_atributo_id', '=', 'pa.id')
                                        ->whereColumn('pa.producto_id', 'p2.id')
                                        ->where('pav.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_atributo as pa2', 'pa2.producto_id', '=', 'hijo.id')
                                        ->join('producto_atributo_valores as pav2', 'pav2.producto_atributo_id', '=', 'pa2.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('pav2.termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_variaciones as pv')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pv.producto_padre_id', 'p2.id')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                                $attr->orWhereExists(function ($q) use ($termino) {
                                    $q->select(DB::raw(1))
                                        ->from('producto_agrupado as pa')
                                        ->join('productos as hijo', 'hijo.id', '=', 'pa.producto_hijo_id')
                                        ->join('producto_variaciones as pv', 'pv.producto_padre_id', '=', 'hijo.id')
                                        ->join('variacion_atributo_terminos as vat', 'vat.variacion_id', '=', 'pv.id')
                                        ->whereColumn('pa.producto_padre_id', 'p2.id')
                                        ->where('hijo.estado', 'publicado')
                                        ->where('vat.atributo_termino_id', $termino->id);
                                });
                            });
                    })->count();

                $termino->producto_atributos_count = $count;
                return $termino;
            })->filter(function ($termino) {
                return $termino->producto_atributos_count > 0;
            })->sortByDesc('producto_atributos_count');

            if ($terminosConConteo->isNotEmpty()) {
                $atributo->terminos = $terminosConConteo;
                $atributosConTerminos[] = $atributo;
            }
        }

        $base = $this->getBaseConfig();
        return view('layouts.contenido2', array_merge($base, [
            'contenido2'          => 'tienda.categoria-todos',
            'categoria'           => $categoria,
            'productos'           => $productos,
            'subcategorias'       => $subcategorias,
            'atributosConTerminos'=> $atributosConTerminos,
            'filtros'             => $request->all()
        ]));
    }

    /**
     * Obtiene las variaciones simplificadas para la tarjeta del producto
     */
    private function getVariacionesParaTarjeta($producto)
    {
        if ($producto->tipo_producto !== 'variable') {
            return null;
        }
        
        // Obtener el primer atributo que sea visible y variación
        $primerAtributo = ProductoAtributo::where('producto_id', $producto->id)
            ->where('visible', true)
            ->where('variacion', true)
            ->orderBy('id', 'asc')
            ->first();
        
        if (!$primerAtributo) {
            return null;
        }
        
        // Obtener los términos con sus valores extra
        $terminos = $primerAtributo->valores()
            ->withPivot('valor_extra')
            ->get();
        
        $resultado = [
            'atributo_id' => $primerAtributo->atributo_id,
            'atributo_nombre' => $primerAtributo->atributo->nombre ?? 'Atributo',
            'tipo' => $primerAtributo->tipo ?? 'Default',
            'shape' => $primerAtributo->shape ?? 'Default',
            'terminos' => []
        ];
        
        // 🔥 Preparar imágenes de variaciones para data attribute
        $variacionesImagenes = [];
        
        foreach ($terminos as $termino) {
            // Buscar variación que tenga este término
            $variacion = ProductoVariacion::where('producto_padre_id', $producto->id)
                ->where('activo', true)
                ->whereHas('atributos', function($q) use ($termino) {
                    $q->where('atributo_terminos.id', $termino->id);
                })
                ->first();
            
            $imagenUrl = null;
            $stock = 0;
            $precioRegular = null;
            $precioRebajado = null;
            $imagenesVariacion = [];
            
            if ($variacion) {
                // Buscar imágenes de la variación
                if ($variacion->imagenes->isNotEmpty()) {
                    foreach ($variacion->imagenes as $img) {
                        $imagenesVariacion[] = $img->imagen_path;
                    }
                    $imagenUrl = $variacion->imagenes->first()->imagen_path;
                }
                $stock = $variacion->stock ?? 0;
                $precioRegular = $variacion->precio_regular ?? null;
                $precioRebajado = $variacion->precio_rebajado ?? null;
                
                // Guardar imágenes de la variación
                if (!empty($imagenesVariacion)) {
                    $variacionesImagenes[$variacion->id] = $imagenesVariacion;
                }
            }
            
            $resultado['terminos'][] = [
                'id' => $termino->id,
                'nombre' => $termino->nombre,
                'valor_extra' => $termino->pivot->valor_extra ?? null,
                'imagen_url' => $imagenUrl,
                'variacion_id' => $variacion ? $variacion->id : null,
                'stock' => $stock,
                'precio_regular' => $precioRegular,
                'precio_rebajado' => $precioRebajado,
                'disponible' => $variacion !== null,
            ];
        }
        
        // Guardar imágenes de variaciones en el producto para data attribute
        $producto->variaciones_imagenes = $variacionesImagenes;
        
        return $resultado;
    }

    /**
     * Procesa una colección de productos para agregar variaciones_tarjeta
     */
    public function procesarVariacionesTarjeta($productos)
    {
        return $productos->map(function($producto) {
            if ($producto->tipo_producto === 'variable') {
                $producto->variaciones_tarjeta = $this->getVariacionesParaTarjeta($producto);
            }
            return $producto;
        });
    }

    /**
     * Crear notificación de bienvenida para un nuevo cliente
     */
    private function crearNotificacionBienvenida($usuario)
    {
        $tipoUsuario = TipoNotificacion::where('slug', 'usuario')->first();
        
        if (!$tipoUsuario) {
            return;
        }

        $notificacion = Notificacion::create([
            'tipo_notificacion_id' => $tipoUsuario->id,
            'creado_por' => null,
            'titulo' => '¡Bienvenido a ' . ConfiguracionHelper::getCompanyName() . '!',
            'mensaje' => "Hola {$usuario->nombres} {$usuario->apellidos}, bienvenido a nuestra tienda. Estamos felices de tenerte con nosotros.",
            'mensaje_corto' => '¡Bienvenido a nuestra tienda!',
            'data_extra' => null,
            'url' => route('tienda.home'),
            'boton_texto' => 'Explorar tienda',
            'prioridad' => 'baja',
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(7),
            'usuario_id' => $usuario->id,
            'rol_id' => null,
            'visible' => true,
            'eliminada' => false
        ]);

        return $notificacion;
    }
    
}