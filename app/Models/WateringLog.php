<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WateringLog extends Model
{
    protected $fillable = [
        'farm_id',
        'field_id',
        'crop_id',
        'watering_date',
        'water_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'watering_date' => 'date',
            'water_amount' => 'decimal:2',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function field()
    {
        return $this->belongsTo(
            FieldModel::class,
            'field_id'
        );
    }

    public function crop()
    {
        return $this->belongsTo(
            Crop::class,
            'crop_id'
        );
    }
}