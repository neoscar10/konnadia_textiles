<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            if (auth()->check()) {
                return app(\App\Services\Auth\AuthService::class)->getWebRedirectRoute(auth()->user());
            }
            return route('home');
        });

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'customer' => \App\Http\Middleware\CustomerMiddleware::class,
            'api.customer' => \App\Http\Middleware\ApiCustomerMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication token has expired.',
                    'errors' => new \stdClass()
                ], 401);
            }
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid authentication token.',
                    'errors' => new \stdClass()
                ], 401);
            }
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\JWTException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication token is missing.',
                    'errors' => new \stdClass()
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication token is missing.',
                    'errors' => new \stdClass()
                ], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e, $request) {
            if ($request->is('api/*')) {
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'expired')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication token has expired.',
                        'errors' => new \stdClass()
                    ], 401);
                }
                if (str_contains($msg, 'invalid') || str_contains($msg, 'fail') || str_contains($msg, 'signature') || str_contains($msg, 'blacklisted')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid authentication token.',
                        'errors' => new \stdClass()
                    ], 401);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication token is missing.',
                    'errors' => new \stdClass()
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
        });
    })->create();
