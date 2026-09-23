<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    protected $fillable = [
        'farm_id',
        'field_id',
        'name',
        'variety',
        'planting_date',
        'expected_harvest_date',
        'quantity_planted',
        'growth_stage',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'planting_date' => 'date',
            'expected_harvest_date' => 'date',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function field()
    {
        return $this->belongsTo(FieldModel::class, 'field_id');
    }
    public function wateringLogs()
    {
        return $this->hasMany(WateringLog::class);
    }
    public function harvestLogs()
    {
        return $this->hasMany(HarvestLog::class);
    }
}
