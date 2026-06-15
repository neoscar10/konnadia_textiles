<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Services\Auth\MobileAuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAuthController extends Controller
{
    use ApiResponseTrait;

    protected MobileAuthService $authService;

    public function __construct(MobileAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Customer mobile login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->attemptLogin($request->validated());

        return $this->successResponse('Login successful.', $data);
    }

    /**
     * Get the authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Ensure user is still active and valid customer
        $this->authService->ensureMobileUserAllowed($user);

        $data = $this->authService->getAuthenticatedProfile($user);

        return $this->successResponse('Authenticated user retrieved successfully.', $data);
    }

    /**
     * Refresh current JWT.
     */
    public function refresh(Request $request): JsonResponse
    {
        $data = $this->authService->refreshToken();

        return $this->successResponse('Token refreshed successfully.', $data);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return $this->successResponse('Logout successful.', []);
    }

    /**
     * Change password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $this->authService->changePassword($user, $validated['current_password'], $validated['password']);

        return $this->successResponse('Password changed successfully. Please log in again.', []);
    }

    /**
     * Forgot password request foundation.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $response = $this->authService->sendForgotPasswordInstruction($validated['login']);

        return response()->json($response);
    }
}
