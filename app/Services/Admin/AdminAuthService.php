<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Http\Resources\Api\V1\AdminUserResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class AdminAuthService
{
    /**
     * Attempt admin login via email or mobile number.
     */
    public function attemptAdminLogin(array $credentials): array
    {
        $login = trim($credentials['login'] ?? '');
        $password = $credentials['password'] ?? '';

        // 1. Resolve User
        $user = null;
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $login)->first();
        } else {
            $user = User::where('mobile_number', $login)->first();
        }

        // 2. Verify existence and password
        if (!$user || !Hash::check($password, $user->password)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Invalid login credentials.',
                'errors' => new \stdClass()
            ], 401));
        }

        // 3. Verify user active status & admin privileges
        $this->ensureAdminUserAllowed($user);

        // 4. Issue JWT Token
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
     * Build authentication response payload.
     */
    public function buildAuthPayload(User $user, string $token): array
    {
        return array_merge([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], $this->getAdminProfile($user));
    }

    /**
     * Get structured admin profile with roles and permission matrix.
     */
    public function getAdminProfile(User $user): array
    {
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        $isSuperAdmin = $user->hasRole('super_admin');

        return [
            'admin' => new AdminUserResource($user),
            'roles' => $user->getRoleNames(),
            'permissions' => $permissions,
            'access_matrix' => [
                'is_super_admin' => $isSuperAdmin,
                'can_access_dashboard' => $isSuperAdmin || in_array('access dashboard', $permissions),
                'can_access_customers' => $isSuperAdmin || in_array('access customers', $permissions),
                'can_access_products' => $isSuperAdmin || in_array('access products', $permissions),
                'can_access_categories' => $isSuperAdmin || in_array('access categories', $permissions),
                'can_access_inventory' => $isSuperAdmin || in_array('access inventory', $permissions),
                'can_access_retail_shops' => $isSuperAdmin || in_array('access retail-shops', $permissions),
                'can_access_product_transfers' => $isSuperAdmin || in_array('access product-transfers', $permissions),
                'can_access_orders' => $isSuperAdmin || in_array('access orders', $permissions),
                'can_access_settings' => $isSuperAdmin || in_array('access settings', $permissions),
                'can_manage_admins' => $isSuperAdmin,
                'can_manage_labor' => $isSuperAdmin || $user->can('manage_labor') || $user->hasRole('Factory Supervisor'),
            ]
        ];
    }

    /**
     * Refresh current admin JWT token.
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
     * Logout current admin.
     */
    public function logout(): void
    {
        auth('api')->logout();
    }

    /**
     * Verify active status and admin access privileges.
     */
    public function ensureAdminUserAllowed(User $user): void
    {
        if (!$user->is_active) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Your admin account has been deactivated. Please contact support.',
                'errors' => new \stdClass()
            ], 403));
        }

        // Must have super_admin, admin, Factory Supervisor or any staff permission
        $hasAdminRole = $user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('Factory Supervisor');
        $hasAnyPermission = $user->permissions()->count() > 0;

        if (!$hasAdminRole && !$hasAnyPermission) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Access denied. Account does not have admin access permissions.',
                'errors' => new \stdClass()
            ], 403));
        }
    }
}
