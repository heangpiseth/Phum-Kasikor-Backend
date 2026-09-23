<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Inventory extends Model
{
    protected $table = 'inventory';
 
    protected $fillable = [
        'farm_id',
        'category_id',
        'name',
        'quantity',
        'unit',
        'minimum_stock',
        'status',
    ];
 
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }
 
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }
}
 
