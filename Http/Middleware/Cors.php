<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');

        if (config('app.env') === 'production') {
            $allowedOrigins = [$origin];

            if (config('app.cors.allowed_origins') !== null) {
                $allowedOrigins = config('app.cors.allowed_origins');
            }

            if (!$origin || !in_array($origin, $allowedOrigins)) {
                return response()->json(['error' => 'originNotAllowed', 'origin' => $origin], 403);
            }
        }

        // For non-production environments, use Origin header or wildcard
        if (!$origin) {
            $origin = '*';
        }

        $allowedHeaders = [
            "Ngrok-Skip-Browser-Warning",
            "Accept",
            "Authorization",
            "Cache-Control",
            "Content-Type",
            "DNT",
            "If-Modified-Since",
            "Keep-Alive",
            "Origin",
            "User-Agent",
            "X-Requested-With",
            "X-Csrf-Token",
            "X-Test-Session-Id",
            "Bearer",
            "Device",
            "Context",
        ];

        $exposedHeaders = [
            "Set-Cookie",
            "Ngrok-Skip-Browser-Warning",
            "Authorization",
            "Bearer",
            "Device",
        ];

        // Handle preflight OPTIONS requests immediately
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'PUT, PATCH, POST, DELETE, GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', implode(',', $allowedHeaders));
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');

            $response->headers->remove('Server');
            $response->headers->remove('X-Powered-By');

            return $response;
        }

        $response = $next($request);
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'PUT, PATCH, POST, DELETE, GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', implode(',', $allowedHeaders));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Expose-Headers', implode(',', $exposedHeaders));
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
