<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Product extends Model
{
    protected $fillable = [
        'farm_id',
        'category_id',
        'name',
        'description',
        'price',
        'unit',
        'quantity_available',
        'harvest_date',
        'farming_method',
        'is_active',
    ];
 
    protected function casts(): array
    {
        return [
            'harvest_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }
 
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
 
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
 
