<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class GeminiService
{
    protected string $model;

    public function __construct()
    {
        $this->model = config('services.gemini.model') ?? 'gemini-2.0-flash';
    }

    /**
     * Send a chat message to Gemini and get the response.
     * Implements a fallback mechanism for high demand.
     */
    public function chat(array $messages, string $systemInstruction = ''): ?string
    {
        // Models to try in order of priority (Backup Scheme)
        $fallbackModels = [
            $this->model, // Primary from .env
            'gemini-2.0-flash',
            'gemini-2.5-flash',
            'gemini-1.5-flash', // Fallback
        ];

        // Remove duplicates and ensure primary is first
        $fallbackModels = array_values(array_unique($fallbackModels));

        foreach ($fallbackModels as $modelToTry) {
            $response = $this->attemptRequest($modelToTry, $messages, $systemInstruction);

            // If success, return response
            if (! str_starts_with($response, 'Error:')) {
                return $response;
            }

            // If it's a rate limit (429), try next model
            if (str_contains($response, '429') || str_contains($response, 'Terlalu banyak permintaan')) {
                Log::warning("Gemini model {$modelToTry} hit rate limit, trying fallback...");

                continue;
            }

            // For other errors (except transient ones), return immediately
            return $response;
        }

        return 'Error: Semua model AI sedang sibuk (High Demand). Silakan coba lagi dalam beberapa menit.';
    }

    /**
     * Attempt a single request to a specific model using Prism.
     */
    protected function attemptRequest(string $model, array $messages, string $systemInstruction = ''): string
    {
        // Prune messages to prevent hitting token limits
        if (count($messages) > 12) {
            $messages = array_slice($messages, -12);
        }

        $prismMessages = [];
        foreach ($messages as $msg) {
            $text = $msg['parts'][0]['text'] ?? '';
            if ($msg['role'] === 'user') {
                $prismMessages[] = new UserMessage($text);
            } else {
                $prismMessages[] = new AssistantMessage($text);
            }
        }

        try {
            $builder = Prism::text()
                ->using('gemini', $model)
                ->withMessages($prismMessages);

            if (! empty($systemInstruction)) {
                $builder->withSystemPrompt($systemInstruction);
            }

            $response = $builder->generate();

            if (empty($response->text)) {
                return 'Error: Respon AI kosong.';
            }

            return $response->text;

        } catch (\Exception $e) {
            Log::error("Gemini Service Exception ({$model}) via Prism: ".$e->getMessage());

            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, '429')) {
                return 'Error (429): Terlalu banyak permintaan.';
            }

            if (str_contains(strtolower($errorMessage), 'quota')) {
                return 'Error: Kuota API telah habis.';
            }

            return 'Error (Exception): '.$errorMessage;
        }
    }
}
