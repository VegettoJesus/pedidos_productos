<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = ['name'];

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'id_rol');
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_rol');
    }
}
