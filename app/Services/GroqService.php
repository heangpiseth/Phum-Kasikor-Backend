<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AgricultureAiService
{
    public function __construct(
        private GeminiService $gemini,
        private GroqService $groq
    ) {
    }

    /**
     * Main Farmer Agriculture AI.
     *
     * Priority:
     * 1. Gemini
     * 2. Groq fallback
     *
     * Both AI providers receive:
     * - the farmer's message
     * - conversation history
     * - real farm weather
     */
    public function chat(
        string $message,
        array $history = [],
        ?array $weather = null
    ): string {
        /*
        |--------------------------------------------------------------------------
        | 1. TRY GEMINI FIRST
        |--------------------------------------------------------------------------
        */

        try {
            $answer = $this->gemini->chat(
                $message,
                $history,
                $weather
            );

            Log::info('Agriculture AI answered with Gemini');

            return $answer;
        } catch (\Throwable $e) {
            Log::warning(
                'Gemini failed. Switching to Groq.',
                [
                    'message' => $e->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. GEMINI FAILED → TRY GROQ
        |--------------------------------------------------------------------------
        */

        try {
            $answer = $this->groq->chat(
                $message,
                $history,
                $weather
            );

            Log::info('Agriculture AI answered with Groq');

            return $answer;
        } catch (\Throwable $e) {
            Log::error(
                'Groq Agriculture AI failed.',
                [
                    'message' => $e->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. BOTH AI PROVIDERS FAILED
        |--------------------------------------------------------------------------
        */

        return
            'The agriculture assistant is temporarily unavailable. '
            . 'Please try again in a moment.';
    }
}