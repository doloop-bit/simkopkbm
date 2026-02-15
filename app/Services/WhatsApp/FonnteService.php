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
            // Updated Strategy: Upload file to public storage and send link
            // because Fonnte Free plan doesn't support direct file uploads efficiently.
            
            $path = 'whatsapp-files/' . date('Y-m-d') . '/' . $fileName;
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $fileContent);
            
            $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            
            // Format message with explicit spacing for link recognition
            $message = ($caption ? $caption . "\n\n" : "") . "Link Download:\n" . $url . "\n\n(Harap dibuka di browser)";

            return $this->sendMessage($target, $message);

        } catch (\Exception $e) {
            Log::error('Fonnte Send Document Error: ' . $e->getMessage());
            return false;
        }
    }
}
