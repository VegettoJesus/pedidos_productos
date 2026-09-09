<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ConfiguracionCorreo extends Model
{
    use HasFactory;

    protected $table = 'configuracion_correo';

    protected $fillable = [
        'servidor_correo',
        'puerto',
        'nombre_acceso',
        'contraseña',
        'seguridad',
        'activo'
    ];

    protected $hidden = [
        'contraseña'
    ];

    protected $casts = [
        'puerto' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Encriptar contraseña antes de guardar.
     */
    public function setContraseñaAttribute($value)
    {
        if ($value !== null && $value !== '') {
            $this->attributes['contraseña'] = Crypt::encryptString($value);
        }
    }

    /**
     * Desencriptar contraseña al obtenerla.
     */
    public function getContraseñaAttribute($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {

            \Log::error('Error al desencriptar contraseña SMTP', [
                'error' => $e->getMessage(),
                'configuracion_id' => $this->id,
            ]);

            return null;
        }
    }

    public static function getActiva()
    {
        return self::where('activo', true)->first();
    }

    public function validarConfiguracion()
    {
        $errors = [];

        if (empty($this->servidor_correo)) {
            $errors[] = 'Servidor SMTP es requerido';
        }

        if (empty($this->puerto) || !is_numeric($this->puerto)) {
            $errors[] = 'Puerto debe ser un número';
        }

        if (empty($this->nombre_acceso)) {
            $errors[] = 'Nombre de acceso es requerido';
        }

        if (empty($this->contraseña)) {
            $errors[] = 'Contraseña es requerida';
        }

        return $errors;
    }

    /**
     * Configuraciones predefinidas comunes
     */
    public static function configuracionesPredefinidas()
    {
        return [
            'gmail' => [
                'servidor_correo' => 'smtp.gmail.com',
                'puerto' => 587,
                'seguridad' => 'tls',
                'descripcion' => 'Gmail'
            ],
            'outlook' => [
                'servidor_correo' => 'smtp.office365.com',
                'puerto' => 587,
                'seguridad' => 'tls',
                'descripcion' => 'Outlook/Office 365'
            ],
            'yahoo' => [
                'servidor_correo' => 'smtp.mail.yahoo.com',
                'puerto' => 465,
                'seguridad' => 'ssl',
                'descripcion' => 'Yahoo Mail'
            ],
            'hosting' => [
                'servidor_correo' => 'mail.tudominio.com',
                'puerto' => 465,
                'seguridad' => 'ssl',
                'descripcion' => 'Hosting propio'
            ],
            'sendgrid' => [
                'servidor_correo' => 'smtp.sendgrid.net',
                'puerto' => 587,
                'seguridad' => 'tls',
                'descripcion' => 'SendGrid'
            ]
        ];
    }
}