<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WeatherController extends Controller
{
    public function index(
        Request $request,
        WeatherService $weatherService
    ): JsonResponse {
        try {
            $data = $weatherService->getFarmWeather(
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}