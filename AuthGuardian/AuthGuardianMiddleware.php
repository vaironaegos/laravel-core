<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\AuthGuardian;

use Closure;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (app('env') === 'testing') {
            Auth::setUser(new AuthGuardianUser(app('testingUserData')));
            return $next($request);
        }

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
                return response()->json(['error' => 'invalidToken'], 401);
            }

            $userInfo = json_decode((string)$response->getBody(), true);
            Auth::setUser(new AuthGuardianUser($userInfo['data']));

            $request->headers->set('X-User-Id', $userInfo['data']['id']);
            $request->headers->set('X-User-Name', $userInfo['data']['name']);
            $request->headers->set('X-User-Login', $userInfo['data']['login']);

            return $next($request);
        } catch (RequestException $e) {
            return response()->json(['error' => 'invalidToken', 'message' => $e->getMessage()], 401);
        } catch (ConnectException $e) {
            return response()->json([
                'error' => 'connectError',
                'message' => $e->getMessage(),
                'url' => $this->authGuardianUrl . '/users/identity'
            ], 500);
        }
    }
}
