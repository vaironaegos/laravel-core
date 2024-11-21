<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->header('Origin');
        $allowedOrigins = [$origin];

        if (config('app.cors.allowed_origins') !== null) {
            $allowedOrigins = config('app.cors.allowed_origins');
        }

        if (!$origin || !in_array($origin, $allowedOrigins)) {
            return response()->json(['error' => 'originNotAllowed'], 403);
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

        $response = $next($request)
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Methods', "PUT, PATCH, HEADERS, POST, DELETE, GET, OPTIONS")
            ->header('Access-Control-Allow-Headers', implode(",", $allowedHeaders))
            ->header('Access-Control-Allow-Credentials', "true")
            ->header('Access-Control-Expose-Headers', implode(",", $exposedHeaders));

        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(204);
        }

        return $response;
    }
}
