<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\AuthGuardian\Api;

use App\Shared\Service\OutputData;
use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleClient;

final class AuthGuardianApi
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly GuzzleClient $guzzleClient,
        private array $headers = []
    ) {
    }

    public function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function getGroupId(string $groupName, string $token, Request $request): array
    {
        $response = $this->executeRequest('GET', $this->baseUrl . '/groups', [
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
                'Origin' => $request->header('Origin'),
            ],
            'query' => [
                'skipPagination' => 1,
                'filter[name][like]' => $groupName,
            ],
        ]);


        if (!isset($response['data']) || count($response['data']) !== 1) {
            return ['error' => 'couldNotDefineGroupId'];
        }

        return ['id' => $response['data'][0]['id']];
    }

    private function executeRequest(string $method, string $url, array $options): array
    {
        $response = $this->guzzleClient->{$method}($url, $options);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function signIn(string $login, string $password, Request $request): array
    {
        return $this->executeRequest('POST', $this->baseUrl . '/oauth/token', [
            'json' => [
                'grantType' => 'client_credentials',
                'login' => $login,
                'password' => $password,
            ],
            'auth' => [$this->clientId, $this->clientSecret],
            'headers' => [
                ...$this->headers,
                'Origin' => $request->header('Origin'),
            ],
        ]);
    }

    public function createUser(array $data, Request $request): array
    {
        return $this->executeRequest('POST', $this->baseUrl . '/users/with-password', [
            'json' => $data,
            'auth' => [$this->clientId, $this->clientSecret],
            'headers' => [
                ...$this->headers,
                'Origin' => $request->header('Origin'),
            ],
        ]);
    }

    public function updateUser(string $userId, array $data, string $token, Request $request): array
    {
        return $this->executeRequest('PUT', $this->baseUrl . "/users/{$userId}", [
            'json' => $data,
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
                'Origin' => $request->header('Origin'),
            ]
        ]);
    }

    public function deleteUser(string $userId, string $token, Request $request): array
    {
        return $this->executeRequest('DELETE', $this->baseUrl . '/users/' . $userId, [
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
                'Origin' => $request->header('Origin'),
            ]
        ]);
    }

    public function destroyUser(string $userId, string $token, Request $request): array
    {
        return $this->executeRequest('DELETE', $this->baseUrl .
            "/users/{$userId}/destroy", [
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
                'Origin' => $request->header('Origin'),
            ]
        ]);
    }

    public function readUser(string $userId, string $token, string $origin): array
    {
        return $this->executeRequest('GET', $this->baseUrl . "/users/{$userId}", [
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
                'Origin' => $origin,
            ]
        ]);
    }

    public function checkLoginAvailable(string $login, string $token, string $origin): array
    {
        return $this->executeRequest('GET', $this->baseUrl . "/users/verify?login={$login}", [
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
                'Origin' => $origin,
            ]
        ]);
    }

    public function findUserByLogin(string $login, string $token, string $origin): array
    {
        return $this->executeRequest('GET', $this->baseUrl . "/users?filter[login]={$login}", [
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
                'Origin' => $origin,
            ]
        ]);
    }

    public function impersonate(string $login, string $token, Request $request): array
    {
        return $this->executeRequest('POST', $this->baseUrl . '/oauth/token', [
            'json' => [
                'grantType' => 'impersonate',
                'login' => $login,
            ],
            'headers' => [
                ...$this->headers,
                'Origin' => $request->header('Origin'),
                'Authorization' => "Bearer {$token}",
            ],
        ]);
    }

    public function requestResetPassword(string $login, Request $request, bool $ignoreMail = false): array
    {
        $url = $this->baseUrl . '/users/reset-password-request';
        if ($ignoreMail) {
            $url .= '?ignoreMail=1';
        }

        return $this->executeRequest('POST', $url, [
            'json' => [
                'login' => $login,
            ],
            'auth' => [$this->clientId, $this->clientSecret],
            'headers' => [
                ...$this->headers,
                'Origin' => $request->header('Origin'),
            ]
        ]);
    }

    public function changePassword(
        string $passwordResetToken,
        string $password,
        Request $request
    ): array {
        return $this->executeRequest('PUT', $this->baseUrl . '/users/change-password', [
           'json' => [
               'token' => $passwordResetToken,
               'password' => $password,
           ],
            'auth' => [$this->clientId, $this->clientSecret],
            'headers' => [
                ...$this->headers,
                'Origin' => $request->header('Origin'),
            ]
        ]);
    }

    public function getIdentity(string $token, Request $request): array
    {
        $this->addHeader('Authorization', "Bearer $token");
        $this->addHeader('Origin', $request->header('Origin'));

        return $this->executeRequest('GET', $this->baseUrl . "/users/identity", [
            'headers' => [
                ...$this->headers,
            ]
        ]);
    }
}
