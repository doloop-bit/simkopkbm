<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;

    protected string $model;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?? '';
        $this->model = config('services.gemini.model') ?? 'gemini-2.0-flash';
    }

    /**
     * Send a chat message to Gemini and get the response.
     *
     * @param  array  $messages  [{role: "user"|"model", parts: [{text: "..."}]}]
     */
    public function chat(array $messages, string $systemInstruction = ''): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API Key is missing.');

            return 'Error: Gemini API Key is not configured in .env';
        }

        // Prune messages to prevent hitting token limits (TPM)
        // Keep last 12 messages (approx 6 rounds of conversation)
        if (count($messages) > 12) {
            $messages = array_slice($messages, -12);
        }

        $payload = [
            'contents' => $messages,
        ];

        if (! empty($systemInstruction)) {
            $payload['system_instruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        // Configure generation config for better results
        $payload['generationConfig'] = [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 8192,
        ];

        try {
            $response = Http::retry(3, 2000, function ($exception, $request) {
                return $exception instanceof ConnectionException ||
                       ($exception instanceof RequestException && $exception->response->status() === 429);
            }, throw: false)
                ->throw()
                ->post($this->baseUrl.$this->model.':generateContent?key='.$this->apiKey, $payload);

            if ($response->failed()) {
                if ($response->status() === 429) {
                    Log::warning('Gemini API Rate Limit Hit', ['body' => $response->body()]);

                    return 'Error: Terlalu banyak permintaan (Rate Limit). Silakan tunggu sebentar dan coba lagi.';
                }

                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 'Error: '.($response->json('error.message') ?? 'Unknown API error');
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: '.$e->getMessage());

            return 'Error: Exception occurred while contacting Gemini API.';
        }
    }
}
