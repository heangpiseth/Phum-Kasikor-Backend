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

    // ============================================================
    // FARM
    // ============================================================

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    // ============================================================
    // CROPS
    // ============================================================

    public function crops()
    {
        return $this->hasMany(
            Crop::class,
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
            'field_id'
        );
    }

    // ============================================================
    // HARVEST LOGS
    // ============================================================

    public function harvestLogs()
    {
        return $this->hasMany(
            HarvestLog::class,
            'field_id'
        );
    }
}