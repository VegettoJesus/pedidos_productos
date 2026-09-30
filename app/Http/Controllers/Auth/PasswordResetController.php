<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionCorreo;
use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Helpers\ConfiguracionHelper;

class PasswordResetController extends Controller
{
    /**
     * Tiempo de validez del código en minutos
     */
    const MINUTOS_VALIDEZ = 15;

    /**
     * Solicitar código de recuperación
     * - Si ya existe uno activo, lo reutiliza
     * - Si no, genera uno nuevo y lo envía
     */
    public function solicitarCodigo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No existe una cuenta asociada a este correo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        // Verificar que no sea cliente
        if ($user->id_rol == 2) {
            return response()->json([
                'success' => false,
                'message' => 'Los clientes no pueden recuperar su contraseña desde este panel.',
            ], 403);
        }

        $registroExistente = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if ($registroExistente) {
            $creado = \Carbon\Carbon::parse($registroExistente->created_at);
            $expira = $creado->copy()->addMinutes(self::MINUTOS_VALIDEZ);

            // Si aún está activo, REUTILIZAR
            if (now()->lt($expira)) {
                $segundosRestantes = now()->diffInSeconds($expira, false);

                return response()->json([
                    'success' => true,
                    'reutilizado' => true,
                    'message' => 'Ya tienes un código activo. Revisa tu correo.',
                    'segundos_restantes' => $segundosRestantes,
                    'expira_en' => $expira->toIso8601String(),
                ]);
            }

            // Si expiró, eliminar el registro viejo
            DB::table('password_reset_tokens')->where('email', $email)->delete();
        }

        // GENERAR NUEVO CÓDIGO
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $tokenEncriptado = Hash::make($codigo);

        // Guardar en BD
        DB::table('password_reset_tokens')->insert([
            'email'      => $email,
            'token'      => $tokenEncriptado,
            'created_at' => now(),
        ]);

        // ENVIAR CORREO
        $envioExitoso = $this->enviarCorreoCodigo($user, $codigo);

        if (!$envioExitoso['success']) {
            // Eliminar el token ya que no se pudo enviar
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // CREAR NOTIFICACIÓN DE ERROR PARA ADMINS
            $this->notificarErrorCorreo($user, $envioExitoso['error']);

            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al enviar el correo. El equipo de soporte ha sido notificado.',
                'error_tecnico' => $envioExitoso['error'], // Quitar en producción
            ], 500);
        }

        return response()->json([
            'success' => true,
            'reutilizado' => false,
            'message' => 'Código enviado correctamente. Revisa tu correo.',
            'segundos_restantes' => self::MINUTOS_VALIDEZ * 60,
            'expira_en' => now()->addMinutes(self::MINUTOS_VALIDEZ)->toIso8601String(),
        ]);
    }

    /**
     * Verificar si el código sigue activo (al abrir el modal o recargar)
     */
    public function verificarCodigoActivo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'activo' => false], 422);
        }

        $email = $request->input('email');

        $registro = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$registro) {
            return response()->json([
                'success' => true,
                'activo' => false,
                'message' => 'No hay código activo.',
            ]);
        }

        $creado = \Carbon\Carbon::parse($registro->created_at);
        $expira = $creado->copy()->addMinutes(self::MINUTOS_VALIDEZ);

        if (now()->gte($expira)) {
            // Expirado → limpiar
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return response()->json([
                'success' => true,
                'activo' => false,
                'message' => 'El código ha expirado.',
            ]);
        }

        $segundosRestantes = now()->diffInSeconds($expira, false);

        return response()->json([
            'success' => true,
            'activo' => true,
            'segundos_restantes' => $segundosRestantes,
            'expira_en' => $expira->toIso8601String(),
        ]);
    }

    /**
     * Validar el código ingresado por el usuario
     */
    public function validarCodigo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'codigo' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $email = $request->input('email');
        $codigo = $request->input('codigo');

        $registro = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un código activo para este correo.',
            ], 404);
        }

        // Verificar expiración
        $creado = \Carbon\Carbon::parse($registro->created_at);
        $expira = $creado->copy()->addMinutes(self::MINUTOS_VALIDEZ);

        if (now()->gte($expira)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'El código ha expirado. Solicita uno nuevo.',
                'expirado' => true,
            ], 410);
        }

        // Verificar código
        if (!Hash::check($codigo, $registro->token)) {
            return response()->json([
                'success' => false,
                'message' => 'El código ingresado es incorrecto.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Código validado correctamente.',
        ]);
    }

    /**
     * Cambiar la contraseña después de validar el código
     */
    public function cambiarPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'codigo' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $email = $request->input('email');
        $codigo = $request->input('codigo');

        $registro = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un código activo para este correo.',
            ], 404);
        }

        // Verificar expiración
        $creado = \Carbon\Carbon::parse($registro->created_at);
        if (now()->gte($creado->copy()->addMinutes(self::MINUTOS_VALIDEZ))) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'El código ha expirado. Solicita uno nuevo.',
                'expirado' => true,
            ], 410);
        }

        // Verificar código
        if (!Hash::check($codigo, $registro->token)) {
            return response()->json([
                'success' => false,
                'message' => 'El código ingresado es incorrecto.',
            ], 401);
        }

        // Cambiar contraseña
        $user = User::where('email', $email)->first();
        $user->password = Hash::make($request->input('password'));
        $user->save();

        // Eliminar token usado
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Crear notificación de seguridad
        $this->notificarCambioPassword($user);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    // ============================================
    // MÉTODOS PRIVADOS
    // ============================================

    /**
     * Enviar correo con el código
     */
    private function enviarCorreoCodigo(User $user, string $codigo): array
    {
        try {
            $configuracion = ConfiguracionCorreo::getActiva();

            if (!$configuracion) {
                return ['success' => false, 'error' => 'No hay configuración de correo activa.'];
            }

            $errores = $configuracion->validarConfiguracion();
            if (!empty($errores)) {
                return ['success' => false, 'error' => 'Configuración incompleta: ' . implode(', ', $errores)];
            }

            // Configurar mailer dinámico
            $config = [
                'transport'  => 'smtp',
                'host'       => $configuracion->servidor_correo,
                'port'       => $configuracion->puerto,
                'encryption' => $configuracion->seguridad === 'ninguna' ? null : $configuracion->seguridad,
                'username'   => $configuracion->nombre_acceso,
                'password'   => $configuracion->contraseña,
                'timeout'    => 30,
            ];

            config(['mail.mailers.dynamic_smtp' => $config]);

            $fromEmail = $config['username'];
            $fromName  = ConfiguracionHelper::getCompanyName();

            Mail::mailer('dynamic_smtp')->send([], [], function ($message) use ($user, $codigo, $fromEmail, $fromName) {
                $message->to($user->email, $user->nombres . ' ' . $user->apellidos)
                        ->from($fromEmail, $fromName)
                        ->subject('🔐 Código de recuperación de contraseña')
                        ->html($this->htmlCorreo($user, $codigo));
            });

            return ['success' => true];
        } catch (\Throwable $e) {
            \Log::error('Error enviando correo de recuperación', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * HTML del correo
     */
    private function htmlCorreo(User $user, string $codigo): string
    {
        $nombre = $user->nombres;
        $minutos = self::MINUTOS_VALIDEZ;
        $appName = ConfiguracionHelper::getCompanyName();
        $year = date('Y');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #FEEBC3; padding: 20px; margin: 0; }
                .container { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
                .header { background: linear-gradient(135deg, #f35b08, #f77819); color: #fff; padding: 32px 24px; text-align: center; }
                .header h1 { margin: 0; font-size: 22px; }
                .content { padding: 32px 28px; color: #333; }
                .content p { font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
                .code-box { background: #FEEBC3; border: 2px dashed #F4AB28; border-radius: 12px; padding: 22px; text-align: center; margin: 24px 0; }
                .code { font-size: 36px; font-weight: bold; color: #f35b08; letter-spacing: 8px; font-family: 'Courier New', monospace; }
                .code-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 2px; margin-top: 6px; }
                .warning { background: #fff4e5; border-left: 4px solid #F4AB28; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #8a5a00; }
                .footer { background: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #999; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔐 Recuperación de contraseña</h1>
                </div>
                <div class="content">
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña en <strong>{$appName}</strong>.</p>
                    <p>Tu código de verificación es:</p>
                    <div class="code-box">
                        <div class="code">{$codigo}</div>
                        <div class="code-label">Código de verificación</div>
                    </div>
                    <div class="warning">
                        ⏱️ Este código expira en <strong>{$minutos} minutos</strong>.<br>
                        Si no solicitaste este cambio, ignora este correo.
                    </div>
                    <p style="margin-top: 24px; color: #888; font-size: 13px;">
                        Por seguridad, nunca compartas este código con nadie.
                    </p>
                </div>
                <div class="footer">
                    &copy; {$year} {$appName}. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Notificar a admins sobre error de envío de correo
     */
    private function notificarErrorCorreo(User $user, string $error): void
    {
        try {
            $rolAdmin = \App\Models\Rol::where('name', 'admin')->first();
            if (!$rolAdmin) return;

            Notificacion::create([
                'tipo_notificacion_id' => TipoNotificacion::where('slug', 'error-sistema')->value('id'),
                'titulo' => '❌ Error al enviar correo de recuperación',
                'mensaje' => "No se pudo enviar el código de recuperación a {$user->email} ({$user->nombres} {$user->apellidos}). Error: {$error}",
                'mensaje_corto' => 'Error al enviar correo de recuperación',
                'prioridad' => 'alta',
                'rol_id' => $rolAdmin->id,
                'data_extra' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $error,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al crear notificación de error de correo', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notificar cambio de contraseña exitoso
     */
    private function notificarCambioPassword(User $user): void
    {
        try {
            Notificacion::create([
                'tipo_notificacion_id' => TipoNotificacion::where('slug', 'seguridad')->value('id'),
                'titulo' => '✅ Contraseña restablecida',
                'mensaje' => 'Tu contraseña ha sido restablecida exitosamente mediante el código de verificación.',
                'mensaje_corto' => 'Contraseña restablecida',
                'prioridad' => 'media',
                'usuario_id' => $user->id,
                'url' => '',
                'boton_texto' => '',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al crear notificación de cambio de password', ['error' => $e->getMessage()]);
        }
    }
}