<?php

namespace App\Services;

use App\Models\Farm;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeatherService
{
    /**
     * Get the full weather data for the farmer's farm.
     */
    public function getFarmWeather(int $userId): array
    {
        $farm = Farm::where('user_id', $userId)->first();

        if (!$farm) {
            throw new RuntimeException(
                'No farm was found for this farmer.'
            );
        }

        if ($farm->latitude === null || $farm->longitude === null) {
            throw new RuntimeException(
                'Farm location has not been set yet.'
            );
        }

        $latitude = (float) $farm->latitude;
        $longitude = (float) $farm->longitude;

        $url = config('services.weather.url');

        $response = Http::timeout(15)->get($url, [
            'latitude' => $latitude,
            'longitude' => $longitude,

            'current' => implode(',', [
                'temperature_2m',
                'relative_humidity_2m',
                'precipitation',
                'rain',
                'weather_code',
                'wind_speed_10m',
                'is_day',
            ]),

            'hourly' => implode(',', [
                'temperature_2m',
                'rain',
                'precipitation',
                'precipitation_probability',
                'wind_speed_10m',
                'soil_temperature_0cm',
                'soil_moisture_0_to_1cm',
                'soil_moisture_1_to_3cm',
                'soil_moisture_3_to_9cm',
                'soil_moisture_9_to_27cm',
                'is_day',
            ]),

            'daily' => implode(',', [
                'weather_code',
                'temperature_2m_max',
                'temperature_2m_min',
                'precipitation_sum',
                'rain_sum',
                'precipitation_probability_max',
                'wind_speed_10m_max',
            ]),

            'timezone' => 'auto',
            'forecast_days' => 7,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Unable to retrieve weather data.'
            );
        }

        return [
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->farm_name,
                'location' => $farm->location,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],

            'weather' => $response->json(),
        ];
    }

    /**
     * Get a compact weather summary for the AI assistant.
     */
    public function getFarmWeatherForAi(int $userId): array
    {
        $data = $this->getFarmWeather($userId);

        $weather = $data['weather'];

        return [
            'farm' => $data['farm'],

            'current' => [
                'time' => data_get(
                    $weather,
                    'current.time'
                ),

                'temperature_c' => data_get(
                    $weather,
                    'current.temperature_2m'
                ),

                'humidity_percent' => data_get(
                    $weather,
                    'current.relative_humidity_2m'
                ),

                'rain_mm' => data_get(
                    $weather,
                    'current.rain'
                ),

                'precipitation_mm' => data_get(
                    $weather,
                    'current.precipitation'
                ),

                'wind_kmh' => data_get(
                    $weather,
                    'current.wind_speed_10m'
                ),

                'weather_code' => data_get(
                    $weather,
                    'current.weather_code'
                ),
            ],

            'forecast' => [
                'time' => data_get(
                    $weather,
                    'daily.time',
                    []
                ),

                'temperature_max_c' => data_get(
                    $weather,
                    'daily.temperature_2m_max',
                    []
                ),

                'temperature_min_c' => data_get(
                    $weather,
                    'daily.temperature_2m_min',
                    []
                ),

                'rain_mm' => data_get(
                    $weather,
                    'daily.rain_sum',
                    []
                ),

                'precipitation_mm' => data_get(
                    $weather,
                    'daily.precipitation_sum',
                    []
                ),

                'rain_probability_percent' => data_get(
                    $weather,
                    'daily.precipitation_probability_max',
                    []
                ),

                'wind_max_kmh' => data_get(
                    $weather,
                    'daily.wind_speed_10m_max',
                    []
                ),
            ],
        ];
    }
}