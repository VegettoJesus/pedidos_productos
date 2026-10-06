<?php
// app/Models/Carrito.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    protected $table = 'carritos';

    protected $fillable = ['user_id', 'ultima_actividad'];

    protected $casts = [
        'ultima_actividad' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(CarritoItem::class);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActivos($query, int $minutos = 120)
    {
        return $query->where('ultima_actividad', '>=', now()->subMinutes($minutos));
    }

    // ============================================
    // MÉTODOS
    // ============================================

    public static function obtenerOCrear(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            ['ultima_actividad' => now()]
        );
    }

    public function tocar()
    {
        $this->ultima_actividad = now();
        $this->saveQuietly();
    }

    /**
     * Total del carrito
     */
    public function getTotalAttribute(): float
    {
        return $this->items->sum(fn($i) => $i->cantidad * $i->precio_unitario);
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('cantidad');
    }

    /**
     * Vaciar carrito y DEVOLVER stock
     */
    public function vaciar(bool $devolverStock = true): void
    {
        if ($devolverStock) {
            foreach ($this->items as $item) {
                $item->devolverStock();
            }
        }

        $this->items()->delete();
        $this->tocar();
    }
}