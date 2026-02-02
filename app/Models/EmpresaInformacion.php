<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresaInformacion extends Model
{
    use HasFactory;

    protected $table = 'empresa_informacion';

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'propietario_nombre',
        'propietario_apellido',
        'direccion',
        'ubigeo',
        'departamento_id',
        'provincia_id',
        'distrito_id',
        'maps_url',
        'telefono',
        'celular'
    ];

    protected $appends = [
        'propietario_completo',
        'direccion_completa',
        'telefono_formateado',
        'whatsapp_link'
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }

    public function getPropietarioCompletoAttribute()
    {
        return trim($this->propietario_nombre . ' ' . $this->propietario_apellido);
    }

    public function getDireccionCompletaAttribute()
    {
        return trim(sprintf(
            '%s, %s, %s, %s',
            $this->direccion,
            optional($this->distrito)->nombre,
            optional($this->provincia)->nombre,
            optional($this->departamento)->nombre
        ));
    }

    public function getTelefonoFormateadoAttribute()
    {
        if (!$this->telefono) {
            return null;
        }

        if (str_starts_with($this->telefono, '+51')) {
            return $this->telefono;
        }

        if (str_starts_with($this->telefono, '1') || str_starts_with($this->telefono, '01')) {
            return '+51 ' . ltrim($this->telefono, '0');
        }

        return $this->telefono;
    }

    public function getWhatsappLinkAttribute()
    {
        if (!$this->celular) {
            return null;
        }

        $celular = preg_replace('/[^0-9]/', '', $this->celular);

        if (str_starts_with($celular, '51')) {
            $celular = substr($celular, 2);
        }

        if (str_starts_with($celular, '9')) {
            $celular = '51' . $celular;
        }

        return 'https://wa.me/' . $celular;
    }
}
