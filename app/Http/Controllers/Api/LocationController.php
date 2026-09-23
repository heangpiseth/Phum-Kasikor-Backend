<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Reverse geocode latitude/longitude using OpenStreetMap Nominatim.
     *
     * POST /api/location/reverse-geocode
     *
     * Body:
     * {
     *     "latitude": 11.5564,
     *     "longitude": 104.9282
     * }
     */
    public function reverseGeocode(Request $request)
    {
        $data = $request->validate([
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ]);

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'PhumKasikor/1.0 (Laravel reverse geocoding)',
                    'Accept' => 'application/json',
                ])
                ->get(
                    'https://nominatim.openstreetmap.org/reverse',
                    [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'format' => 'jsonv2',
                        'addressdetails' => 1,
                        'zoom' => 18,
                        'accept-language' => 'en',
                    ]
                );

            if (! $response->successful()) {
                Log::error('Nominatim Reverse Geocode HTTP Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to detect the address right now.',
                ], 502);
            }

            $result = $response->json();

            Log::info('Nominatim Reverse Geocode Response', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'display_name' => $result['display_name'] ?? null,
                'address' => $result['address'] ?? null,
            ]);

            if (empty($result) || empty($result['address'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No address was found for this location.',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ], 404);
            }

            $address = $result['address'];

            /*
             * Cambodia's OpenStreetMap administrative hierarchy
             * is not always identical for every location.
             *
             * Therefore we use several fallbacks.
             */

            // ---------------------------------------------------------
            // PROVINCE
            // ---------------------------------------------------------

            $province = $this->firstValue($address, [
                'state',
                'province',
                'region',
            ]);

            // ---------------------------------------------------------
            // DISTRICT
            // ---------------------------------------------------------

            $district = $this->firstValue($address, [
                'town',
                'city_district',
                'district',
                'county',
                'city',
            ]);

            // ---------------------------------------------------------
            // COMMUNE
            // ---------------------------------------------------------

            $commune = $this->firstValue($address, [
                'village',
                'municipality',
                'suburb',
                'quarter',
                'neighbourhood',
                'neighborhood',
            ]);

            /*
             * Phnom Penh can sometimes be represented differently
             * by OpenStreetMap, so if district is empty and we have
             * a city-level value, use it as district.
             */
            if (empty($district)) {
                $district = $this->firstValue($address, [
                    'city',
                    'town',
                ]);
            }

            /*
             * If commune is still empty, try more detailed fields.
             */
            if (empty($commune)) {
                $commune = $this->firstValue($address, [
                    'quarter',
                    'neighbourhood',
                    'neighborhood',
                    'suburb',
                ]);
            }

            // ---------------------------------------------------------
            // FORMATTED ADDRESS
            // ---------------------------------------------------------

            $formattedAddress = $result['display_name'] ?? null;

            /*
             * Remove unnecessary null values.
             */
            $province = $this->cleanValue($province);
            $district = $this->cleanValue($district);
            $commune = $this->cleanValue($commune);
            $formattedAddress = $this->cleanValue($formattedAddress);

            // ---------------------------------------------------------
            // RESPONSE
            // ---------------------------------------------------------

            return response()->json([
                'success' => true,

                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,

                    'province' => $province,
                    'district' => $district,
                    'commune' => $commune,

                    'formatted_address' => $formattedAddress,
                ],

                /*
                 * Keep the original OSM address available.
                 * This is useful for debugging if Cambodia's
                 * administrative hierarchy differs at a location.
                 */
                'address_details' => $address,
            ]);
        } catch (\Throwable $e) {
            Log::error('Reverse geocoding exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to detect the address right now.',
            ], 500);
        }
    }

    /**
     * Return the first non-empty value from an address array.
     */
    private function firstValue(array $address, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (
                isset($address[$key]) &&
                is_string($address[$key]) &&
                trim($address[$key]) !== ''
            ) {
                return trim($address[$key]);
            }
        }

        return null;
    }

    /**
     * Clean an optional string value.
     */
    private function cleanValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
