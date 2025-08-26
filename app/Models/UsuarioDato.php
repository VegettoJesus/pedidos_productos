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
}
