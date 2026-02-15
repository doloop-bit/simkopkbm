<?php

namespace App\Services\WhatsApp;

interface WhatsAppService
{
    /**
     * Send a text message to a specific number.
     *
     * @param string $target The phone number (e.g., 08123456789)
     * @param string $message The message content
     * @return mixed
     */
    public function sendMessage(string $target, string $message);

    /**
     * Send a document (PDF/Image) to a specific number.
     *
     * @param string $target The phone number
     * @param string $fileContent The raw file content
     * @param string $fileName The filename (e.g., rab.pdf)
     * @param string|null $caption Optional caption
     * @return mixed
     */
    public function sendDocument(string $target, string $fileContent, string $fileName, ?string $caption = null);
}
