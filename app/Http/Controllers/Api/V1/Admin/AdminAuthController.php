<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AdminLoginRequest;
use App\Services\Admin\AdminAuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    use ApiResponseTrait;

    protected AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Admin login endpoint.
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $payload = $this->authService->attemptAdminLogin($request->validated());

        return $this->successResponse('Admin authentication successful.', $payload);
    }

    /**
     * Get authenticated admin profile with permissions matrix.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authService->ensureAdminUserAllowed($user);

        $profile = $this->authService->getAdminProfile($user);

        return $this->successResponse('Admin profile retrieved successfully.', $profile);
    }

    /**
     * Refresh current JWT token.
     */
    public function refresh(): JsonResponse
    {
        $payload = $this->authService->refreshToken();

        return $this->successResponse('Token refreshed successfully.', $payload);
    }

    /**
     * Logout current admin.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->successResponse('Admin logout successful.', []);
    }
}
