<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HarvestLog extends Model
{
    protected $fillable = [
        'farm_id',
        'field_id',
        'crop_id',
        'harvest_date',
        'quantity',
        'quality',
        'condition',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'harvest_date' => 'date',
            'quantity' => 'decimal:2',
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
    // CROP
    // ============================================================

    public function crop()
    {
        return $this->belongsTo(
            Crop::class,
            'crop_id'
        );
    }
}