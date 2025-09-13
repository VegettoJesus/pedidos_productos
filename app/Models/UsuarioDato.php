<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsuarioDato extends Model
{
    use HasFactory;

    protected $table = 'usuarios_datos';
    public $timestamps = false;

    protected $fillable = [
        'tipoDoc',
        'numeroDoc',
        'direccion',
        'celular',
        'fecha_nacimiento',
        'imagen',
        'id_usuario',
        'nacionalidad',
        'distrito',
        'provincia',
        'departamento',
        'cod_postal'
    ];

    // Relación con el modelo User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento');
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia');
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class, 'distrito');
    }

}
