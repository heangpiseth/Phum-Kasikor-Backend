<?php

namespace App\Services;

use App\Models\Farm;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeatherService
{
    /**
     * Get weather for the authenticated farmer's farm.
     */
    public function getFarmWeather(int $userId): array
    {
        $farm = Farm::where('user_id', $userId)->first();

        if (!$farm) {
            throw new RuntimeException(
                'You do not have a farm yet.'
            );
        }

        if (
            $farm->latitude === null ||
            $farm->longitude === null
        ) {
            throw new RuntimeException(
                'Farm location is required for weather information.'
            );
        }

        $weather = $this->getForecast(
            (float) $farm->latitude,
            (float) $farm->longitude
        );

        return [
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->name,
                'latitude' => (float) $farm->latitude,
                'longitude' => (float) $farm->longitude,
            ],
            'weather' => $weather,
        ];
    }

    /**
     * Get real weather data from Open-Meteo.
     *
     * This is also used by:
     * Weather -> Crop -> Watering -> Harvest
     * 
     * 
     */

     public function getFarmWeatherForAi(int $userId): array
    {
        return $this->getFarmWeather($userId);
    }

    
    public function getForecast(
        float $latitude,
        float $longitude
    ): array {
        $response = Http::timeout(15)
            ->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $latitude,
                'longitude' => $longitude,

                'current' => implode(',', [
                    'temperature_2m',
                    'relative_humidity_2m',
                    'precipitation',
                    'rain',
                    'weather_code',
                ]),

                'hourly' => implode(',', [
                    'temperature_2m',
                    'precipitation_probability',
                    'precipitation',
                    'rain',
                ]),

                'daily' => implode(',', [
                    'temperature_2m_max',
                    'temperature_2m_min',
                    'precipitation_sum',
                    'rain_sum',
                    'precipitation_probability_max',
                ]),

                'timezone' => 'auto',

                'forecast_days' => 7,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Unable to retrieve weather data.'
            );
        }

        $data = $response->json();

        $current = $data['current'] ?? [];
        $hourly = $data['hourly'] ?? [];
        $daily = $data['daily'] ?? [];

        /*
         * Current temperature.
         */
        $temperature = (float) (
            $current['temperature_2m'] ?? 0
        );

        /*
         * Current precipitation.
         */
        $rainfall = (float) (
            $current['rain'] ??
            $current['precipitation'] ??
            0
        );

        /*
         * Find the current hourly rain probability.
         */
        $rainProbability = 0;

        if (
            isset($hourly['precipitation_probability']) &&
            is_array($hourly['precipitation_probability']) &&
            count($hourly['precipitation_probability']) > 0
        ) {
            $rainProbability = (float)
                ($hourly['precipitation_probability'][0] ?? 0);
        }

        return [
            /*
             * These simplified values are consumed by
             * WateringRecommendationService.
             */
            'temperature' => $temperature,

            'rainfall_mm' => $rainfall,

            'rain_probability' => $rainProbability,

            /*
             * Keep the real Open-Meteo data available
             * for the normal weather screen.
             */
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timezone' => $data['timezone'] ?? null,
            ],

            'current' => [
                'temperature' => $temperature,
                'humidity' => (float) (
                    $current['relative_humidity_2m'] ?? 0
                ),
                'rainfall_mm' => $rainfall,
                'rain_probability' => $rainProbability,
                'weather_code' => $current['weather_code'] ?? null,
            ],

            'daily' => [
                'time' => $daily['time'] ?? [],
                'temperature_max' =>
                    $daily['temperature_2m_max'] ?? [],
                'temperature_min' =>
                    $daily['temperature_2m_min'] ?? [],
                'precipitation_sum' =>
                    $daily['precipitation_sum'] ?? [],
                'rain_sum' =>
                    $daily['rain_sum'] ?? [],
                'rain_probability' =>
                    $daily['precipitation_probability_max'] ?? [],
            ],

            'hourly' => [
                'time' => $hourly['time'] ?? [],
                'temperature' =>
                    $hourly['temperature_2m'] ?? [],
                'rain_probability' =>
                    $hourly['precipitation_probability'] ?? [],
                'precipitation' =>
                    $hourly['precipitation'] ?? [],
                'rain' =>
                    $hourly['rain'] ?? [],
            ],
        ];
    }
}