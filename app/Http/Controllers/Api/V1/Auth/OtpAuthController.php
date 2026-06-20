<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OtpService;
use App\Services\Auth\MobileAuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class OtpAuthController extends Controller
{
    use ApiResponseTrait;

    protected OtpService $otpService;
    protected MobileAuthService $mobileAuthService;

    public function __construct(OtpService $otpService, MobileAuthService $mobileAuthService)
    {
        $this->otpService = $otpService;
        $this->mobileAuthService = $mobileAuthService;
    }

    /**
     * Request a dummy OTP.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
        ]);

        $user = $this->otpService->resolveUser($validated['login']);

        if (!$user) {
            return $this->errorResponse('Account not found.', 404);
        }

        // Apply same checks as password login for API
        try {
            $this->mobileAuthService->ensureMobileUserAllowed($user);
        } catch (HttpResponseException $e) {
            // Re-throw or return JSON response
            return $e->getResponse();
        }

        $sent = $this->otpService->sendOtp($validated['login']);

        if (!$sent) {
            return $this->errorResponse('Failed to send OTP.', 400);
        }

        return $this->successResponse('OTP sent successfully. Any 6-digit code will pass.', [
            'login' => $validated['login'],
        ]);
    }

    /**
     * Verify OTP and login.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $this->otpService->resolveUser($validated['login']);

        if (!$user) {
            return $this->errorResponse('Account not found.', 404);
        }

        // Apply same checks as password login for API
        try {
            $this->mobileAuthService->ensureMobileUserAllowed($user);
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }

        if (!$this->otpService->verifyOtp($validated['login'], $validated['otp'])) {
            return $this->errorResponse('Invalid OTP code. Please enter any 6 digits.', 401);
        }

        // Generate JWT
        $token = auth('api')->login($user);
        if (!$token) {
            return $this->errorResponse('Could not generate authentication token.', 500);
        }

        $data = $this->mobileAuthService->buildAuthPayload($user, $token);

        return $this->successResponse('Login successful.', $data);
    }
}
