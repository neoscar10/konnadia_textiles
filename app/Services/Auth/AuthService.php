<?php

namespace App\Services\Auth;

use App\Models\User;

class AuthService
{
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
