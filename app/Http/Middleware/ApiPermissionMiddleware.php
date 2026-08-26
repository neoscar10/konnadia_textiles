<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'errors' => new \stdClass()
            ], 401);
        }

        // Super Admin bypasses all individual permission checks
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check if user has explicit permission
        $hasPerm = false;
        try {
            $hasPerm = $user->hasPermissionTo($permission, 'api') || $user->hasPermissionTo($permission, 'web');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            $hasPerm = false;
        }

        if (!$hasPerm) {
            return response()->json([
                'success' => false,
                'message' => "Access denied. You do not have permission to access '{$permission}'.",
                'errors' => new \stdClass()
            ], 403);
        }

        return $next($request);
    }
}
