<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use App\Models\ConfiguracionCorreo;
use App\Models\HomeConfiguracion;
use App\Traits\AuditableTrait;
use App\Models\EmpresaInformacion;
use App\Models\FooterColumn;
use App\Models\FooterLink;
use App\Models\FooterContact;
use App\Models\FooterSocial;
use App\Data\Icons;
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
use App\Helpers\ConfiguracionHelper;
use App\Services\MenuService;
use Illuminate\Support\Facades\Cache;

class Empresa extends Controller
{
    use AuditableTrait;
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
                    $datosPermitidos = $request->except(['opcion', '_token', '_method']);
                    if ($empresa) {
                        $valoresAnteriores = $empresa->toArray();
                        unset($valoresAnteriores['id'], $valoresAnteriores['created_at'], $valoresAnteriores['updated_at']);
                        $empresa->update($datosPermitidos);
                        $this->registrarAuditoria(
                            'Actualizar',
                            'empresa_informacion',
                            $empresa->id,
                            $empresa->razon_social,
                            $valoresAnteriores,   
                            $datosPermitidos,     
                            null
                        );
                        $data->message = 'Datos de la empresa actualizados correctamente';
                    } else {
                        $empresa = EmpresaInformacion::create($datosPermitidos);
                        $this->registrarAuditoria(
                            'Crear',
                            'empresa_informacion',
                            $empresa->id,
                            $empresa->razon_social,
                            null,                  
                            null,                  
                            "RUC: {$empresa->ruc}"
                        );
                        
                        $data->message = 'Datos de la empresa guardados correctamente';
                    }

                    $data->success = true;
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
                    $contenidoOriginal = $request->input('descripcion_corta', '');
                    $contenidoLimpioImagenes = preg_replace('/<img[^>]*>/i', '', $contenidoOriginal);
                    $contenidoDecodificado = html_entity_decode($contenidoLimpioImagenes, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $textoPlano = strip_tags($contenidoDecodificado);
                    $textoPlano = preg_replace('/\s+/u', ' ', trim($textoPlano));

                    $longitudReal = mb_strlen($textoPlano, 'UTF-8');

                    $validator = Validator::make($request->all(), [
                        'titulo_site' => 'required|string|max:255',
                        'abreviatura_titulo' => 'required|string|max:50',
                        'icono_site' => 'nullable|string|max:255',
                        'email_admin' => 'required|email|max:255',
                        'footer_text' => 'nullable|string|max:1000',
                        'max_entradas_home' => 'required|integer|min:1|max:50'
                    ]);
                    
                    if ($longitudReal > 500) {
                        $validator->errors()->add('descripcion_corta', 'La introducción no puede superar los 500 caracteres.');
                        $data->respuesta = 'error';
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    if ($validator->fails()) {
                        $data->respuesta = 'error';
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $configuracion = ConfiguracionSistema::first();
                    
                    $datosPermitidos = $request->except(['opcion', '_token', '_method']);
                    $datosPermitidos['descripcion_corta'] = $contenidoLimpioImagenes;

                    if ($configuracion) {
                        $valoresAnteriores = $configuracion->toArray();
                        unset($valoresAnteriores['id'], $valoresAnteriores['created_at'], $valoresAnteriores['updated_at']);
                        
                        $configuracion->update($datosPermitidos);
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'configuracion_sistema',
                            $configuracion->id,
                            $configuracion->titulo_site,
                            $valoresAnteriores,
                            $datosPermitidos,
                            null
                        );
                        
                        $data->message = 'Configuración actualizada correctamente';
                    } else {
                        $configuracion = ConfiguracionSistema::create($datosPermitidos);
                        
                        $this->registrarAuditoria(
                            'Crear',
                            'configuracion_sistema',
                            $configuracion->id,
                            $configuracion->titulo_site,  
                            null,
                            null,
                            "Título: {$configuracion->titulo_site} | Email: {$configuracion->email_admin}"
                        );
                        
                        $data->message = 'Configuración creada correctamente';
                    }

                    ConfiguracionHelper::clearCache();
                    MenuService::limpiarTodaLaCache();

                    $data->respuesta = 'success';
                    $data->success = true;
                    $data->cache_cleared = true;
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
                $inputData = $request->getContent();
                
                if (empty($inputData)) {
                    $inputData = $request->all();
                } else {
                    $inputData = json_decode($inputData, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $inputData = $request->all();
                    }
                }
                
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
                    'secciones.*.mostrar' => ['required', Rule::in([true, false, 'true', 'false', 1, 0, '1', '0'])],
                    'secciones.*.configuracion_json' => 'nullable|array'
                ]);

                if ($validator->fails()) {
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
                    $todosLosCambios = [];
                    $seccionesActualizadas = [];
                    
                    foreach ($inputData['secciones'] as $seccionData) {
                        $valoresAnteriores = DB::table('home_configuracion')
                            ->where('id', $seccionData['id'])
                            ->first();
                        
                        $seccionNombre = $valoresAnteriores->seccion ?? "Sección ID {$seccionData['id']}";
                        $mostrar = filter_var($seccionData['mostrar'], FILTER_VALIDATE_BOOLEAN);
                        $valoresNuevos = [
                            'numero_elementos' => $seccionData['numero_elementos'],
                            'orden' => $seccionData['orden'],
                            'mostrar' => $mostrar,
                            'configuracion_json' => json_encode($seccionData['configuracion_json'] ?? []),
                            'updated_at' => now()
                        ];
                        $cambiosSeccion = [];
                        if ($valoresAnteriores) {
                            if ($valoresAnteriores->numero_elementos != $seccionData['numero_elementos']) {
                                $cambiosSeccion[] = "numero_elementos: '{$valoresAnteriores->numero_elementos}' → '{$seccionData['numero_elementos']}'";
                            }
                            if ($valoresAnteriores->orden != $seccionData['orden']) {
                                $cambiosSeccion[] = "orden: '{$valoresAnteriores->orden}' → '{$seccionData['orden']}'";
                            }
                            if ($valoresAnteriores->mostrar != $mostrar) {
                                $cambiosSeccion[] = "mostrar: '" . ($valoresAnteriores->mostrar ? 'true' : 'false') . "' → '" . ($mostrar ? 'true' : 'false') . "'";
                            }
                        }
                        
                        DB::table('home_configuracion')
                            ->where('id', $seccionData['id'])
                            ->update($valoresNuevos);
                        
                        if (!empty($cambiosSeccion)) {
                            $todosLosCambios[] = "{$seccionNombre}: " . implode(', ', $cambiosSeccion);
                            $seccionesActualizadas[] = $seccionNombre;
                        }
                    }

                    DB::commit();

                    if (!empty($todosLosCambios)) {
                        $descripcion = "Configuración del Home actualizada. Secciones modificadas: " . implode('; ', $todosLosCambios);
                    } else {
                        $descripcion = "Configuración del Home actualizada (sin cambios detectados)";
                    }
                    
                    $this->registrarAuditoria(
                        'Actualizar',
                        'home_configuracion',
                        null,  
                        'Configuración Home',
                        null,  
                        null,  
                        $descripcion  
                    );

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

                if (!empty($busqueda)) {
                    $query->where(function($q) use ($busqueda) {
                        $q->where('nombre', 'LIKE', "%{$busqueda}%")
                        ->orWhere('sku', 'LIKE', "%{$busqueda}%");
                
                        if (is_numeric($busqueda)) {
                            $q->orWhere('id', '=', (int) $busqueda);
                        }
                    });
                }

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
            case 'ListarFooterColumns':
                $columns = FooterColumn::orderBy('sort_order')->get();
                return response()->json(['success' => true, 'data' => $columns]);
                break;

            case 'GuardarFooterColumn':
                $validator = Validator::make($request->all(), [
                    'id' => 'nullable|integer',
                    'title' => 'required|string|max:100',
                    'column_type' => 'required|in:links,mixed',
                    'active' => 'boolean',
                    'icon' => 'nullable|string|max:255',
                    'icono_archivo' => 'nullable|file|mimes:ico|max:2048'
                ]);

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
                }

                $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $iconValue = $request->input('icon');

                if ($request->hasFile('icono_archivo')) {
                    $archivo = $request->file('icono_archivo');
                    $extension = $archivo->getClientOriginalExtension();
                    $nombreUnico = 'img_' . time() . '_' . uniqid() . '_' . 'column' . '.' . $extension;
                    $destino = 'iconos_footer';
                    $rutaDestino = public_path($destino);
                    if (!file_exists($rutaDestino)) mkdir($rutaDestino, 0755, true);
                    $archivo->move($rutaDestino, $nombreUnico);
                    $iconValue = '/' . $destino . '/' . $nombreUnico;
                }

                if (empty($iconValue)) {
                    $iconValue = null;
                }

                DB::beginTransaction();
                try {
                    if (empty($request->id)) {
                        $maxOrder = FooterColumn::max('sort_order') ?? 0;
                        $column = FooterColumn::create([
                            'title' => $request->title,
                            'column_type' => $request->column_type,
                            'active' => $active,
                            'sort_order' => $maxOrder + 1,
                            'icon' => $iconValue
                        ]);
                        $id = $column->id;
        
                        $this->registrarAuditoria(
                            'Crear',
                            'footer_columns',
                            $column->id,
                            $column->title,
                            null,
                            null,
                            "Tipo: {$column->column_type} | Orden: {$column->sort_order}"
                        );
                        
                    } else {
                        $column = FooterColumn::findOrFail($request->id);
                        $valoresAnteriores = [
                            'title' => $column->title,
                            'column_type' => $column->column_type,
                            'active' => $column->active,
                            'icon' => $column->icon,
                            'sort_order' => $column->sort_order
                        ];
                        
                        if (!$request->hasFile('icono_archivo') && $column->icon && str_starts_with($column->icon, '/iconos_footer/') && ($iconValue === null || str_starts_with($iconValue, 'bi bi-'))) {
                            $oldPath = public_path($column->icon);
                            if (file_exists($oldPath)) unlink($oldPath);
                        }
                        
                        $valoresNuevos = [
                            'title' => $request->title,
                            'column_type' => $request->column_type,
                            'active' => $active,
                            'icon' => $iconValue
                        ];
                        
                        $column->update($valoresNuevos);
                        $id = $column->id;
                        $cambioIcono = false;
                        if ($valoresAnteriores['icon'] != $iconValue) {
                            $cambioIcono = true;
                        }
                        
                        $detalleExtra = [];
                        if ($cambioIcono) {
                            $detalleExtra[] = "Ícono actualizado";
                        }
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'footer_columns',
                            $column->id,
                            $column->title,
                            $valoresAnteriores,
                            $valoresNuevos,
                            !empty($detalleExtra) ? implode(' | ', $detalleExtra) : null
                        );
                    }
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json(['success' => true, 'id' => $id]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al guardar columna de footer: ' . $e->getMessage());
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al guardar: ' . $e->getMessage()
                    ], 500);
                }
                break;

            case 'OrdenarFooterColumns':
                $orden = $request->input('orden');
                foreach ($orden as $item) {
                    FooterColumn::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
                }
                ConfiguracionHelper::clearFooterCache();
                return response()->json(['success' => true]);
                break;

            case 'EliminarFooterColumn':
                $validator = Validator::make($request->all(), [
                    'id' => 'required|integer|exists:footer_columns,id'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'ID de columna no válido',
                        'errors' => $validator->errors()
                    ], 422);
                }

                DB::beginTransaction();
                try {
                    $column = FooterColumn::find($request->input('id'));
                    
                    if (!$column) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Columna no encontrada'
                        ], 404);
                    }
                    
                    $id = $column->id;
                    $titulo = $column->title;
                    $tipo = $column->column_type;
                    $icono = $column->icon;
                    
                    if ($column->icon && str_starts_with($column->icon, '/iconos_footer/')) {
                        $filePath = public_path($column->icon);
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    $column->delete();
                    
                    $this->registrarAuditoria(
                        'Eliminar',
                        'footer_columns',
                        $id,
                        $titulo,
                        null,
                        null,  
                        "Tipo: {$tipo}"
                    );
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json([
                        'success' => true, 
                        'message' => 'Columna eliminada correctamente'
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al eliminar columna de footer: ' . $e->getMessage(), [
                        'id' => $request->input('id'),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al eliminar: ' . $e->getMessage()
                    ], 500);
                }
                break;

            case 'ListarFooterLinks':
                $links = FooterLink::where('column_id', $request->input('column_id'))
                    ->orderBy('sort_order')
                    ->get();
                return response()->json(['success' => true, 'data' => $links]);
                break;

            case 'GuardarFooterLink':
                $validator = Validator::make($request->all(), [
                    'id' => 'nullable|integer',
                    'column_id' => 'required|integer|exists:footer_columns,id',
                    'text' => 'nullable|string|max:100',
                    'url' => 'nullable|string|max:255',
                    'active' => 'boolean',
                    'icon' => 'nullable|string|max:255',
                    'icono_archivo' => 'nullable|file|mimes:ico|max:2048'
                ]);

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
                }

                $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $iconValue = $request->input('icon');

                if ($request->hasFile('icono_archivo')) {
                    $archivo = $request->file('icono_archivo');
                    $extension = $archivo->getClientOriginalExtension();
                    $nombreUnico = 'img_' . time() . '_' . uniqid() . '_' . 'link' . '.' . $extension;
                    $destino = 'iconos_footer';
                    $rutaDestino = public_path($destino);
                    if (!file_exists($rutaDestino)) mkdir($rutaDestino, 0755, true);
                    $archivo->move($rutaDestino, $nombreUnico);
                    $iconValue = '/' . $destino . '/' . $nombreUnico;
                }

                if (empty($iconValue)) {
                    $iconValue = null;
                }

                DB::beginTransaction();
                try {
                    if (empty($request->id)) {
                        $maxOrder = FooterLink::where('column_id', $request->column_id)->max('sort_order') ?? 0;
                        $link = FooterLink::create([
                            'column_id' => $request->column_id,
                            'text' => $request->text,
                            'url' => $request->url,
                            'active' => $active,
                            'sort_order' => $maxOrder + 1,
                            'icon' => $iconValue
                        ]);
                        $id = $link->id;
                        
                        $columna = FooterColumn::find($request->column_id);
                        $nombreColumna = $columna ? $columna->title : "ID {$request->column_id}";
                        
                        $this->registrarAuditoria(
                            'Crear',
                            'footer_links',
                            $link->id,
                            $link->text,
                            null,
                            null,
                            "Columna: {$nombreColumna} | URL: {$link->url}"
                        );
                        
                    } else {
                        $link = FooterLink::findOrFail($request->id);
                        
                        $valoresAnteriores = [
                            'text' => $link->text,
                            'url' => $link->url,
                            'active' => $link->active,
                            'icon' => $link->icon,
                            'sort_order' => $link->sort_order,
                            'column_id' => $link->column_id
                        ];
                        
                        if ($request->hasFile('icono_archivo') && $link->icon && str_starts_with($link->icon, '/iconos_footer/') && ($iconValue === null || str_starts_with($iconValue, 'bi bi-'))) {
                            $oldPath = public_path($link->icon);
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                        
                        $valoresNuevos = [
                            'text' => $request->text,
                            'url' => $request->url,
                            'active' => $active,
                            'icon' => $iconValue
                        ];
                        
                        $link->update($valoresNuevos);
                        $id = $link->id;
                        $cambioIcono = ($valoresAnteriores['icon'] != $iconValue);
                        $detalleExtra = [];
                        if ($cambioIcono) {
                            $detalleExtra[] = "Ícono actualizado";
                        }
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'footer_links',
                            $link->id,
                            $link->text,
                            $valoresAnteriores,
                            $valoresNuevos,
                            !empty($detalleExtra) ? implode(' | ', $detalleExtra) : null
                        );
                    }
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json(['success' => true, 'id' => $id]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al guardar link de footer: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al guardar: ' . $e->getMessage()
                    ], 500);
                }
                break;

            case 'EliminarFooterLink':
                $validator = Validator::make($request->all(), [
                    'id' => 'required|integer|exists:footer_links,id'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'ID de link no válido',
                        'errors' => $validator->errors()
                    ], 422);
                }

                DB::beginTransaction();
                try {
                    $link = FooterLink::find($request->input('id'));
                    
                    if (!$link) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Link no encontrado'
                        ], 404);
                    }
                    
                    $id = $link->id;
                    $texto = $link->text;
                    $url = $link->url;
                    $columnId = $link->column_id;
                    $columna = FooterColumn::find($columnId);
                    $nombreColumna = $columna ? $columna->title : "ID {$columnId}";
                    if ($link->icon && str_starts_with($link->icon, '/iconos_footer/')) {
                        $filePath = public_path($link->icon);
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    $link->delete();
                    
                    $this->registrarAuditoria(
                        'Eliminar',
                        'footer_links',
                        $id,
                        $texto,
                        null,  
                        null,  
                        "URL: {$url} | Columna: {$nombreColumna}"
                    );
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json([
                        'success' => true, 
                        'message' => 'Link eliminado correctamente'
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al eliminar link de footer: ' . $e->getMessage(), [
                        'id' => $request->input('id'),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al eliminar: ' . $e->getMessage()
                    ], 500);
                }
                break;

            case 'ObtenerFooterContact':
                $contact = FooterContact::where('column_id', $request->input('column_id'))->first();
                return response()->json(['success' => true, 'data' => $contact]);
                break;

            case 'GuardarFooterContact':
                $validator = Validator::make($request->all(), [
                    'column_id' => 'required|integer|exists:footer_columns,id',
                    'phone' => 'nullable|string|max:20',
                    'email' => 'nullable|email|max:100',
                    'address' => 'nullable|string|max:255',
                    'phone_icon' => 'nullable|string|max:255',
                    'email_icon' => 'nullable|string|max:255',
                    'address_icon' => 'nullable|string|max:255',
                    'phone_icono_archivo' => 'nullable|file|mimes:ico|max:2048',
                    'email_icono_archivo' => 'nullable|file|mimes:ico|max:2048',
                    'address_icono_archivo' => 'nullable|file|mimes:ico|max:2048'
                ]);

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
                }

                DB::beginTransaction();
                try {
                    $contact = FooterContact::firstOrNew(['column_id' => $request->column_id]);
                    $esCreacion = !$contact->exists;
                    $valoresAnteriores = $esCreacion ? null : [
                        'phone' => $contact->phone,
                        'email' => $contact->email,
                        'address' => $contact->address,
                        'phone_icon' => $contact->phone_icon,
                        'email_icon' => $contact->email_icon,
                        'address_icon' => $contact->address_icon
                    ];

                    $contact->phone = $request->phone;
                    $contact->email = $request->email;
                    $contact->address = $request->address;
                    $iconFields = ['phone', 'email', 'address'];
                    $iconosCambiados = [];
                    
                    foreach ($iconFields as $field) {
                        $oldIcon = $contact->{$field . '_icon'} ?? null;
                        $newIcon = null;

                        if ($request->hasFile($field . '_icono_archivo')) {
                            $archivo = $request->file($field . '_icono_archivo');
                            $extension = $archivo->getClientOriginalExtension();
                            $nombreUnico = 'img_' . time() . '_' . uniqid() . '_' . $field . '.' . $extension;
                            $destino = 'iconos_footer';
                            $rutaDestino = public_path($destino);
                            if (!file_exists($rutaDestino)) mkdir($rutaDestino, 0755, true);
                            $archivo->move($rutaDestino, $nombreUnico);
                            $newIcon = '/' . $destino . '/' . $nombreUnico;

                            if ($oldIcon && str_starts_with($oldIcon, '/iconos_footer/')) {
                                $oldPath = public_path($oldIcon);
                                if (file_exists($oldPath)) unlink($oldPath);
                            }
                            $iconosCambiados[] = $field;
                        } else {
                            $newIcon = $request->input($field . '_icon'); 

                            if ($newIcon && !str_starts_with($newIcon, '/iconos_footer/')) {
                                if ($oldIcon && str_starts_with($oldIcon, '/iconos_footer/')) {
                                    $oldPath = public_path($oldIcon);
                                    if (file_exists($oldPath)) unlink($oldPath);
                                }
                                $iconosCambiados[] = $field;
                            } elseif (empty($newIcon)) {
                                if ($oldIcon && str_starts_with($oldIcon, '/iconos_footer/')) {
                                    $oldPath = public_path($oldIcon);
                                    if (file_exists($oldPath)) unlink($oldPath);
                                }
                                $iconosCambiados[] = $field;
                            } elseif ($oldIcon != $newIcon) {
                                $iconosCambiados[] = $field;
                            }
                        }

                        $contact->{$field . '_icon'} = $newIcon ?? null;
                    }

                    $contact->save();
                    
                    $columna = FooterColumn::find($request->column_id);
                    $nombreColumna = $columna ? $columna->title : "ID {$request->column_id}";
                    
                    $valoresNuevos = [
                        'phone' => $contact->phone,
                        'email' => $contact->email,
                        'address' => $contact->address,
                        'phone_icon' => $contact->phone_icon,
                        'email_icon' => $contact->email_icon,
                        'address_icon' => $contact->address_icon
                    ];
                    
                    $detalleExtra = [];
                    if (!empty($iconosCambiados)) {
                        $detalleExtra[] = "Íconos actualizados: " . implode(', ', $iconosCambiados);
                    }
                    
                    if ($esCreacion) {
                        $this->registrarAuditoria(
                            'Crear',
                            'footer_contacts',
                            $contact->id,
                            "Contacto - {$nombreColumna}",
                            null,
                            null,
                            "Columna: {$nombreColumna} | Email: {$contact->email} | Teléfono: {$contact->phone}"
                        );
                    } else {
                        $this->registrarAuditoria(
                            'Actualizar',
                            'footer_contacts',
                            $contact->id,
                            "Contacto - {$nombreColumna}",
                            $valoresAnteriores,
                            $valoresNuevos,
                            !empty($detalleExtra) ? implode(' | ', $detalleExtra) : null
                        );
                    }
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json(['success' => true, 'message' => $esCreacion ? 'Contacto creado correctamente' : 'Contacto actualizado correctamente']);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al guardar contacto de footer: ' . $e->getMessage(), [
                        'column_id' => $request->column_id,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al guardar: ' . $e->getMessage()
                    ], 500);
                }
                break;

            case 'ListarFooterSocial':
                $social = FooterSocial::where('column_id', $request->input('column_id'))
                    ->orderBy('sort_order')
                    ->get();
                return response()->json(['success' => true, 'data' => $social]);
                break;

            case 'GuardarFooterSocial':
                $validator = Validator::make($request->all(), [
                    'id' => 'nullable|integer',
                    'column_id' => 'required|integer|exists:footer_columns,id',
                    'name' => 'nullable|string|max:50',
                    'url' => 'required|string|max:255',
                    'active' => 'boolean',
                    'icon' => 'nullable',
                    'icono_archivo' => 'nullable|file|mimes:ico|max:2048' 
                ]);

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
                }

                $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $iconValue = $request->input('icon'); 

                if ($request->hasFile('icono_archivo')) {
                    $archivo = $request->file('icono_archivo');
                    $nombreOriginal = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
                    $nombreOriginal = preg_replace('/[^a-zA-Z0-9_-]/', '', $nombreOriginal);
                    $extension = $archivo->getClientOriginalExtension();
                    $nombreUnico = 'img_' . time() . '_' . uniqid() . '.' . $extension;
                    
                    $destino = 'iconos_footer';
                    $rutaDestino = public_path($destino);
                    if (!file_exists($rutaDestino)) {
                        mkdir($rutaDestino, 0755, true);
                    }
                    
                    $archivo->move($rutaDestino, $nombreUnico);
                    $iconValue = '/' . $destino . '/' . $nombreUnico; 
                }

                if (empty($iconValue)) {
                    return response()->json(['success' => false, 'message' => 'Debe proporcionar un ícono o una imagen'], 422);
                }

                DB::beginTransaction();
                try {
                    $columna = FooterColumn::find($request->column_id);
                    $nombreColumna = $columna ? $columna->title : "ID {$request->column_id}";
                    
                    if (empty($request->id)) {
                        $maxOrder = FooterSocial::where('column_id', $request->column_id)->max('sort_order') ?? 0;
                        $social = FooterSocial::create([
                            'column_id' => $request->column_id,
                            'name' => $request->name,
                            'icon' => $iconValue,
                            'url' => $request->url,
                            'active' => $active,
                            'sort_order' => $maxOrder + 1
                        ]);
                        $id = $social->id;
                        
                        $this->registrarAuditoria(
                            'Crear',
                            'footer_social',
                            $social->id,
                            $social->name,
                            null,
                            null,
                            "Columna: {$nombreColumna} | URL: {$social->url} | Activo: " . ($active ? 'Sí' : 'No')
                        );
                        
                    } else {
                        $social = FooterSocial::findOrFail($request->id);
                        
                        $valoresAnteriores = [
                            'name' => $social->name,
                            'icon' => $social->icon,
                            'url' => $social->url,
                            'active' => $social->active,
                            'sort_order' => $social->sort_order,
                            'column_id' => $social->column_id
                        ];
                        
                        if ($social->icon && str_starts_with($social->icon, '/iconos_footer/') && $social->icon != $iconValue) {
                            $oldPath = public_path($social->icon);
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                        
                        $valoresNuevos = [
                            'name' => $request->name,
                            'icon' => $iconValue,
                            'url' => $request->url,
                            'active' => $active
                        ];
                        
                        $social->update($valoresNuevos);
                        $id = $social->id;
                        
                        $cambioIcono = ($valoresAnteriores['icon'] != $iconValue);
                        
                        $detalleExtra = [];
                        if ($cambioIcono) {
                            $detalleExtra[] = "Ícono actualizado";
                        }
                        
                        $this->registrarAuditoria(
                            'Actualizar',
                            'footer_social',
                            $social->id,
                            $social->name,
                            $valoresAnteriores,
                            $valoresNuevos,
                            !empty($detalleExtra) ? implode(' | ', $detalleExtra) : null
                        );
                    }
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json(['success' => true, 'id' => $id]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al guardar red social de footer: ' . $e->getMessage(), [
                        'column_id' => $request->column_id,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al guardar: ' . $e->getMessage()
                    ], 500);
                }
                break;

            case 'EliminarFooterSocial':
                $validator = Validator::make($request->all(), [
                    'id' => 'required|integer|exists:footer_social,id'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'ID de red social no válido',
                        'errors' => $validator->errors()
                    ], 422);
                }

                DB::beginTransaction();
                try {
                    $social = FooterSocial::find($request->input('id'));
                    
                    if (!$social) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Red social no encontrada'
                        ], 404);
                    }
                    
                    $id = $social->id;
                    $nombre = $social->name;
                    $url = $social->url;
                    $icon = $social->icon;
                    $columnId = $social->column_id;
                    $columna = FooterColumn::find($columnId);
                    $nombreColumna = $columna ? $columna->title : "ID {$columnId}";
                    
                    if ($icon && str_starts_with($icon, '/iconos_footer/')) {
                        $filePath = public_path($icon);
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    $social->delete();
                    
                    $this->registrarAuditoria(
                        'Eliminar',
                        'footer_social',
                        $id,
                        $nombre,
                        null,
                        null,
                        "Columna: {$nombreColumna} | URL: {$url}"
                    );
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json([
                        'success' => true, 
                        'message' => 'Red social eliminada correctamente'
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al eliminar red social de footer: ' . $e->getMessage(), [
                        'id' => $request->input('id'),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al eliminar: ' . $e->getMessage()
                    ], 500);
                }
                break;

            case 'ObtenerFooterLink':
                $link = FooterLink::find($request->input('id'));
                return response()->json(['success' => true, 'data' => $link]);
                break;

            case 'ObtenerFooterSocial':
                $social = FooterSocial::find($request->input('id'));
                return response()->json(['success' => true, 'data' => $social]);
                break;
            case 'LimpiarIconoFooterColumn':
                $validator = Validator::make($request->all(), [
                    'id' => 'required|integer|exists:footer_columns,id'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'ID de columna no válido',
                        'errors' => $validator->errors()
                    ], 422);
                }

                DB::beginTransaction();
                try {
                    $column = FooterColumn::find($request->input('id'));
                    
                    if (!$column) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Columna no encontrada'
                        ], 404);
                    }
                    
                    $id = $column->id;
                    $titulo = $column->title;
                    $iconoAnterior = $column->icon;
                    $teniaIcono = !is_null($iconoAnterior);
            
                    if ($iconoAnterior && str_starts_with($iconoAnterior, '/iconos_footer/')) {
                        $filePath = public_path($iconoAnterior);
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    $column->icon = null;
                    $column->save();
                    
                    if ($teniaIcono) {
                        $this->registrarAuditoria(
                            'Actualizar',
                            'footer_columns',
                            $id,
                            $titulo,
                            ['icon' => $iconoAnterior],
                            ['icon' => null],
                            "Ícono de columna eliminado"
                        );
                    }
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json([
                        'success' => true, 
                        'message' => 'Ícono eliminado correctamente'
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al limpiar ícono de columna de footer: ' . $e->getMessage(), [
                        'id' => $request->input('id'),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al eliminar el ícono: ' . $e->getMessage()
                    ], 500);
                }
                break;

            case 'LimpiarIconoFooterLink':
                $validator = Validator::make($request->all(), [
                    'id' => 'required|integer|exists:footer_links,id'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'ID de enlace no válido',
                        'errors' => $validator->errors()
                    ], 422);
                }

                DB::beginTransaction();
                try {
                    $link = FooterLink::find($request->input('id'));
                    
                    if (!$link) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Enlace no encontrado'
                        ], 404);
                    }
                    
                    $id = $link->id;
                    $texto = $link->text;
                    $iconoAnterior = $link->icon;
                    $teniaIcono = !is_null($iconoAnterior);
                
                    if ($iconoAnterior && str_starts_with($iconoAnterior, '/iconos_footer/')) {
                        $filePath = public_path($iconoAnterior);
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    $link->icon = null;
                    $link->save();
                    
                    if ($teniaIcono) {
                        $this->registrarAuditoria(
                            'Actualizar',
                            'footer_links',
                            $id,
                            $texto,
                            ['icon' => $iconoAnterior],
                            ['icon' => null],
                            "Ícono de enlace eliminado"
                        );
                    }
                    
                    DB::commit();
                    ConfiguracionHelper::clearFooterCache();
                    
                    return response()->json([
                        'success' => true, 
                        'message' => 'Ícono eliminado correctamente'
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error al limpiar ícono de enlace de footer: ' . $e->getMessage(), [
                        'id' => $request->input('id'),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false, 
                        'message' => 'Error al eliminar el ícono: ' . $e->getMessage()
                    ], 500);
                }
                break;

                default:
                    $data->respuesta = 'error';
                    $data->mensaje = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->iconos = Icons::all();
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

                    DB::beginTransaction();
                    try {
                        $configuracion = ConfiguracionCorreo::first();
                        $esCreacion = is_null($configuracion);
                        
                        $valoresAnteriores = $configuracion ? [
                            'servidor_correo' => $configuracion->servidor_correo,
                            'puerto' => $configuracion->puerto,
                            'nombre_acceso' => $configuracion->nombre_acceso,
                            'seguridad' => $configuracion->seguridad,
                            'activo' => $configuracion->activo,
                            'contraseña' => '***OCULTA***'  
                        ] : null;

                        $datosActualizar = $request->only([
                            'servidor_correo',
                            'puerto',
                            'nombre_acceso',
                            'seguridad',
                            'activo'
                        ]);
                        
                        $datosActualizar['activo'] = filter_var($request->input('activo', false), FILTER_VALIDATE_BOOLEAN);

                        $cambiosContraseña = false;
                        if ($request->filled('contraseña')) {
                            $datosActualizar['contraseña'] = Crypt::encryptString($request->input('contraseña'));
                            $cambiosContraseña = true;
                        }

                        if ($configuracion) {
                            if ($datosActualizar['activo']) {
                                ConfiguracionCorreo::where('id', '!=', $configuracion->id)
                                    ->update(['activo' => false]);
                            }
                            
                            $configuracion->update($datosActualizar);
                            
                            $valoresNuevos = [
                                'servidor_correo' => $configuracion->servidor_correo,
                                'puerto' => $configuracion->puerto,
                                'nombre_acceso' => $configuracion->nombre_acceso,
                                'seguridad' => $configuracion->seguridad,
                                'activo' => $configuracion->activo,
                                'contraseña' => $cambiosContraseña ? '***ACTUALIZADA***' : '***SIN CAMBIOS***'
                            ];
                            
                            $detalleExtra = [];
                            if ($cambiosContraseña) {
                                $detalleExtra[] = "Contraseña actualizada";
                            }
                            
                            $this->registrarAuditoria(
                                'Actualizar',
                                'configuracion_correo',
                                $configuracion->id,
                                "Configuración de correo",
                                $valoresAnteriores,
                                $valoresNuevos,
                                !empty($detalleExtra) ? implode(' | ', $detalleExtra) : null
                            );
                            
                        } else {
                            if ($request->filled('contraseña')) {
                                $datosActualizar['contraseña'] = Crypt::encryptString($request->input('contraseña'));
                            }
                            
                            $configuracion = ConfiguracionCorreo::create($datosActualizar);
                            
                            $this->registrarAuditoria(
                                'Crear',
                                'configuracion_correo',
                                $configuracion->id,
                                "Configuración de correo",
                                null,
                                null,
                                "Servidor: {$configuracion->servidor_correo} | Puerto: {$configuracion->puerto}"
                            );
                        }
                        
                        DB::commit();

                        $data->success = true;
                        $data->message = 'Configuración de correo guardada correctamente';
                        $data->data = $configuracion;
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        \Log::error('Error al guardar configuración de correo: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        $data->success = false;
                        $data->message = 'Error al guardar: ' . $e->getMessage();
                    }
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