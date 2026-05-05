<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterContact extends Model
{
    protected $table = 'footer_contact';
    
    protected $fillable = [
        'column_id',
        'phone',
        'email',
        'address',
        'phone_icon',
        'email_icon',
        'address_icon'
    ];
    
    public function column()
    {
        return $this->belongsTo(FooterColumn::class, 'column_id');
    }
}