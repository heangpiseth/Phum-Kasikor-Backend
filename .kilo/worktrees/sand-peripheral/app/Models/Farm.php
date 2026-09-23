<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
    protected $fillable = [
        'uder_id',
        'farm_name',
        'description',
        'location',
        'latitude',
        'longitude',
        'farm_size',
        'farming_method',
        'cover_image',
    ];

    public function user()
    {
        return $this->hasMany(User::class);
    }
    public function images()
    {
        return $this->hasMany(FarmImage::class);
    }
    public function fields()
    {
        return $this->hasMany(FieldModel::class);
    }
    public function crops()
    {
        return $this->hasMany(Crop::class);
    }
    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}