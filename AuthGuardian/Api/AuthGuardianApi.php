<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\AuthGuardian\Api;

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

    public function updateUser(string $userId, array $data, string $token, string $origin): array
    {
        return $this->executeRequest('PUT', $this->baseUrl . "/users/{$userId}", [
            'json' => $data,
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
                'Origin' => $origin,
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
}
