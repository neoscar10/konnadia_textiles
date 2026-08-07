<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtractBearerTokenFromQuery
{
    /**
     * Handle an incoming request.
     * If no Authorization header is present, check query parameters for bearer token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->headers->has('Authorization')) {
            $token = $request->query('token')
                ?? $request->query('api_token')
                ?? $request->query('bearer')
                ?? $request->query('access_token');

            if (!empty($token) && is_string($token)) {
                $request->headers->set('Authorization', 'Bearer ' . trim($token));
            }
        }

        return $next($request);
    }
}
