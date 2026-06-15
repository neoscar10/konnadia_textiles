<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Http\Resources\Api\V1\AuthUserResource;
use App\Http\Resources\Api\V1\CustomerProfileResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MobileAuthService
{
    /**
     * Attempt to log in a user with dual email/mobile credentials.
     */
    public function attemptLogin(array $credentials): array
    {
        $login = $credentials['login'] ?? '';
        $password = $credentials['password'] ?? '';

        // 1. Resolve User
        $user = null;
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $login)->first();
        } else {
            // Find by user mobile_number
            $user = User::where('mobile_number', $login)->first();
            if (!$user) {
                // Find by linked customer mobile_number
                $customer = \App\Models\Customer::where('mobile_number', $login)->first();
                if ($customer) {
                    $user = $customer->user;
                }
            }
        }

        // 2. Verify existence and password
        if (!$user || !Hash::check($password, $user->password)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Invalid login credentials.',
                'errors' => new \stdClass()
            ], 401));
        }

        // 3. Ensure role and active status
        $this->ensureMobileUserAllowed($user);

        // 4. Generate JWT
        $token = auth('api')->login($user);
        if (!$token) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Could not generate authentication token.',
                'errors' => new \stdClass()
            ], 500));
        }

        return $this->buildAuthPayload($user, $token);
    }

    /**
     * Build the successful API authentication payload.
     */
    public function buildAuthPayload(User $user, string $token): array
    {
        $profile = $this->getAuthenticatedProfile($user);
        
        return array_merge([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], $profile);
    }

    /**
     * Get user, customer, and navigation structure.
     */
    public function getAuthenticatedProfile(User $user): array
    {
        $customer = $user->customer;

        return [
            'user' => new AuthUserResource($user),
            'customer' => $customer ? new CustomerProfileResource($customer) : null,
            'navigation' => [
                'default_screen' => 'dashboard',
                'can_access_products' => true,
                'can_place_orders' => true,
            ]
        ];
    }

    /**
     * Refresh the current JWT.
     */
    public function refreshToken(): array
    {
        $token = auth('api')->refresh();
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    /**
     * Log out current user.
     */
    public function logout(): void
    {
        auth('api')->logout();
    }

    /**
     * Update user password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors' => new \stdClass()
            ], 422));
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        // Invalidate current token
        try {
            auth('api')->logout();
        } catch (\Exception $e) {
            // Ignore logout failures during invalidation
        }
    }

    /**
     * Forgot password foundation logic.
     */
    public function sendForgotPasswordInstruction(string $identifier): array
    {
        $user = null;
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $identifier)->first();
        } else {
            $user = User::where('mobile_number', $identifier)->first();
            if (!$user) {
                $customer = \App\Models\Customer::where('mobile_number', $identifier)->first();
                if ($customer) {
                    $user = $customer->user;
                }
            }
        }

        if ($user && $user->hasRole('customer')) {
            Log::info("Forgot password requested for customer ID: {$user->id} (Identifier: {$identifier}). Sending instructions is pending configuration.");
        }

        return [
            'success' => true,
            'message' => 'If the account exists, password reset instructions will be sent.',
            'data' => null
        ];
    }

    /**
     * Strict checking of mobile login permissions.
     */
    public function ensureMobileUserAllowed(User $user): void
    {
        // 1. Inactive user blocking
        if (!$user->is_active) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
                'errors' => new \stdClass()
            ], 403));
        }

        // 2. Role restriction checks
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'This account is not allowed to access the mobile app.',
                'errors' => new \stdClass()
            ], 403));
        }

        if (!$user->hasRole('customer')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'This account is not allowed to access the mobile app.',
                'errors' => new \stdClass()
            ], 403));
        }

        // 3. Customer profile validation
        $customer = $user->customer;
        if (!$customer) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Customer profile not found for this account.',
                'errors' => new \stdClass()
            ], 403));
        }

        if (!$customer->is_active) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
                'errors' => new \stdClass()
            ], 403));
        }
    }
}
