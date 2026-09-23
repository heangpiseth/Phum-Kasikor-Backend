<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Services\WateringRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WateringRecommendationController extends Controller
{
    public function index(
        Request $request,
        WateringRecommendationService $service
    ): JsonResponse {
        try {
            $data = $service->getRecommendation(
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to generate watering recommendation.',
            ], 503);
        }
    }
}