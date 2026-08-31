<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WhatsAppNotificationService
{
    /**
     * Send Meta WhatsApp Cloud API Template Message.
     */
    public function sendTemplateMessage(
        string $phoneNumber,
        string $templateName,
        array $bodyParameters = [],
        string $languageCode = 'en_US'
    ): array {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (empty($phoneNumber)) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'Invalid phone number provided.'];
        }

        $apiToken = config('services.whatsapp.api_token', env('WHATSAPP_API_TOKEN'));
        $phoneNumberId = config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID'));

        if (empty($apiToken) || empty($phoneNumberId)) {
            Log::info("[WhatsApp Notification] Credentials missing (WHATSAPP_API_TOKEN / WHATSAPP_PHONE_NUMBER_ID). Skipping WhatsApp template notification \"{$templateName}\" to {$phoneNumber}.");
            return [
                'sent' => false,
                'skipped' => true,
                'reason' => 'WhatsApp Cloud API credentials not configured in environment.',
            ];
        }

        $components = [];
        if (!empty($bodyParameters)) {
            $params = [];
            foreach ($bodyParameters as $param) {
                $params[] = [
                    'type' => 'text',
                    'text' => (string) $param,
                ];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $params,
            ];
        }

        try {
            $url = "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages";
            $response = Http::withToken($apiToken)->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $phoneNumber,
                'type'              => 'template',
                'template'          => [
                    'name'       => $templateName,
                    'language'   => ['code' => $languageCode],
                    'components' => $components,
                ],
            ]);

            if ($response->successful()) {
                Log::info("[WhatsApp Notification] Template message \"{$templateName}\" successfully sent to {$phoneNumber}.");
                return ['sent' => true, 'skipped' => false, 'response' => $response->json()];
            } else {
                Log::warning("[WhatsApp Notification] Meta API returned failure for {$phoneNumber}: " . $response->body());
                return ['sent' => false, 'skipped' => false, 'error' => $response->body()];
            }
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Notification] Exception sending WhatsApp to {$phoneNumber}. Error: " . $e->getMessage());
            return ['sent' => false, 'skipped' => false, 'error' => $e->getMessage()];
        }
    }
}
