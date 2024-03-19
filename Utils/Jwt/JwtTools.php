<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Utils\Jwt;

use DateTime;
use stdClass;
use Exception;
use DateInterval;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

final class JwtTools
{
    private string $algorithm = 'HS256';
    private string $secretKey;
    private int $expiration;
    private JwtPayload $payload;

    private function __construct(string $secretKey, array $options = [])
    {
        $this->secretKey = $secretKey;

        if (isset($options['algorithm'])) {
            $this->algorithm = $options['algorithm'];
        }

        if (isset($options['expiration'])) {
            $this->expiration = (int)$options['expiration'];

            $options['exp'] = (new DateTime())
                ->add(new DateInterval("PT{$this->expiration}S"))
                ->getTimestamp();
        }

        $this->payload = JwtPayload::build($options);
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @return JwtPayload
     */
    public function getPayload(): JwtPayload
    {
        return $this->payload;
    }

    /**
     * @param  string $secretKey
     * @param  array  $options
     * @return JWTTools
     */
    public static function build(string $secretKey, array $options = []): self
    {
        return new self($secretKey, $options);
    }

    public function getJWT(): string
    {
        try {
            return JWT::encode(
                $this->payload->getData(),
                $this->secretKey,
                $this->algorithm,
                $this->payload->get('sub')
            );
        } catch (ExpiredException $e) {
            throw new Exception('Authentication token is expired.');
        }
    }

    public function decodeToken(string $token): stdClass
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, $this->algorithm));
        } catch (ExpiredException $e) {
            throw new Exception('Authentication token is expired.');
        }
    }

    /**
     * @param  string $token
     * @return bool
     */
    public function signatureIsValid(string $token): bool
    {
        try {
            $this->decodeToken($token);
            return true;
        } catch (SignatureInvalidException $e) {
            return false;
        }
    }

    /**
     * @param string $token
     * @return bool
     */
    public function tokenIsExpired(string $token): bool
    {
        try {
            $this->decodeToken($token);
            return false;
        } catch (SignatureInvalidException | ExpiredException $e) {
            return true;
        }
    }
}
