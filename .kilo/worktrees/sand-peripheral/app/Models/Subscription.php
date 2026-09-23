<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'farm_id', 'type', 'frequency',
        'price', 'status', 'next_delivery_date',
    ];

    protected function casts(): array
    {
        return ['next_delivery_date' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }
}