<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    protected $fillable = [
        'user_id', 'skin_tone', 'skin_type', 'skin_issues',
        'skincare_history', 'allergies', 'makeup_preferences', 'profile_photo'
    ];

    protected $casts = [
        'skin_issues' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
