<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
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

        $user = Auth::user();

        // 1. Check if the user account is active
        if (isset($user->is_active) && !$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account has been restricted/suspended. Please contact the super admin.');
        }

        /** @var AuthService $authService */
        $authService = app(AuthService::class);

        if (! $authService->isAdmin($user)) {
            return redirect()->route('home')->with('error', 'You do not have administrative access.');
        }

        // 2. Super admin bypass
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // 3. Page authorization checking based on route names
        $routeName = $request->route()->getName();

        if ($routeName) {
            // Admins management is strictly restricted to Super Admin
            if (str_starts_with($routeName, 'admin.admins.') || $routeName === 'admin.admins') {
                return response()->view('admin.unauthorized', [], 403);
            }

            // Define map of sidebar pages
            $routePermissionMap = [
                'admin.dashboard' => 'access dashboard',
                'admin.customers' => 'access customers',
                'admin.customer-levels' => 'access customer-levels',
                'admin.products' => 'access products',
                'admin.design-catalog' => 'access design-catalog',
                'admin.categories' => 'access categories',
                'admin.inventory' => 'access inventory',
                'admin.retail-shops' => 'access retail-shops',
                'admin.product-transfers' => 'access product-transfers',
                'admin.orders' => 'access orders',
                'admin.home-content' => 'access home-content',
                'admin.settings' => 'access settings',
            ];

            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            foreach ($routePermissionMap as $routePrefix => $permission) {
                if ($routeName === $routePrefix || str_starts_with($routeName, $routePrefix . '.')) {
                    if (!in_array($permission, $userPermissions)) {
                        return response()->view('admin.unauthorized', [], 403);
                    }
                    break;
                }
            }
        }

        return $next($request);
    }
}
