<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiChatController extends Controller
{
    public function chat(
        Request $request,
        GeminiService $gemini,
        WeatherService $weatherService,
    ): JsonResponse {
        $data = $request->validate([
            'message' => [
                'required',
                'string',
                'max:4000',
            ],

            'history' => [
                'nullable',
                'array',
                'max:20',
            ],

            'history.*.role' => [
                'required',
                'string',
                'in:user,assistant',
            ],

            'history.*.text' => [
                'required',
                'string',
                'max:4000',
            ],
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | GET FARM WEATHER
            |--------------------------------------------------------------------------
            */

            $weather = $weatherService->getFarmWeatherForAi(
                $request->user()->id
            );

            /*
            |--------------------------------------------------------------------------
            | SEND MESSAGE + WEATHER TO GEMINI
            |--------------------------------------------------------------------------
            */

            $reply = $gemini->chat(
                $data['message'],
                $data['history'] ?? [],
                $weather,
            );

            return response()->json([
                'success' => true,
                'message' => $reply,
                'weather_available' => true,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'The agriculture assistant is temporarily unavailable. Please try again.',
            ], 503);
        }
    }
}