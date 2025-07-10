<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MuaProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'certification',
        'service_area',
        'studio_lat',
        'studio_lng',
        'makeup_styles',
        'makeup_specializations',
        'available_days',
        'available_start_time',
        'available_end_time',
        'profile_photo',
        'skin_type'
    ];

    protected $casts = [
        'makeup_styles' => 'array',
        'makeup_specializations' => 'array',
        'skin_type' => 'array',
        'available_days' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
