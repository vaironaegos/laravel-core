<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleClient;

final class AuthGuardianCheckPermissionMiddleware
{
    protected string $authGuardianUrl;
    protected GuzzleClient $guzzleClient;

    public function __construct()
    {
        $this->authGuardianUrl = config('services.authGuardian.baseUrl');
        $this->guzzleClient = new GuzzleClient();
    }

    public function handle(Request $request, Closure $next, string $key)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        $response = $this->guzzleClient->get($this->authGuardianUrl . '/users/has-permission?key=' . $key, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            return response()->json(['error' => 'permissionDenied'], 401);
        }

        $userInfo = json_decode((string)$response->getBody(), true);
        if (!$userInfo['data']['hasPermission']) {
            return response()->json(['error' => 'permissionDenied'], 403);
        }

        return $next($request);
    }
}
