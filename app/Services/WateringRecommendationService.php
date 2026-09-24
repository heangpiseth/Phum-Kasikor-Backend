<?php

namespace App\Services;

use App\Models\Crop;
use App\Services\WeatherService;
use Carbon\Carbon;

class WateringRecommendationService
{
    public function __construct(
        protected WeatherService $weatherService
    ) {}

    public function getRecommendation(Crop $crop): array
    {
        $farm = $crop->farm;

        if (!$farm) {
            throw new \RuntimeException('Crop is not attached to a farm.');
        }

        $latitude = $farm->latitude;
        $longitude = $farm->longitude;

        if (!$latitude || !$longitude) {
            throw new \RuntimeException(
                'Farm location is required for weather-based watering recommendations.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | REAL WEATHER
        |--------------------------------------------------------------------------
        */

        $weather = $this->weatherService->getForecast(
            (float) $latitude,
            (float) $longitude
        );

        /*
        |--------------------------------------------------------------------------
        | CROP INFORMATION
        |--------------------------------------------------------------------------
        */

        $plantingDate = $crop->planting_date
            ? Carbon::parse($crop->planting_date)
            : null;

        $daysAfterPlanting = $plantingDate
            ? $plantingDate->diffInDays(now())
            : null;

        $growthStage = $this->getGrowthStage(
            $crop,
            $daysAfterPlanting
        );

        /*
        |--------------------------------------------------------------------------
        | WEATHER VALUES
        |--------------------------------------------------------------------------
        */

        $temperature = (float) ($weather['temperature'] ?? 0);
        $rainfall = (float) ($weather['rainfall_mm'] ?? 0);
        $rainProbability = (float) ($weather['rain_probability'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | WATERING DECISION
        |--------------------------------------------------------------------------
        */

        $watering = $this->calculateWatering(
            $temperature,
            $rainfall,
            $rainProbability,
            $growthStage
        );

        /*
        |--------------------------------------------------------------------------
        | HARVEST RELATIONSHIP
        |--------------------------------------------------------------------------
        */

        $harvest = $this->calculateHarvest(
            $crop,
            $plantingDate,
            $daysAfterPlanting,
            $growthStage
        );

        return [
            'crop' => [
                'id' => $crop->id,
                'name' => $crop->name,
                'growth_stage' => $growthStage,
                'planting_date' => $plantingDate?->toDateString(),
                'days_after_planting' => $daysAfterPlanting,
            ],

            'weather' => [
                'temperature' => $temperature,
                'rainfall_mm' => $rainfall,
                'rain_probability' => $rainProbability,
            ],

            'watering' => $watering,

            'harvest' => $harvest,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GROWTH STAGE
    |--------------------------------------------------------------------------
    */

    private function getGrowthStage(
        Crop $crop,
        ?int $daysAfterPlanting
    ): string {
        /*
         * If your crop table already has a growth_stage column,
         * use it first.
         */
        if (!empty($crop->growth_stage)) {
            return strtolower($crop->growth_stage);
        }

        if ($daysAfterPlanting === null) {
            return 'unknown';
        }

        if ($daysAfterPlanting <= 14) {
            return 'seedling';
        }

        if ($daysAfterPlanting <= 35) {
            return 'vegetative';
        }

        if ($daysAfterPlanting <= 60) {
            return 'flowering';
        }

        if ($daysAfterPlanting <= 90) {
            return 'fruiting';
        }

        return 'mature';
    }

    /*
    |--------------------------------------------------------------------------
    | WATERING
    |--------------------------------------------------------------------------
    */

    private function calculateWatering(
        float $temperature,
        float $rainfall,
        float $rainProbability,
        string $growthStage
    ): array {
        /*
         * Rain already received.
         */
        if ($rainfall >= 10) {
            return [
                'required' => false,
                'recommended_amount_mm' => 0,
                'timing' => 'Wait',
                'reason' => 'Recent rainfall is sufficient. Additional watering is not recommended now.',
            ];
        }

        /*
         * Significant rain is expected.
         */
        if ($rainProbability >= 70) {
            return [
                'required' => false,
                'recommended_amount_mm' => 0,
                'timing' => 'Wait for rain',
                'reason' => 'High probability of rain. Avoid unnecessary irrigation.',
            ];
        }

        /*
         * Base watering amount by crop stage.
         */
        $amount = match ($growthStage) {
            'seedling' => 4,
            'vegetative' => 7,
            'flowering' => 10,
            'fruiting' => 12,
            'mature' => 8,
            default => 7,
        };

        /*
         * Hot weather increases water demand.
         */
        if ($temperature >= 35) {
            $amount += 5;
        } elseif ($temperature >= 32) {
            $amount += 3;
        }

        /*
         * Moderate rain reduces watering.
         */
        if ($rainfall > 0) {
            $amount = max(0, $amount - (int) round($rainfall));
        }

        if ($amount <= 0) {
            return [
                'required' => false,
                'recommended_amount_mm' => 0,
                'timing' => 'Wait',
                'reason' => 'Recent rainfall has reduced the crop water requirement.',
            ];
        }

        return [
            'required' => true,
            'recommended_amount_mm' => $amount,
            'timing' => $temperature >= 32
                ? 'Early morning or late afternoon'
                : 'Early morning',
            'reason' => $this->wateringReason(
                $temperature,
                $rainfall,
                $rainProbability,
                $growthStage
            ),
        ];
    }

    private function wateringReason(
        float $temperature,
        float $rainfall,
        float $rainProbability,
        string $growthStage
    ): string {
        if ($temperature >= 35) {
            return "Hot weather and the {$growthStage} growth stage increase the crop's water demand.";
        }

        if ($temperature >= 32) {
            return "Warm weather combined with the {$growthStage} growth stage requires additional moisture.";
        }

        if ($rainfall > 0) {
            return "Some rainfall has occurred, so watering has been reduced.";
        }

        return "The crop is in the {$growthStage} stage and current weather conditions indicate that watering is needed.";
    }

    /*
    |--------------------------------------------------------------------------
    | HARVEST
    |--------------------------------------------------------------------------
    */

    private function calculateHarvest(
    Crop $crop,
    ?Carbon $plantingDate,
    ?int $daysAfterPlanting,
    string $growthStage,
): array {
    /*
     * If the crop already has an expected harvest date,
     * use the farmer's actual crop data first.
     */
    if ($crop->expected_harvest_date) {
        $estimatedDate = Carbon::parse(
            $crop->expected_harvest_date
        );

        $daysRemaining = max(
            0,
            now()->startOfDay()->diffInDays(
                $estimatedDate->copy()->startOfDay(),
                false
            )
        );

        return [
            'available' => true,
            'estimated_date' => $estimatedDate->toDateString(),
            'days_remaining' => $daysRemaining,
            'growth_stage' => $growthStage,
            'harvest_days' => $plantingDate
                ? $plantingDate->diffInDays(
                    $estimatedDate
                )
                : null,
            'source' => 'crop_expected_harvest_date',
        ];
    }

    /*
     * Without planting date we cannot calculate
     * a reliable harvest estimate.
     */
    if (
        !$plantingDate ||
        $daysAfterPlanting === null
    ) {
        return [
            'available' => false,
            'estimated_date' => null,
            'days_remaining' => null,
            'growth_stage' => $growthStage,
            'harvest_days' => null,
            'source' => 'insufficient_crop_data',
        ];
    }

    /*
     * Use harvest_days when available.
     */
    $harvestDays = $crop->harvest_days;

    /*
     * Fallback based on growth stage.
     */
    if ($harvestDays === null) {
        $harvestDays = match ($growthStage) {
            'seedling' => 90,
            'vegetative' => 70,
            'flowering' => 45,
            'fruiting' => 25,
            'mature' => 0,
            default => 60,
        };
    }

    $harvestDays = (int) $harvestDays;

    $daysRemaining = max(
        0,
        $harvestDays - $daysAfterPlanting
    );

    $estimatedDate = $plantingDate
        ->copy()
        ->addDays($harvestDays);

    return [
        'available' => true,
        'estimated_date' => $estimatedDate->toDateString(),
        'days_remaining' => $daysRemaining,
        'growth_stage' => $growthStage,
        'harvest_days' => $harvestDays,
        'source' => 'calculated_from_planting_date',
    ];
}
}