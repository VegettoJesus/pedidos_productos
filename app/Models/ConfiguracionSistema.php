<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    use HasFactory;

    protected $table = 'configuracion_sistema';
    
    protected $fillable = [
        'titulo_site',
        'abreviatura_titulo',
        'descripcion_corta',
        'icono_site',
        'email_admin',
        'footer_text',
        'max_entradas_home'
    ];

    protected $casts = [
        'max_entradas_home' => 'integer',
        'fecha_actualizacion' => 'datetime'
    ];

    public $timestamps = false;
}