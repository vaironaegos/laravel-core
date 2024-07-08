<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Auth;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $login
 * @property array $group
 * @property array $permissions
 */
final class AuthGuardianUser implements Authenticatable
{
    private array $userInfo;

    public function __construct(array $userInfo)
    {
        $this->userInfo = $userInfo;
    }

    public function __get(string $name): mixed
    {
        $name = ($name !== 'external_id' ? $name : 'id');

        if (!array_key_exists($name, $this->userInfo)) {
            throw new Exception("Invalid user field '{$name}'");
        }

        return $this->userInfo[$name];
    }

    public function getAttribute(string $name): mixed
    {
        return $this->{$name};
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->userInfo['id'];
    }

    public function getAuthPassword(): ?string
    {
        return null;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function getAuthPasswordName()
    {
    }
}
