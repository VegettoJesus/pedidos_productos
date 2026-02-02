<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use App\Models\ConfiguracionCorreo;
use App\Models\HomeConfiguracion;
use App\Models\Auditoria;
use App\Models\EmpresaInformacion;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Mail\Message;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class Empresa extends Controller
{
    private function registrarAuditoria($accion, $tabla, $registroId = null, $descripcion = null)
    {
        Auditoria::create([
            'user_id'       => Auth::id(),
            'accion'        => $accion,
            'tabla_afectada'=> $tabla,
            'registro_id'   => $registroId,
            'descripcion'   => $descripcion,
            'ip'            => request()->ip(),
            'navegador'     => request()->header('User-Agent')
        ]);
    }

    public function datosEmpresa(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $empresa = EmpresaInformacion::first();
                    $data->respuesta = 'success';
                    $data->success = true;
                    $data->data = $empresa;
                    break;

                case 'Guardar':
                    $validator = Validator::make($request->all(), [
                        'ruc' => 'required|string|max:20',
                        'razon_social' => 'required|string|max:255',
                        'nombre_comercial' => 'nullable|string|max:255',
                        'propietario_nombre' => 'required|string|max:255',
                        'propietario_apellido' => 'required|string|max:255',
                        'direccion' => 'required|string',
                        'ubigeo' => 'required|string',
                        'departamento_id' => 'required',
                        'provincia_id'    => 'required',
                        'distrito_id'     => 'required',
                        'maps_url' => 'nullable|url',
                        'telefono' => 'nullable|string|max:20',
                        'celular' => 'nullable|string|max:20'
                    ], [
                        'ruc.max' => 'El RUC no debe exceder los 11 dígitos',
                        'maps_url.url' => 'La URL de Google Maps debe ser válida'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $empresa = EmpresaInformacion::first();
                    $valoresAnteriores = $empresa ? $empresa->toArray() : [];

                    if ($empresa) {
                        $empresa->update($request->all());
                        $accion = 'actualizar';
                    } else {
                        $empresa = EmpresaInformacion::create($request->all());
                        $accion = 'crear';
                    }

                    // Registrar auditoría
                    $descripcion = "Datos de empresa actualizados. ";
                    if ($request->has('razon_social')) {
                        $descripcion .= "Razón Social: {$request->razon_social}. ";
                    }
                    if ($request->has('ruc')) {
                        $descripcion .= "RUC: {$request->ruc}";
                    }
                    
                    $this->registrarAuditoria($accion, 'empresa_informacion', $empresa->id, $descripcion);

                    $data->success = true;
                    $data->message = 'Datos de la empresa guardados correctamente';
                    $data->data = $empresa;
                    break;

                default:
                    $data->success = false;
                    $data->message = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->departamentos = Departamento::orderBy('nombre')->get();
            $data->script = 'js/datosEmpresa.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'empresa.datosEmpresa';
            return view('layouts.contenido', (array) $data);
        }
    }

    public function configuracionSitio(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $configuracion = ConfiguracionSistema::first();
                    $data->respuesta = 'success';
                    $data->success = true;
                    $data->data = $configuracion;
                    break;

                case 'Guardar':
                    $validator = Validator::make($request->all(), [
                        'titulo_site' => 'required|string|max:255',
                        'abreviatura_titulo' => 'required|string|max:50',
                        'descripcion_corta' => 'required|string|max:500',
                        'icono_site' => 'nullable|string|max:255',
                        'email_admin' => 'required|email|max:255',
                        'footer_text' => 'nullable|string|max:1000',
                        'max_entradas_home' => 'required|integer|min:1|max:50'
                    ]);

                    if ($validator->fails()) {
                        $data->respuesta = 'error';
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $configuracion = ConfiguracionSistema::first();
                    
                    // Guardar valores antiguos para auditoría
                    $valoresAnteriores = $configuracion ? $configuracion->toArray() : [];

                    if ($configuracion) {
                        $configuracion->update($request->only([
                            'titulo_site',
                            'abreviatura_titulo',
                            'descripcion_corta',
                            'icono_site',
                            'email_admin',
                            'footer_text',
                            'max_entradas_home'
                        ]));
                        
                        $accion = 'actualizar';
                    } else {
                        $configuracion = ConfiguracionSistema::create($request->all());
                        $accion = 'crear';
                    }

                    // Registrar auditoría detallada
                    $descripcion = "Configuración del sitio actualizada. Cambios: ";
                    $cambios = [];
                    
                    foreach ($request->all() as $key => $value) {
                        if (isset($valoresAnteriores[$key]) && $valoresAnteriores[$key] != $value) {
                            $cambios[] = "$key: '{$valoresAnteriores[$key]}' → '$value'";
                        }
                    }
                    
                    if (!empty($cambios)) {
                        $descripcion .= implode(', ', $cambios);
                    } else {
                        $descripcion = "Configuración del sitio actualizada (sin cambios detectados)";
                    }
                    
                    $this->registrarAuditoria($accion, 'configuracion_sistema', $configuracion->id, $descripcion);

                    $data->respuesta = 'success';
                    $data->success = true;
                    $data->message = 'Configuración actualizada correctamente';
                    $data->data = $configuracion;
                    break;

                case 'Iconos':

                    $iconos = [];
                    $directorio = public_path('iconos');

                    if (File::exists($directorio)) {

                        $archivos = File::files($directorio);

                        foreach ($archivos as $archivo) {

                            $extension = strtolower($archivo->getExtension());

                            if (in_array($extension, ['ico', 'png', 'jpg', 'jpeg', 'svg', 'gif', 'webp'])) {

                                $iconos[] = [
                                    'nombre' => $archivo->getFilename(),
                                    'ruta'   => '/iconos/' . $archivo->getFilename(),
                                    'tamaño' => $this->formatBytes($archivo->getSize()),
                                    'fecha'  => date('d/m/Y H:i', $archivo->getMTime())
                                ];
                            }
                        }
                    }

                    return response()->json([
                        'respuesta' => 'success',
                        'success' => true,
                        'iconos' => $iconos
                    ]);

                break;

                case 'SubirIcono':

                    $validator = Validator::make($request->all(), [
                        'icono' => 'required|image|mimes:ico,png,jpg,jpeg,gif,svg,webp|max:2048'
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'respuesta' => 'error',
                            'success' => false,
                            'message' => 'Error de validación',
                            'errors' => $validator->errors()
                        ], 422);
                    }

                    $archivo = $request->file('icono');

                    $nombre = time() . '_' . $archivo->getClientOriginalName();
                    $rutaDestino = public_path('iconos');
                    if (!file_exists($rutaDestino)) {
                        mkdir($rutaDestino, 0755, true);
                    }
                    $archivo->move($rutaDestino, $nombre);
                    $rutaPublica = '/iconos/' . $nombre;

                    $this->registrarAuditoria(
                        'crear',
                        'archivos',
                        null,
                        "Icono subido en carpeta 'ICONOS': $nombre"
                    );

                    return response()->json([
                        'respuesta' => 'success',
                        'success' => true,
                        'message' => 'Icono subido correctamente',
                        'ruta' => $rutaPublica,
                        'nombre' => $nombre
                    ]);

                break;
            case 'ListarHome':
                $secciones = DB::table('home_configuracion')
                    ->orderBy('orden', 'asc')
                    ->get()
                    ->map(function($seccion) {
                        // Convertir JSON a array si es necesario
                        if (is_string($seccion->configuracion_json)) {
                            $seccion->configuracion_json = json_decode($seccion->configuracion_json, true);
                        }
                        
                        // Agregar atributos calculados
                        $nombres = [
                            'destacados' => 'Productos Destacados',
                            'nuevos' => 'Nuevos Productos',
                            'ofertas' => 'Ofertas Especiales',
                            'mas_vendidos' => 'Más Vendidos',
                            'recomendados' => 'Recomendados para Ti'
                        ];
                        $seccion->nombre_legible = $nombres[$seccion->seccion] ?? ucfirst(str_replace('_', ' ', $seccion->seccion));
                        
                        $iconos = [
                            'destacados' => 'bi-star-fill',
                            'nuevos' => 'bi-badge-ad',
                            'ofertas' => 'bi-tag-fill',
                            'mas_vendidos' => 'bi-trophy-fill',
                            'recomendados' => 'bi-person-fill'
                        ];
                        $seccion->icono = $iconos[$seccion->seccion] ?? 'bi-grid-fill';
                        
                        return $seccion;
                    });
                
                $data->respuesta = 'success';
                $data->success = true;
                $data->data = $secciones;
                break;

            case 'GuardarHome':
                // Si los datos vienen como JSON string, decodificarlos
                $inputData = $request->getContent();
                
                if (empty($inputData)) {
                    $inputData = $request->all();
                } else {
                    // Intentar decodificar como JSON
                    $inputData = json_decode($inputData, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $inputData = $request->all();
                    }
                }
                
                // Validar que secciones exista
                if (!isset($inputData['secciones'])) {
                    return response()->json([
                        'respuesta' => 'error',
                        'success' => false,
                        'message' => 'No se recibieron datos de secciones'
                    ], 422);
                }
                
                $validator = Validator::make($inputData, [
                    'secciones' => 'required|array',
                    'secciones.*.id' => 'required|integer|exists:home_configuracion,id',
                    'secciones.*.numero_elementos' => 'required|integer|min:1|max:50',
                    'secciones.*.orden' => 'required|integer|min:0',
                    'secciones.*.mostrar' => ['required', Rule::in([true, false, 'true', 'false', 1, 0, '1', '0'])], // Aceptar varios formatos
                    'secciones.*.configuracion_json' => 'nullable|array'
                ]);

                if ($validator->fails()) {
                    // Log para debug
                    \Log::error('Validación fallida GuardarHome', [
                        'errors' => $validator->errors(),
                        'input_data' => $inputData,
                        'secciones' => $inputData['secciones'] ?? []
                    ]);
                    
                    return response()->json([
                        'respuesta' => 'error',
                        'success' => false,
                        'message' => 'Error de validación',
                        'errors' => $validator->errors()
                    ], 422);
                }

                DB::beginTransaction();
                try {
                    $cambios = [];
                    
                    foreach ($inputData['secciones'] as $seccionData) {
                        $valoresAnteriores = DB::table('home_configuracion')
                            ->where('id', $seccionData['id'])
                            ->first();
                        
                        // Convertir 'mostrar' a booleano para la base de datos
                        $mostrar = filter_var($seccionData['mostrar'], FILTER_VALIDATE_BOOLEAN);
                        
                        DB::table('home_configuracion')
                            ->where('id', $seccionData['id'])
                            ->update([
                                'numero_elementos' => $seccionData['numero_elementos'],
                                'orden' => $seccionData['orden'],
                                'mostrar' => $mostrar,
                                'configuracion_json' => json_encode($seccionData['configuracion_json'] ?? []),
                                'updated_at' => now()
                            ]);

                        // Detectar cambios para auditoría
                        if ($valoresAnteriores) {
                            $seccionNombre = DB::table('home_configuracion')
                                ->where('id', $seccionData['id'])
                                ->value('seccion');
                            
                            if ($valoresAnteriores->numero_elementos != $seccionData['numero_elementos']) {
                                $cambios[] = "{$seccionNombre}.numero_elementos: '{$valoresAnteriores->numero_elementos}' → '{$seccionData['numero_elementos']}'";
                            }
                            if ($valoresAnteriores->orden != $seccionData['orden']) {
                                $cambios[] = "{$seccionNombre}.orden: '{$valoresAnteriores->orden}' → '{$seccionData['orden']}'";
                            }
                            if ($valoresAnteriores->mostrar != $mostrar) {
                                $cambios[] = "{$seccionNombre}.mostrar: '" . ($valoresAnteriores->mostrar ? 'true' : 'false') . "' → '" . ($mostrar ? 'true' : 'false') . "'";
                            }
                        }
                    }

                    DB::commit();

                    // Registrar auditoría
                    $descripcion = !empty($cambios) 
                        ? "Configuración del Home actualizada. Cambios: " . implode(', ', $cambios)
                        : "Configuración del Home actualizada (sin cambios detectados)";
                    
                    $this->registrarAuditoria('actualizar', 'home_configuracion', null, $descripcion);

                    return response()->json([
                        'respuesta' => 'success',
                        'success' => true,
                        'message' => 'Configuración del Home actualizada correctamente'
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al guardar configuración home: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'respuesta' => 'error',
                        'success' => false,
                        'message' => 'Error al guardar: ' . $e->getMessage()
                    ], 500);
                }
                break;
            case 'BuscarProductos':
                $validator = Validator::make($request->all(), [
                    'busqueda' => 'nullable|string|max:255',
                    'excluir_ids' => 'nullable|array',
                    'excluir_ids.*' => 'integer',
                    'limite' => 'nullable|integer|min:1|max:50'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $busqueda = $request->input('busqueda', '');
                $excluirIds = $request->input('excluir_ids', []);
                $limite = $request->input('limite', 10);

                $query = DB::table('productos')
                    ->where('estado', 'publicado')
                    ->select(
                        'id',
                        'nombre',
                        'sku',
                        'imagen_miniatura',
                        'precio_regular',
                        'precio_rebajado',
                        'estado'
                    );

                // Buscar por nombre, SKU o ID
                if (!empty($busqueda)) {
                    $query->where(function($q) use ($busqueda) {
                        $q->where('nombre', 'LIKE', "%{$busqueda}%")
                        ->orWhere('sku', 'LIKE', "%{$busqueda}%");
                        
                        // Si la búsqueda es numérica, buscar por ID también
                        if (is_numeric($busqueda)) {
                            $q->orWhere('id', '=', (int) $busqueda);
                        }
                    });
                }

                // Excluir IDs si se especifican
                if (!empty($excluirIds)) {
                    $query->whereNotIn('id', $excluirIds);
                }

                $productos = $query->orderBy('nombre', 'asc')
                    ->limit($limite)
                    ->get()
                    ->map(function($producto) {
                        return [
                            'id' => $producto->id,
                            'nombre' => $producto->nombre,
                            'sku' => $producto->sku ?? 'null',
                            'imagen' => $producto->imagen_miniatura ? asset($producto->imagen_miniatura) : asset('img/default-product.png'),
                            'precio_regular' => number_format($producto->precio_regular, 2),
                            'precio_rebajado' => $producto->precio_rebajado ? number_format($producto->precio_rebajado, 2) : null,
                            'tiene_oferta' => !is_null($producto->precio_rebajado),
                            'estado' => $producto->estado
                        ];
                    });

                return response()->json([
                    'success' => true,
                    'productos' => $productos,
                    'total' => count($productos)
                ]);
                break;

                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->script = 'js/configuracionSitio.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'empresa.configuracionSitio';
            return view('layouts.contenido', (array) $data);
        }
    }

    public function servidorCorreo(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $configuracion = ConfiguracionCorreo::first();
                    $data->respuesta = 'success';
                    $data->success = true;
                    $data->data = $configuracion;
                    break;

                case 'Guardar':
                    $validator = Validator::make($request->all(), [
                        'servidor_correo' => 'required|string|max:255',
                        'puerto' => 'required|integer|min:1|max:65535',
                        'nombre_acceso' => 'required|string|max:255',
                        'contraseña' => 'nullable|string|max:500',
                        'seguridad' => 'required|in:ssl,tls,ninguna',
                        'activo' => 'boolean'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $configuracion = ConfiguracionCorreo::first();
                    $valoresAnteriores = $configuracion ? $configuracion->toArray() : [];
                    $cambiosContraseña = false;

                    $datosActualizar = $request->only([
                        'servidor_correo',
                        'puerto',
                        'nombre_acceso',
                        'seguridad',
                        'activo'
                    ]);

                    // Manejar contraseña (solo actualizar si se proporciona)
                    if ($request->filled('contraseña')) {
                        $datosActualizar['contraseña'] = $request->input('contraseña');
                        $cambiosContraseña = true;
                    }

                    if ($configuracion) {
                        // Si se activa esta configuración, desactivar las demás
                        if ($request->input('activo', false)) {
                            ConfiguracionCorreo::where('id', '!=', $configuracion->id)
                                ->update(['activo' => false]);
                        }
                        
                        $configuracion->update($datosActualizar);
                        $accion = 'actualizar';
                    } else {
                        $configuracion = ConfiguracionCorreo::create($datosActualizar);
                        $accion = 'crear';
                    }

                    // Auditoría detallada
                    $descripcion = "Configuración de correo actualizada. ";
                    if ($cambiosContraseña) {
                        $descripcion .= "Contraseña actualizada. ";
                    }
                    
                    $this->registrarAuditoria($accion, 'configuracion_correo', $configuracion->id, $descripcion);

                    $data->success = true;
                    $data->message = 'Configuración de correo guardada correctamente';
                    $data->data = $configuracion;
                    break;

                case 'ProbarConexion':
                    $validator = Validator::make($request->all(), [
                        'email' => 'required|email',
                        'configuracion' => 'nullable|array'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Email inválido';
                        return response()->json($data, 422);
                    }

                    try {
                        $email = $request->input('email');
                        $configPrueba = $request->input('configuracion');
                        
                        if ($configPrueba) {
                            // Usar configuración de prueba desde el formulario
                            $config = [
                                'transport' => 'smtp',
                                'host' => $configPrueba['servidor_correo'],
                                'port' => $configPrueba['puerto'],
                                'encryption' => $configPrueba['seguridad'] === 'ninguna' ? null : $configPrueba['seguridad'],
                                'username' => $configPrueba['nombre_acceso'],
                                'password' => $configPrueba['contraseña'] ?? '',
                                'timeout' => 30,
                            ];
                        } else {
                            // Usar configuración guardada
                            $configuracion = ConfiguracionCorreo::getActiva();
                            if (!$configuracion) {
                                throw new \Exception('No hay configuración de correo activa');
                            }
                            
                            $config = [
                                'transport' => 'smtp',
                                'host' => $configuracion->servidor_correo,
                                'port' => $configuracion->puerto,
                                'encryption' => $configuracion->seguridad === 'ninguna' ? null : $configuracion->seguridad,
                                'username' => $configuracion->nombre_acceso,
                                'password' => $configuracion->contraseña,
                                'timeout' => 30,
                            ];
                        }

                        // Configurar temporalmente el mailer
                        config(['mail.mailers.smtp_test' => $config]);
                        
                        // Enviar email de prueba
                        Mail::mailer('smtp_test')->send([], [], function (Message $message) use ($email, $configPrueba) {
                            $fromName = config('app.name', 'Sistema');
                            $fromEmail = 'no-reply@example.com';
                            
                            $message->to($email)
                                    ->from($fromEmail, $fromName)
                                    ->subject('Prueba de conexión SMTP')
                                    ->html($this->generarContenidoEmailPrueba());
                        });

                        $this->registrarAuditoria('probar', 'configuracion_correo', null, "Prueba de conexión SMTP enviada a: $email");

                        $data->success = true;
                        $data->message = 'Email de prueba enviado correctamente';
                        $data->email = $email;

                    } catch (\Exception $e) {
                        $data->success = false;
                        $data->message = 'Error al enviar email de prueba: ' . $e->getMessage();
                        $data->error = $e->getMessage();
                    }
                    break;

                case 'GetPredefinidas':
                    $data->success = true;
                    $data->configuraciones = ConfiguracionCorreo::configuracionesPredefinidas();
                    break;

                default:
                    $data->success = false;
                    $data->message = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->script = 'js/servidorCorreo.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'empresa.servidorCorreo';
            return view('layouts.contenido', (array) $data);
        }
    }

    private function generarContenidoEmailPrueba()
    {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Prueba de Conexión SMTP</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4a6fa5; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; }
                    .success { color: #28a745; font-weight: bold; }
                    .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>✅ Prueba de Conexión Exitosa</h1>
                    </div>
                    <div class="content">
                        <p>Hola,</p>
                        <p>Este es un email de prueba para verificar que la configuración del servidor SMTP está funcionando correctamente.</p>
                        
                        <div class="info">
                            <p><strong>Detalles del envío:</strong></p>
                            <ul>
                                <li><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</li>
                                <li><strong>Sistema:</strong> ' . config('app.name', 'Sistema') . '</li>
                                <li><strong>Servidor SMTP:</strong> Configuración activa</li>
                            </ul>
                        </div>
                        
                        <p>Si recibes este email, significa que la configuración de correo está funcionando correctamente y podrás recibir:</p>
                        <ul>
                            <li>Notificaciones del sistema</li>
                            <li>Confirmaciones de pedidos</li>
                            <li>Recuperación de contraseñas</li>
                            <li>Otros emails automáticos</li>
                        </ul>
                        
                        <p class="success">¡La configuración de correo está lista para usar!</p>
                    </div>
                    <div class="footer">
                        <p>Este es un email automático, por favor no responder.</p>
                        <p>&copy; ' . date('Y') . ' ' . config('app.name', 'Sistema') . ' - Todos los derechos reservados.</p>
                    </div>
                </div>
            </body>
            </html>
        ';
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}