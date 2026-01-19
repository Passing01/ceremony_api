<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $token;
    protected string $phoneNumberId;

    public function __construct()
    {
        // En prod ces valeurs viendraient du config / .env
        $this->baseUrl = 'https://graph.facebook.com/v18.0';
        $this->token = config('services.whatsapp.token', 'placeholder_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', 'placeholder_id');
    }

    public function sendTemplateMessage(string $to, string $templateName, string $languageCode = 'fr', array $components = [])
    {
        // Integration stub for Meta Cloud API
        $url = "{$this->baseUrl}/{$this->phoneNumberId}/messages";

        // Simulate API call
        Log::info("Sending WhatsApp to {$to} using template {$templateName}", ['components' => $components]);

        /* 
        $response = Http::withToken($this->token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components
            ]
        ]);
        
        return $response->json();
        */
        
        return ['status' => 'success', 'message_id' => 'wamid.' . uniqid()];
    }
}
