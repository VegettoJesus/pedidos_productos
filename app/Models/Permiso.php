<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permiso extends Model
{
    protected $fillable = [
        'id_rol',
        'id_menus',
        'permisos',
    ];

    protected $casts = [
        'permisos' => 'array', 
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
