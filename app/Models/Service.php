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
        'makeup_style',
        'category'
    ];
    protected $appends = ['formatted_price', 'service_photo_url'];

    public function mua()
    {
        return $this->belongsTo(User::class, 'mua_id');
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getServicePhotoUrlAttribute()
    {
        if ($this->photo) {
            // If photo contains full URL, return as is
            if (str_starts_with($this->photo, 'http')) {
                return $this->photo;
            }
            // Otherwise, construct the storage URL
            return asset('storage/images/service_photos/' . $this->photo);
        }
        return null;
    }
}
