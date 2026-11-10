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
use App\Helpers\ConfiguracionHelper;

class PasswordResetClienteController extends Controller
{
    const MINUTOS_VALIDEZ = 15;

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

        // ✅ VALIDACIÓN PARA CLIENTES (rol 2)
        if ($user->id_rol != 2) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta no está registrada como cliente.',
            ], 403);
        }

        $registroExistente = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if ($registroExistente) {
            $creado = \Carbon\Carbon::parse($registroExistente->created_at);
            $expira = $creado->copy()->addMinutes(self::MINUTOS_VALIDEZ);

            if (now()->lt($expira)) {
                $segundosRestantes = now()->diffInSeconds($expira, false);
                return response()->json([
                    'success' => true,
                    'reutilizado' => true,
                    'message' => 'Ya tienes un código activo. Revisa tu correo.',
                    'segundos_restantes' => $segundosRestantes,
                ]);
            }

            DB::table('password_reset_tokens')->where('email', $email)->delete();
        }

        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $tokenEncriptado = Hash::make($codigo);

        DB::table('password_reset_tokens')->insert([
            'email'      => $email,
            'token'      => $tokenEncriptado,
            'created_at' => now(),
        ]);

        $envioExitoso = $this->enviarCorreoCodigo($user, $codigo);

        if (!$envioExitoso['success']) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al enviar el correo. Intenta más tarde.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'reutilizado' => false,
            'message' => 'Código enviado correctamente. Revisa tu correo.',
            'segundos_restantes' => self::MINUTOS_VALIDEZ * 60,
        ]);
    }

    public function verificarCodigoActivo(Request $request)
    {
        // Misma lógica, sin restricciones de rol
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'activo' => false], 422);
        }

        $email = $request->input('email');
        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$registro) {
            return response()->json(['success' => true, 'activo' => false]);
        }

        $creado = \Carbon\Carbon::parse($registro->created_at);
        $expira = $creado->copy()->addMinutes(self::MINUTOS_VALIDEZ);

        if (now()->gte($expira)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json(['success' => true, 'activo' => false]);
        }

        return response()->json([
            'success' => true,
            'activo' => true,
            'segundos_restantes' => now()->diffInSeconds($expira, false),
        ]);
    }

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
        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un código activo para este correo.',
            ], 404);
        }

        $creado = \Carbon\Carbon::parse($registro->created_at);
        if (now()->gte($creado->copy()->addMinutes(self::MINUTOS_VALIDEZ))) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'El código ha expirado. Solicita uno nuevo.',
                'expirado' => true,
            ], 410);
        }

        if (!Hash::check($codigo, $registro->token)) {
            return response()->json([
                'success' => false,
                'message' => 'El código ingresado es incorrecto.',
            ], 401);
        }

        return response()->json(['success' => true]);
    }

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
        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un código activo para este correo.',
            ], 404);
        }

        $creado = \Carbon\Carbon::parse($registro->created_at);
        if (now()->gte($creado->copy()->addMinutes(self::MINUTOS_VALIDEZ))) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'El código ha expirado. Solicita uno nuevo.',
                'expirado' => true,
            ], 410);
        }

        if (!Hash::check($codigo, $registro->token)) {
            return response()->json([
                'success' => false,
                'message' => 'El código ingresado es incorrecto.',
            ], 401);
        }

        $user = User::where('email', $email)->first();
        $user->password = Hash::make($request->input('password'));
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Notificación de seguridad (opcional para cliente)
        // $this->notificarCambioPassword($user);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    private function enviarCorreoCodigo(User $user, string $codigo): array
    {
        try {
            $configuracion = ConfiguracionCorreo::getActiva();

            if (!$configuracion) {
                return ['success' => false, 'error' => 'No hay configuración de correo activa.'];
            }

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
                        ->subject('🔐 Recuperación de contraseña - ' . ConfiguracionHelper::getCompanyName())
                        ->html($this->htmlCorreo($user, $codigo));
            });

            return ['success' => true];
        } catch (\Throwable $e) {
            \Log::error('Error enviando correo de recuperación cliente', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

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
                body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; margin: 0; }
                .container { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
                .header { background: linear-gradient(135deg, #f4ab27, #e69c1f); color: #fff; padding: 32px 24px; text-align: center; }
                .header h1 { margin: 0; font-size: 22px; }
                .content { padding: 32px 28px; color: #333; }
                .content p { font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
                .code-box { background: #fdfaf3; border: 2px dashed #f4ab27; border-radius: 12px; padding: 22px; text-align: center; margin: 24px 0; }
                .code { font-size: 36px; font-weight: bold; color: #e69c1f; letter-spacing: 8px; font-family: 'Courier New', monospace; }
                .warning { background: #fff4e5; border-left: 4px solid #f4ab27; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #8a5a00; }
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
                    <p>Recibimos una solicitud para restablecer tu contraseña en <strong>{$appName}</strong>.</p>
                    <p>Tu código de verificación es:</p>
                    <div class="code-box">
                        <div class="code">{$codigo}</div>
                    </div>
                    <div class="warning">
                        ⏱️ Este código expira en <strong>{$minutos} minutos</strong>.<br>
                        Si no solicitaste este cambio, ignora este correo.
                    </div>
                </div>
                <div class="footer">
                    &copy; {$year} {$appName}. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}