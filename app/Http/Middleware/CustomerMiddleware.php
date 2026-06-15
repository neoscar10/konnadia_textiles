<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        /** @var AuthService $authService */
        $authService = app(AuthService::class);

        // If they are admin/super_admin, redirect them to admin dashboard
        if ($authService->isAdmin(Auth::user())) {
            return redirect()->route('admin.dashboard');
        }

        // If they are active and have the customer role, let them through
        if (Auth::user()->hasRole('customer')) {
            return $next($request);
        }

        // Otherwise abort or log out
        Auth::logout();
        return redirect()->route('login')->with('error', 'Unauthorized access.');
    }
}
