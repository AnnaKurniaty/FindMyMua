<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'mua_id',
        'name',
        'description',
        'price',
        'duration',
        'photo',
        'makeup_style'
    ];
    protected $appends = ['formatted_price'];

    public function mua()
    {
        return $this->belongsTo(User::class, 'mua_id');
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}
