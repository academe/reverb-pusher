<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('api.token');

        if (empty($configuredToken)) {
            return response()->json(['message' => 'API access is not configured.'], 401);
        }

        $providedToken = $request->bearerToken();

        if (! $providedToken || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Invalid API token.'], 401);
        }

        return $next($request);
    }
}
