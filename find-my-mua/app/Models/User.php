<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function muaProfile()
    {
        return $this->hasOne(MuaProfile::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'mua_id');
    }

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class, 'mua_id');
    }

    public function bookingsAsCustomer()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function bookingsAsMua()
    {
        return $this->hasMany(Booking::class, 'mua_id');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'customer_id');
    }
}