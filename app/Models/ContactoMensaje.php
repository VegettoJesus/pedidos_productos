<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactoMensaje extends Model
{
    use HasFactory;

    protected $table = 'contacto_mensajes';

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'asunto',
        'mensaje',
        'ip',
        'user_agent',
        'estado',
        'nota_admin',
        'leido_en',
        'respondido_en',
        'respondido_por',
    ];

    protected $casts = [
        'leido_en'      => 'datetime',
        'respondido_en' => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function respondidoPor()
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }

    public function scopeNuevos($query)
    {
        return $query->where('estado', 'nuevo');
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['nuevo', 'leido']);
    }

    public function scopeRecientes($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function getEstadoBadgeAttribute(): array
    {
        return match ($this->estado) {
            'nuevo'      => ['label' => 'Nuevo',      'class' => 'bg-primary'],
            'leido'      => ['label' => 'Leído',      'class' => 'bg-info'],
            'respondido' => ['label' => 'Respondido', 'class' => 'bg-success'],
            'archivado'  => ['label' => 'Archivado',  'class' => 'bg-secondary'],
            default      => ['label' => ucfirst($this->estado), 'class' => 'bg-secondary'],
        };
    }

    public function getResumenMensajeAttribute(): string
    {
        return \Str::limit($this->mensaje, 80);
    }
}