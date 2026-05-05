<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FooterColumn extends Model
{
    protected $table = 'footer_columns';
    
    protected $fillable = [
        'title',
        'column_type',
        'sort_order',
        'active',
        'icon'
    ];
    
    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer'
    ];
    
    // Relaciones
    public function links(): HasMany
    {
        return $this->hasMany(FooterLink::class, 'column_id')->orderBy('sort_order');
    }
    
    public function contact(): HasOne
    {
        return $this->hasOne(FooterContact::class, 'column_id');
    }
    
    public function socialNetworks(): HasMany
    {
        return $this->hasMany(FooterSocial::class, 'column_id')->orderBy('sort_order');
    }
}