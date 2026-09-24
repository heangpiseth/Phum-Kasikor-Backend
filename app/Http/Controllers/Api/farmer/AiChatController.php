<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Services\AgricultureAiService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiChatController extends Controller
{
    public function chat(
        Request $request,
        AgricultureAiService $ai,
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
            | SEND MESSAGE TO AGRICULTURE AI
            |--------------------------------------------------------------------------
            |
            | AgricultureAiService:
            |
            | Gemini → retry → Groq → safe fallback
            |
            */

            $reply = $ai->chat(
                $data['message'],
                $data['history'] ?? [],
                $weather,
            );

            return response()->json([
                'success' => true,
                'message' => $reply,
                'weather_available' => $weather !== null,
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