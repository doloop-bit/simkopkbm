<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService implements WhatsAppService
{
    protected string $token;
    protected string $baseUrl = 'https://api.fonnte.com';

    public function __construct()
    {
        $this->token = config('services.fonnte.token', env('FONNTE_TOKEN', ''));
    }

    public function sendMessage(string $target, string $message)
    {
        if (empty($this->token)) {
            Log::warning('Fonnte Token is missing.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post("{$this->baseUrl}/send", [
                'target' => $target,
                'message' => $message,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Fonnte Send Message Error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendDocument(string $target, string $fileContent, string $fileName, ?string $caption = null)
    {
        if (empty($this->token)) {
            Log::warning('Fonnte Token is missing.');
            return false;
        }

        try {
            // Fonnte recommends creating a temporary URL if possible, or multipart upload.
            // For simplicity with generated content, multipart is best.
            
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->attach(
                'file', $fileContent, $fileName
            )->post("{$this->baseUrl}/send", [
                'target' => $target,
                'message' => $caption ?? $fileName,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Fonnte Send Document Error: ' . $e->getMessage());
            return false;
        }
    }
}
