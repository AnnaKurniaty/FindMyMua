<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'title', 'body', 'read', 'sent_at'
    ];

    protected $casts = [
        'read' => 'boolean',
        'sent_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

