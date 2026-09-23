<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\Farm;
use Carbon\Carbon;

class WateringRecommendationService
{
    public function getRecommendation(
        int $userId
    ): array {
        $farm = Farm::query()
            ->where('user_id', $userId)
            ->first();

        if (!$farm) {
            throw new \RuntimeException(
                'You do not have a farm yet.'
            );
        }

        if (
            $farm->latitude === null ||
            $farm->longitude === null
        ) {
            throw new \RuntimeException(
                'Your farm does not have a valid location.'
            );
        }

        $weatherService = app(WeatherService::class);

        $weatherData = $weatherService->getFarmWeather(
            $userId
        );

        $weather = $weatherData['weather'] ?? [];

        $current = $weather['current'] ?? [];
        $daily = $weather['daily'] ?? [];

        $todayRainProbability =
            $daily['precipitation_probability_max'][0]
            ?? 0;

        $todayRain =
            $daily['rain_sum'][0]
            ?? $daily['precipitation_sum'][0]
            ?? 0;

        $temperature =
            $current['temperature_2m']
            ?? null;

        $crops = Crop::query()
            ->where('farm_id', $farm->id)
            ->with([
                'field',
                'wateringLogs' => function ($query) {
                    $query
                        ->latest('watering_date')
                        ->latest('id');
                },
            ])
            ->get();

        $recommendations = [];

        foreach ($crops as $crop) {
            $lastWatering = $crop
                ->wateringLogs
                ->first();

            $daysSinceWatering = null;

            if ($lastWatering) {
                $daysSinceWatering = Carbon::parse(
                    $lastWatering->watering_date
                )->diffInDays(
                    Carbon::today()
                );
            }

            $recommendation =
                $this->calculateRecommendation(
                    $daysSinceWatering,
                    $todayRainProbability,
                    $todayRain,
                    $crop->growth_stage
                );

            $recommendations[] = [
                'crop' => $crop,
                'last_watering_date' =>
                    $lastWatering?->watering_date,
                'days_since_watering' =>
                    $daysSinceWatering,
                'weather' => [
                    'rain_probability' =>
                        $todayRainProbability,
                    'rain_amount_mm' =>
                        $todayRain,
                    'temperature_c' =>
                        $temperature,
                ],
                'recommendation' =>
                    $recommendation,
            ];
        }

        return [
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->farm_name,
                'latitude' => $farm->latitude,
                'longitude' => $farm->longitude,
            ],
            'recommendations' => $recommendations,
        ];
    }

    private function calculateRecommendation(
        ?int $daysSinceWatering,
        float|int $rainProbability,
        float|int $rainAmount,
        ?string $growthStage
    ): array {
        // --------------------------------------------------------
        // Rain is expected
        // --------------------------------------------------------

        if (
            $rainProbability >= 60 &&
            $rainAmount >= 1
        ) {
            return [
                'status' => 'skip',
                'title' => 'Rain expected',
                'message' =>
                    'Rain is expected today, so additional watering may not be necessary.',
            ];
        }

        // --------------------------------------------------------
        // Already watered today
        // --------------------------------------------------------

        if (
            $daysSinceWatering !== null &&
            $daysSinceWatering === 0
        ) {
            return [
                'status' => 'done',
                'title' => 'Already watered',
                'message' =>
                    'This crop has already been watered today.',
            ];
        }

        // --------------------------------------------------------
        // No watering history
        // --------------------------------------------------------

        if ($daysSinceWatering === null) {
            return [
                'status' => 'check',
                'title' => 'Watering history needed',
                'message' =>
                    'No watering record was found. Check the crop and field before watering.',
            ];
        }

        // --------------------------------------------------------
        // More than 2 days
        // --------------------------------------------------------

        if ($daysSinceWatering >= 3) {
            return [
                'status' => 'recommended',
                'title' => 'Watering recommended',
                'message' =>
                    'This crop has not been watered for several days and significant rain is not expected.',
            ];
        }

        // --------------------------------------------------------
        // Growth stage consideration
        // --------------------------------------------------------

        if (
            $growthStage !== null &&
            in_array(
                strtolower($growthStage),
                [
                    'flowering',
                    'fruiting',
                    'reproductive',
                ],
                true
            ) &&
            $daysSinceWatering >= 2
        ) {
            return [
                'status' => 'recommended',
                'title' => 'Watering recommended',
                'message' =>
                    'The crop is in an active growth stage and has not been watered recently.',
            ];
        }

        // --------------------------------------------------------
        // Normal condition
        // --------------------------------------------------------

        return [
            'status' => 'monitor',
            'title' => 'Monitor crop',
            'message' =>
                'Recent watering was recorded. Monitor the crop and field conditions before watering again.',
        ];
    }
}