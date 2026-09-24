<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AgricultureAiService
{
    public function __construct(
        private GeminiService $gemini
    ) {
    }

    public function chat(
        string $message,
        array $history = [],
        ?array $weather = null
    ): string {
        try {
            $answer = $this->gemini->chat(
                $message,
                $history,
                $weather
            );

            Log::info(
                'Agriculture AI answered with Gemini.'
            );

            return $answer;
        } catch (\Throwable $e) {
            Log::error(
                'Gemini Agriculture AI failed.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }
}