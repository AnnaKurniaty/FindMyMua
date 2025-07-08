<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MuaProfile extends Model
{
    protected $fillable = [
        'user_id', 'bio', 'certification', 'service_area',
        'studio_lat', 'studio_lng'
    ];

    protected $casts = [
        'makeup_specializations' => 'array',
        'makeup_styles' => 'array',
    ];    

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
