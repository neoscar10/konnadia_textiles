<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Customer;

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
     * Send OTP (Dummy implementation, logs code and returns status).
     */
    public function sendOtp(string $login): bool
    {
        $user = $this->resolveUser($login);
        if (!$user) {
            return false;
        }

        if (!$user->is_active) {
            return false;
        }

        // For dummy OTP, we do not need to persist anything or send SMS/Email.
        // We will just log that an OTP was requested.
        \Illuminate\Support\Facades\Log::info("Dummy OTP requested for user ID: {$user->id} (Identifier: {$login}). Any 6-digit code will pass.");

        return true;
    }

    /**
     * Verify OTP (Accepts any 6 digits).
     */
    public function verifyOtp(string $login, string $otp): bool
    {
        $user = $this->resolveUser($login);
        if (!$user) {
            return false;
        }

        if (!$user->is_active) {
            return false;
        }

        // Must be exactly 6 digits
        return (bool) preg_match('/^\d{6}$/', $otp);
    }
}
