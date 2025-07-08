<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'customer_id', 'mua_id', 'service_id', 'date', 'time',
        'status', 'payment_status', 'total_price', 'payment_method',
        'customer_skin_profile_snapshot'
    ];

    protected $casts = [
        'customer_skin_profile_snapshot' => 'array'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function mua()
    {
        return $this->belongsTo(User::class, 'mua_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
