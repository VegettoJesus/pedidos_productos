<?php

namespace App\Http\Controllers;

use App\Models\TemaColor;
use App\Models\ConfiguracionColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Auditoria;

class Temas extends Controller
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

    public function editorAdministracion(Request $request)
    {
        if ($request->isMethod('post')) {
            $opcion = $request->input('opcion');
            $data = new \stdClass();

            switch ($opcion) {
                case 'Listar':
                    $temas = TemaColor::withCount('configuraciones')->get();
                    $data->respuesta = 'success';
                    $data->success = true;
                    $data->data = $temas;
                    break;

                case 'Crear':
                    $validator = Validator::make($request->all(), [
                        'nombre_tema' => 'required|string|max:100|unique:temas_colores,nombre_tema',
                        'descripcion' => 'nullable|string',
                        'es_predeterminado' => 'boolean',
                        'duplicar_tema_id' => 'nullable|exists:temas_colores,id'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    // Si se marca como predeterminado, desmarcar otros
                    if ($request->es_predeterminado) {
                        TemaColor::where('es_predeterminado', true)->update(['es_predeterminado' => false]);
                    }

                    $tema = TemaColor::create([
                        'nombre_tema' => $request->nombre_tema,
                        'descripcion' => $request->descripcion,
                        'es_predeterminado' => $request->es_predeterminado ?? false
                    ]);

                    // Si se especifica un tema a duplicar, copiar sus configuraciones
                    if ($request->duplicar_tema_id) {
                        $configuraciones = ConfiguracionColor::where('tema_id', $request->duplicar_tema_id)->get();
                        
                        foreach ($configuraciones as $config) {
                            ConfiguracionColor::create([
                                'tema_id' => $tema->id,
                                'variable_nombre' => $config->variable_nombre,
                                'variable_valor' => $config->variable_valor,
                                'grupo' => $config->grupo,
                                'descripcion' => $config->descripcion,
                                'orden' => $config->orden
                            ]);
                        }
                    }

                    $this->registrarAuditoria('crear', 'temas_colores', $tema->id, "Nuevo tema creado: {$tema->nombre_tema}");

                    $data->success = true;
                    $data->message = 'Tema creado correctamente';
                    $data->data = $tema;
                    break;

                case 'Activar':
                    $validator = Validator::make($request->all(), [
                        'id' => 'required|exists:temas_colores,id'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $tema = TemaColor::find($request->id);
                    $tema->activar();

                    $this->registrarAuditoria('activar', 'temas_colores', $tema->id, "Tema activado: {$tema->nombre_tema}");

                    $data->success = true;
                    $data->message = 'Tema activado correctamente';
                    $data->data = $tema;
                    break;

                case 'Eliminar':
                    $validator = Validator::make($request->all(), [
                        'id' => 'required|exists:temas_colores,id'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $tema = TemaColor::find($request->id);

                    // No permitir eliminar tema predeterminado
                    if ($tema->es_predeterminado) {
                        $data->success = false;
                        $data->message = 'No se puede eliminar el tema predeterminado';
                        return response()->json($data, 400);
                    }

                    // No permitir eliminar tema activo
                    if ($tema->activo) {
                        $data->success = false;
                        $data->message = 'No se puede eliminar el tema activo. Active otro tema primero.';
                        return response()->json($data, 400);
                    }

                    $nombreTema = $tema->nombre_tema;
                    $tema->delete();

                    $this->registrarAuditoria('eliminar', 'temas_colores', null, "Tema eliminado: {$nombreTema}");

                    $data->success = true;
                    $data->message = 'Tema eliminado correctamente';
                    break;

                case 'ObtenerConfiguraciones':
                    $validator = Validator::make($request->all(), [
                        'tema_id' => 'required|exists:temas_colores,id'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $configuraciones = ConfiguracionColor::where('tema_id', $request->tema_id)
                        ->orderBy('grupo')
                        ->orderBy('orden')
                        ->get()
                        ->groupBy('grupo');

                    $data->success = true;
                    $data->data = $configuraciones;
                    break;

                case 'GuardarConfiguracion':
                    $validator = Validator::make($request->all(), [
                        'tema_id' => 'required|exists:temas_colores,id',
                        'variable_nombre' => 'required|string|max:50',
                        'variable_valor' => 'required|string|max:20',
                        'grupo' => 'required|in:bordes,fondos,textos,sidebar,tablas,cards,botones,tooltips,paginate',
                        'descripcion' => 'nullable|string',
                        'orden' => 'nullable|integer'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $config = ConfiguracionColor::updateOrCreate(
                        [
                            'tema_id' => $request->tema_id,
                            'variable_nombre' => $request->variable_nombre
                        ],
                        [
                            'variable_valor' => $request->variable_valor,
                            'grupo' => $request->grupo,
                            'descripcion' => $request->descripcion,
                            'orden' => $request->orden ?? 0
                        ]
                    );

                    $this->registrarAuditoria('actualizar', 'configuracion_colores', $config->id, "Configuración actualizada: {$config->variable_nombre} = {$config->variable_valor}");

                    $data->success = true;
                    $data->message = 'Configuración guardada correctamente';
                    $data->data = $config;
                    break;

                case 'EliminarConfiguracion':
                    $validator = Validator::make($request->all(), [
                        'id' => 'required|exists:configuracion_colores,id'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    $config = ConfiguracionColor::find($request->id);
                    $config->delete();

                    $this->registrarAuditoria('eliminar', 'configuracion_colores', null, "Configuración eliminada: {$config->variable_nombre}");

                    $data->success = true;
                    $data->message = 'Configuración eliminada correctamente';
                    break;

                case 'AplicarPreconfiguracion':
                    $validator = Validator::make($request->all(), [
                        'tema_id' => 'required|exists:temas_colores,id',
                        'preconfiguracion' => 'required|in:claro,oscuro,navidenio,corporate'
                    ]);

                    if ($validator->fails()) {
                        $data->success = false;
                        $data->message = 'Error de validación';
                        $data->errors = $validator->errors();
                        return response()->json($data, 422);
                    }

                    // Eliminar configuraciones existentes
                    ConfiguracionColor::where('tema_id', $request->tema_id)->delete();

                    $this->registrarAuditoria('actualizar', 'configuracion_colores', null, "Preconfiguración aplicada: {$request->preconfiguracion}");

                    $data->success = true;
                    $data->message = 'Preconfiguración aplicada correctamente';
                    break;

                default:
                    $data->success = false;
                    $data->message = 'Opción inválida';
                    break;
            }

            return response()->json($data);
        } else {
            $data = new \stdClass();
            $data->temas = TemaColor::all();
            $data->script = 'js/temasColores.js';
            $data->css = 'css/administracion.css';
            $data->contenido = 'temas.editorAdministracion';
            return view('layouts.contenido', (array) $data);
        }
    }

    // Método para obtener CSS del tema activo
    public function obtenerCssActivo()
    {
        $css = ConfiguracionColor::generarCssTemaActivo();
        
        return response($css)->header('Content-Type', 'text/css');
    }

    // Agregar este método al controlador
public function previsualizarTema(Request $request)
{
    $validator = Validator::make($request->all(), [
        'tema_id' => 'required|exists:temas_colores,id'
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    // Obtener configuraciones del tema
    $configuraciones = ConfiguracionColor::where('tema_id', $request->tema_id)
        ->orderBy('grupo')
        ->orderBy('orden')
        ->get();

    // Generar CSS temporal
    $css = ":root {\n";
    foreach ($configuraciones as $variable) {
        $css .= "    {$variable->variable_nombre}: {$variable->variable_valor};\n";
    }
    $css .= "}\n";

    // Agregar las mismas reglas adicionales
    $css .= ConfiguracionColor::generarReglasCssAdicionales($configuraciones);

    return response()->json([
        'success' => true,
        'css' => $css
    ]);
}
}