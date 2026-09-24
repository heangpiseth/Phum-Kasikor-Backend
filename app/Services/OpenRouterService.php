<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    public function chat(
        string $message,
        array $history = [],
        ?array $weather = null
    ): string {
        $apiKey = config('services.openrouter.api_key');
        $model = config(
            'services.openrouter.model',
            'openrouter/free'
        );

        if (!$apiKey) {
            throw new \RuntimeException(
                'OpenRouter API key is not configured.'
            );
        }

        $systemPrompt = $this->buildSystemPrompt(
            $weather
        );

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        foreach ($history as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? null;
            $content = $item['content'] ?? null;

            if (
                !in_array(
                    $role,
                    ['user', 'assistant'],
                    true
                )
            ) {
                continue;
            }

            if (
                !is_string($content) ||
                trim($content) === ''
            ) {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        $response = Http::withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => config(
                    'app.url',
                    'http://localhost'
                ),
                'X-Title' => 'Phum Kasikor',
            ])
            ->connectTimeout(10)
            ->timeout(120)
            ->post(
                'https://openrouter.ai/api/v1/chat/completions',
                [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.4,
                    'max_tokens' => 700,
                ]
            );

        if ($response->failed()) {
            Log::error(
                'OpenRouter request failed.',
                [
                    'status' =>
                    $response->status(),
                    'body' =>
                    $response->body(),
                ]
            );

            throw new \RuntimeException(
                'OpenRouter request failed: '
                    . $response->body()
            );
        }

        $data = $response->json();

        $answer =
            $data['choices'][0]['message']['content']
            ?? null;

        if (
            !is_string($answer) ||
            trim($answer) === ''
        ) {
            throw new \RuntimeException(
                'OpenRouter returned an empty response.'
            );
        }

        return trim($answer);
    }

    private function buildSystemPrompt(
        ?array $weather
    ): string {
        $weatherText = 'Weather data is unavailable.';

        if (is_array($weather)) {
            $weatherText = json_encode(
                $weather,
                JSON_UNESCAPED_UNICODE
            );
        }

        return <<<PROMPT
You are the Phum Kasikor Agriculture Assistant.

You are an agriculture assistant for Cambodian farmers.

Your job is to provide practical, clear and safe farming
advice.

IMPORTANT RULES:

1. If the farmer writes in Khmer, answer in Khmer.
2. If the farmer writes in English, answer in English.
3. Keep answers practical and easy for Cambodian farmers
   to understand.
4. Use the farmer's real weather information when it is
   relevant.
5. Do not invent weather information.
6. Give advice about crops, planting, irrigation,
   fertilizer, pests, diseases, harvesting and farm
   management.
7. If you are uncertain, clearly say so instead of
   inventing information.
8. Do not claim that you physically inspected the farm.
9. Consider Cambodian farming conditions when giving
   recommendations.
10. Keep responses concise unless the farmer asks for
    detailed instructions.

CURRENT FARM WEATHER:

{$weatherText}
PROMPT;
    }
}
