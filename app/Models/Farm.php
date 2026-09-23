<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
    protected $fillable = [
        'user_id',
        'farm_name',
        'description',
        'location',
        'latitude',
        'longitude',
        'farm_size',
        'farming_method',
        'cover_image',
    ];

    // ============================================================
    // USER
    // ============================================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================================
    // IMAGES
    // ============================================================

    public function images()
    {
        return $this->hasMany(FarmImage::class);
    }

    // ============================================================
    // FIELDS
    // ============================================================

    public function fields()
    {
        return $this->hasMany(FieldModel::class);
    }

    // ============================================================
    // CROPS
    // ============================================================

    public function crops()
    {
        return $this->hasMany(Crop::class);
    }

    // ============================================================
    // WATERING LOGS
    // ============================================================

    public function wateringLogs()
    {
        return $this->hasMany(WateringLog::class);
    }

    // ============================================================
    // HARVEST LOGS
    // ============================================================

    public function harvestLogs()
    {
        return $this->hasMany(HarvestLog::class);
    }

    // ============================================================
    // INVENTORY
    // ============================================================

    public function inventory()
    {
        return $this->hasMany(
            Inventory::class,
            'farm_id'
        );
    }

    // ============================================================
    // PRODUCTS
    // ============================================================

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}