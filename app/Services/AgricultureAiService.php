<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AgricultureAiService
{
    public function __construct(
        private OpenRouterService $openRouter
    ) {
    }

    public function chat(
        string $message,
        array $history = [],
        ?array $weather = null
    ): string {
        try {
            $answer = $this->openRouter->chat(
                $message,
                $history,
                $weather
            );

            Log::info(
                'Agriculture AI answered with OpenRouter.'
            );

            return $answer;
        } catch (\Throwable $e) {
            Log::error(
                'OpenRouter Agriculture AI failed.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }
}