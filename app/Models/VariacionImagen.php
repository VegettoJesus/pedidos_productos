<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariacionImagen extends Model
{
    use HasFactory;

    protected $table = 'variacion_imagenes';

    protected $fillable = ['variacion_id', 'imagen_path', 'orden'];

    public function variacion()
    {
        return $this->belongsTo(ProductoVariacion::class);
    }
}