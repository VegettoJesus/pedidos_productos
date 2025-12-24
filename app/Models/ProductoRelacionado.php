<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoRelacionado extends Model
{
    use HasFactory;

    protected $table = 'productos_relacionados';

    protected $fillable = [
        'producto_id',
        'producto_relacionado_id',
        'tipo'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function relacionado()
    {
        return $this->belongsTo(Producto::class, 'producto_relacionado_id');
    }
}
