<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');

        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:3000',
            'http://localhost',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1',
        ];

        if (config('app.cors.allowed_origins') !== null) {
            $allowedOrigins = array_merge($allowedOrigins, config('app.cors.allowed_origins'));
        }

        if (!$origin) {
            return response()->json(['error' => 'undefinedOriginHeader'], 403);
        }

        if (!in_array($origin, $allowedOrigins)) {
            return response()->json(['error' => 'originNotAllowed', 'origin' => $origin], 403);
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
            "Bearer",
            "Device",
            "Context",
            "Context-Secondary",
            "PrivateToken",
            "Meta"
        ];

        $exposedHeaders = [
            "Set-Cookie",
            "Ngrok-Skip-Browser-Warning",
            "Authorization",
            "Bearer",
            "Device",
            "Context",
            "Context-Secondary",
            "PrivateToken",
            "Meta"
        ];

        $response = $next($request);

        if ($response instanceof StreamedResponse) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', "PUT, PATCH, HEADERS, POST, DELETE, GET, OPTIONS");
            $response->headers->set('Access-Control-Allow-Credentials', "true");
            $response->headers->set('Access-Control-Expose-Headers', implode(",", $exposedHeaders));
            return $response;
        }

        $response->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Methods', "PUT, PATCH, HEADERS, POST, DELETE, GET, OPTIONS")
            ->header('Access-Control-Allow-Headers', implode(",", $allowedHeaders))
            ->header('Access-Control-Expose-Headers', implode(",", $exposedHeaders))
            ->header('Access-Control-Allow-Credentials', "true");

        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(204);
        }

        return $response;
    }
}
