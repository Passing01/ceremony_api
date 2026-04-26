<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $instanceId;
    protected string $token;
    protected string $baseUrl;

    public function __construct()
    {
        $this->instanceId = config('services.ultramsg.instance_id', 'instance1234');
        $this->token = config('services.ultramsg.token', 'token1234');
        $this->baseUrl = "https://api.ultramsg.com/{$this->instanceId}";
    }

    /**
     * Envoi d'un message simple via UltraMsg
     */
    public function sendMessage(string $to, string $message)
    {
        $url = "{$this->baseUrl}/messages/chat";

        try {
            $response = Http::post($url, [
                'token' => $this->token,
                'to' => $this->formatPhoneNumber($to),
                'body' => $message,
                'priority' => 10
            ]);

            if ($response->successful()) {
                Log::info("Message WhatsApp envoyé avec succès à {$to}");
                return $response->json();
            }

            Log::error("Échec de l'envoi WhatsApp à {$to}", ['response' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'appel à UltraMsg : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Formater le numéro pour UltraMsg (doit inclure le code pays sans le +)
     */
    private function formatPhoneNumber(string $phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return $phone;
    }
}
