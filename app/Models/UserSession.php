<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token',
        'device',
        'ip_address',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
        ];
    }

    publice function user()
    {
        return $this->belongsTo(User::class);
    }
}