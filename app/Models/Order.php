<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
     use HasFactory;
     
    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'delivery_address',
        'latitude',
        'longitude',
        'delivery_method',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
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