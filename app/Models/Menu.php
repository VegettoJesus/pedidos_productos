<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menus';

    protected $fillable = [
        'nombre',
        'url',
        'padre',
        'orden',
        'icono',
    ];

    public function hijos()
    {
        return $this->hasMany(Menu::class, 'padre');
    }

    public function padreMenu()
    {
        return $this->belongsTo(Menu::class, 'padre');
    }

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'id_menus');
    }
}
