<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     * Accepts either an email address or a phone number as the 'identifier'.
     */
    public function store(Request $request, AuthService $authService)
    {
        $request->validate([
            'identifier' => ['required', 'string', 'min:3'],
            'password'   => ['required'],
        ]);

        $identifier = $request->input('identifier');
        $remember   = $request->boolean('remember');

        // Resolve user by email or mobile number
        $user = $authService->resolveUserByLogin($identifier);

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => 'Your account is inactive. Please contact support.',
            ]);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended($authService->getWebRedirectRoute($user));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
