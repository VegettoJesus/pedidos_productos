<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';

    protected $fillable = [
        'id_rol',
        'id_menus',
        'ver',
        'editar',
        'crear',
        'eliminar',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'id_menus');
    }
}
