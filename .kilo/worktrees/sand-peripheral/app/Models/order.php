<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'úser_id',
        'total_amount',
        'status',
        'delivery_address',
        'latitude',
        'longitude',
        'delivery_method',
        'payment_method',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
 
    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }
}