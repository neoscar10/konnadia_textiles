<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Resolve user by email or mobile number (on user or customer model).
     */
    public function resolveUser(string $login): ?User
    {
        $login = trim($login);
        if (empty($login)) {
            return null;
        }

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $login)->first();
        }

        // Find by user mobile_number
        $user = User::where('mobile_number', $login)->first();
        if ($user) {
            return $user;
        }

        // Find by linked customer mobile_number
        $customer = Customer::where('mobile_number', $login)->first();
        if ($customer) {
            return $customer->user;
        }

        return null;
    }

    /**
     * Generate & Send WhatsApp OTP via Waty API.
     */
    public function sendOtp(string $login): bool
    {
        $user = $this->resolveUser($login);
        if (!$user || !$user->is_active) {
            return false;
        }

        // Generate 6-digit OTP code
        $otpCode = sprintf('%06d', random_int(0, 999999));

        // Store OTP in Cache for 10 minutes
        $cacheKey = $this->getCacheKey($user);
        Cache::put($cacheKey, $otpCode, now()->addMinutes(10));

        // Retrieve Waty API configuration
        $baseUrl = config('services.waty.base_url', env('WATY_API_BASE_URL', 'https://bizlawn.storesite.in/api'));
        $apiToken = config('services.waty.api_token', env('WATY_API_TOKEN'));
        $otpAccount = config('services.waty.otp_account', env('WATY_OTP_ACCOUNT', 'mobile_app'));
        $adminPhone = config('services.waty.admin_phone_number', env('WATY_ADMIN_PHONE_NUMBER', '+919911041964'));

        // Determine destination phone number
        $targetPhone = $user->mobile_number ?? $user->customer?->mobile_number;

        if (empty($targetPhone) && !filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $targetPhone = $login;
        }

        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($targetPhone);

        if (empty($apiToken)) {
            Log::info("[Waty OTP] API token not configured. Generated OTP for User ID {$user->id} ({$login}): {$otpCode}");
            return true;
        }

        if (empty($formattedPhone)) {
            Log::warning("[Waty OTP] No valid mobile number for User ID {$user->id}. Cannot send WhatsApp OTP.");
            return true;
        }

        try {
            $response = Http::acceptJson()
                ->post(rtrim($baseUrl, '/') . '/otp/send', [
                    'api_token'          => $apiToken,
                    'otp_account'        => $otpAccount,
                    'phone_number'       => $formattedPhone,
                    'otp_code'           => (string) $otpCode,
                    'admin_phone_number' => $adminPhone,
                ]);

            if ($response->successful() && $response->json('success') === true) {
                Log::info("[Waty OTP] Successfully sent WhatsApp OTP to {$formattedPhone} (Message ID: " . $response->json('message_id') . ").");
                return true;
            } else {
                Log::warning("[Waty OTP] Failed to send WhatsApp OTP to {$formattedPhone}: " . $response->body());
                return true;
            }
        } catch (\Throwable $e) {
            Log::error("[Waty OTP] Exception sending WhatsApp OTP to {$formattedPhone}: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Verify OTP code against cache.
     */
    public function verifyOtp(string $login, string $otp): bool
    {
        $user = $this->resolveUser($login);
        if (!$user || !$user->is_active) {
            return false;
        }

        if (!preg_match('/^\d{6}$/', $otp)) {
            return false;
        }

        $cacheKey = $this->getCacheKey($user);
        $cachedOtp = Cache::get($cacheKey);

        // Check matching cached OTP
        if ($cachedOtp && (string) $otp === (string) $cachedOtp) {
            Cache::forget($cacheKey);
            return true;
        }

        // Testing / Fallback Mode (allow 123456 in testing environment or when API token is empty for demo)
        $apiToken = config('services.waty.api_token', env('WATY_API_TOKEN'));
        if (app()->environment('testing') || (empty($apiToken) && in_array($otp, ['123456', '000000'], true))) {
            return true;
        }

        return false;
    }

    /**
     * Get OTP settings from Waty API (GET /otp/settings).
     */
    public function getOtpSettings(): array
    {
        $baseUrl = config('services.waty.base_url', env('WATY_API_BASE_URL', 'https://bizlawn.storesite.in/api'));
        $apiToken = config('services.waty.api_token', env('WATY_API_TOKEN'));
        $otpAccount = config('services.waty.otp_account', env('WATY_OTP_ACCOUNT', 'mobile_app'));

        if (empty($apiToken)) {
            return [
                'success' => false,
                'message' => 'API token not configured.',
            ];
        }

        try {
            $response = Http::acceptJson()->get(rtrim($baseUrl, '/') . '/otp/settings', [
                'api_token'   => $apiToken,
                'otp_account' => $otpAccount,
            ]);

            return $response->json() ?? ['success' => false, 'message' => 'Empty response from Waty API.'];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Exception fetching Waty OTP settings: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to clean international string.
     */
    public function formatPhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (empty($digits)) {
            return '';
        }

        // If 10 digits (Indian local format), prepend +91
        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }

        // If starts with 91 and length 12, prepend +
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+' . $digits;
        }

        return str_starts_with($phone, '+') ? $phone : '+' . $digits;
    }

    /**
     * Generate cache key for user OTP.
     */
    protected function getCacheKey(User $user): string
    {
        return 'otp_auth_' . $user->id;
    }
}
