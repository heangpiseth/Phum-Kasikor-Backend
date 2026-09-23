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
            'quantity_planted' => 'decimal:2',
        ];
    }

    // ============================================================
    // FARM
    // ============================================================

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    // ============================================================
    // FIELD
    // ============================================================

    public function field()
    {
        return $this->belongsTo(
            FieldModel::class,
            'field_id'
        );
    }

    // ============================================================
    // WATERING LOGS
    // ============================================================

    public function wateringLogs()
    {
        return $this->hasMany(
            WateringLog::class,
            'crop_id'
        );
    }

    // ============================================================
    // HARVEST LOGS
    // ============================================================

    public function harvestLogs()
    {
        return $this->hasMany(
            HarvestLog::class,
            'crop_id'
        );
    }
}