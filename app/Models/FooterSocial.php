<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterSocial extends Model
{
    protected $table = 'footer_social';
    
    protected $fillable = [
        'column_id',
        'name',
        'icon',
        'url',
        'sort_order',
        'active'
    ];
    
    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer'
    ];
    
    public function column()
    {
        return $this->belongsTo(FooterColumn::class, 'column_id');
    }
}