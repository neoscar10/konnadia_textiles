<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Get a JWT via given credentials.
     * Accepts either an email address or a phone number in the 'login' field.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string|max:150',
            'password' => 'required|string',
        ]);

        // Resolve user by email or mobile number
        $user = $this->authService->resolveUserByLogin($request->input('login'));

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return $this->errorResponse('Invalid login credentials.', [], 401);
        }

        $token = auth('api')->login($user);

        if (!$token) {
            return $this->errorResponse('Could not generate authentication token.', [], 500);
        }

        return $this->successResponse(
            'Login successful.',
            $this->authService->buildApiAuthResponse($user, $token)
        );
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth('api')->user();
        return $this->successResponse('User retrieved successfully.', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'is_admin' => $this->authService->isAdmin($user),
            'redirect_to' => $this->authService->getRedirectPath($user),
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return $this->successResponse('Successfully logged out.');
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $token = auth('api')->refresh();
        $user = auth('api')->user();

        return $this->successResponse(
            'Token refreshed successfully.',
            $this->authService->buildApiAuthResponse($user, $token)
        );
    }
}
