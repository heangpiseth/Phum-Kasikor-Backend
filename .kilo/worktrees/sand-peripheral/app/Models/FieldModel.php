<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldModel extends Model
{
    protected $table = 'fields';

    protected $fillable = [
        'farm_id',
        'name',
        'area',
        'soil_type',
        'description',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }
    public function crops()
    {
        return $this->hasMany(Crop::class, 'field_id');
    }
}
