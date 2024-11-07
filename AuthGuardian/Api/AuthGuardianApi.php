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
        private readonly GuzzleClient $guzzleClient
    ) {
    }

    public function createUser(array $data): array
    {
        $response = $this->guzzleClient->post($this->baseUrl . '/users/with-password', [
            'json' => $data,
            'auth' => [$this->clientId, $this->clientSecret]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
