<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'order_id', 'delivery_partner', 'tracking_number',
        'status', 'estimated_delivery_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}