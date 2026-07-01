<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Customer;

class AuthService
{
    /**
     * Resolve a user by email address or mobile phone number.
     *
     * Lookup order:
     *  1. Email  → users.email
     *  2. Phone  → users.mobile_number
     *  3. Phone  → customers.mobile_number (then return linked user)
     */
    public function resolveUserByLogin(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if (empty($identifier)) {
            return null;
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $identifier)->first();
        }

        // Try the User's own mobile_number first
        $user = User::where('mobile_number', $identifier)->first();
        if ($user) {
            return $user;
        }

        // Fall back to the Customer's mobile_number
        $customer = Customer::where('mobile_number', $identifier)->first();

        return $customer?->user;
    }

    /**
     * Check if the user has an admin role.
     */
    public function isAdmin(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function getWebRedirectRoute(User $user): string
    {
        if ($this->isAdmin($user)) {
            return route('admin.dashboard');
        }
        if ($user->hasRole('customer')) {
            return route('customer.dashboard');
        }
        return route('home');
    }

    /**
     * Get the redirect path string (often used for API metadata or redirection responses without route() helper resolving to full URL if needed, though route() works too).
     */
    public function getRedirectPath(User $user): string
    {
        if ($this->isAdmin($user)) {
            return '/admin/dashboard';
        }
        if ($user->hasRole('customer')) {
            return '/portal/dashboard';
        }
        return '/home';
    }

    /**
     * Build the standard API auth response array.
     */
    public function buildApiAuthResponse(User $user, string $token): array
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'is_admin' => $this->isAdmin($user),
            'redirect_to' => $this->getRedirectPath($user),
        ];
    }
}
