<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoNotificacion extends Model
{
    use HasFactory;

    protected $table = 'tipos_notificacion';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'icono',
        'color',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}