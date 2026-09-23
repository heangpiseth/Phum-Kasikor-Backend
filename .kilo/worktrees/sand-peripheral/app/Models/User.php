<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserRole;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'display_name',
        'bio',
        'email',
        'phone',
        'firebase_uid',
        'password',
        'role',
        'profile_image',
        'location',
        'latitude',
        'longitude',
        'gender',
        'date_of_birth',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'date_of_birth' => 'date',
        ];
    }
    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }
    public function isFarmer(): bool
    {
        return $this->role === UserRole::FARMER;
    }
    public function verifications()
    {
        return $this->hasMany(UserVerification::class);
    }
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }
    public function farms()
    {
        return $this->hasMany(Farm::class);
    }
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
    public function farmFavorites()
    {
        return $this->hasMany(farmFavorites::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
