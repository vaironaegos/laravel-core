<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Astrotech\Core\Laravel\Auth\AuthGuardianUser;

final class AuthGuardianMiddleware
{
    protected string $authGuardianUrl;
    protected GuzzleClient $guzzleClient;

    public function __construct()
    {
        $this->authGuardianUrl = config('services.authGuardian.baseUrl');
        $this->guzzleClient = new GuzzleClient();
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            $response = $this->guzzleClient->get($this->authGuardianUrl . '/users/identity', [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            $userInfo = json_decode((string)$response->getBody(), true);
            Auth::setUser(new AuthGuardianUser($userInfo['data']));

            return $next($request);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }
}
