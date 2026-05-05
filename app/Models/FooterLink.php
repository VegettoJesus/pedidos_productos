<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterLink extends Model
{
    protected $table = 'footer_links';
    
    protected $fillable = [
        'column_id',
        'text',
        'url',
        'sort_order',
        'active',
        'icon'
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