<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'mua_id', 'name', 'description', 'price',
        'duration_minutes', 'location_type', 'cancellation_policy'
    ];

    public function mua()
    {
        return $this->belongsTo(User::class, 'mua_id');
    }
}
