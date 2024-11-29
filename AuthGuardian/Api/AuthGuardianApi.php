<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\AuthGuardian\Api;

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

    public function createUser(array $data): array
    {
        $response = $this->guzzleClient->post($this->baseUrl . '/users/with-password', [
            'json' => $data,
            'auth' => [$this->clientId, $this->clientSecret],
            'headers' => $this->headers
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function deleteUser(string $userId, string $token): array
    {
        $response = $this->guzzleClient->delete($this->baseUrl . '/users/' . $userId, [
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function destroyUser(string $userId, string $token): array
    {
        $response = $this->guzzleClient->delete($this->baseUrl . "/users/{$userId}/destroy", [
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function updateUser(string $userId, array $data, string $token): array
    {
        $response = $this->guzzleClient->put($this->baseUrl . "/users/{$userId}", [
            'json' => $data,
            'headers' => [
                ...$this->headers,
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
