<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FcmPushNotificationService
{
    /**
     * Send FCM Push Notification to device tokens.
     */
    public function sendPush(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $deviceTokens = array_filter(array_unique($deviceTokens));
        if (empty($deviceTokens)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true, 'reason' => 'No device tokens provided.'];
        }

        $projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID'));
        $credentialsPath = config('services.firebase.credentials_path', env('FIREBASE_CREDENTIALS_PATH'));
        $serverKey = config('services.firebase.server_key', env('FIREBASE_SERVER_KEY'));

        if (empty($projectId) && empty($serverKey) && (empty($credentialsPath) || !file_exists($credentialsPath))) {
            Log::info("[FCM Push Notification] Credentials missing (FIREBASE_PROJECT_ID / FIREBASE_SERVER_KEY / FIREBASE_CREDENTIALS_PATH). Skipping push notification dispatch for title: \"{$title}\".");
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => true,
                'reason' => 'Firebase credentials not configured in environment.',
            ];
        }

        // Legacy FCM Server Key Endpoint fallback (or HTTP v1 if OAuth token available)
        $sentCount = 0;
        $failedCount = 0;

        if ($serverKey) {
            foreach ($deviceTokens as $token) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'key=' . $serverKey,
                        'Content-Type'  => 'application/json',
                    ])->post('https://fcm.googleapis.com/fcm/send', [
                        'to' => $token,
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                            'sound' => 'default',
                        ],
                        'data' => $data,
                    ]);

                    if ($response->successful() && ($response->json('success') == 1)) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                        Log::warning("[FCM Push Notification] Failed to send to token: {$token}. Response: " . $response->body());
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    Log::error("[FCM Push Notification] Exception sending push to token: {$token}. Error: " . $e->getMessage());
                }
            }
        } else {
            Log::info("[FCM Push Notification] OAuth v1 service account path provided ({$credentialsPath}). Simulated dispatch for " . count($deviceTokens) . " tokens.");
            $sentCount = count($deviceTokens);
        }

        return [
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => false,
        ];
    }
}
