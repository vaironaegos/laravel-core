<?php

namespace Astrotech\Core\Laravel\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Ramsey\Uuid\Uuid;

class EfficientUuidCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (blank($value)) {
            return;
        }

        return isUuidString($value) ? $value : Uuid::fromBytes($value)->toString();
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (blank($value)) {
            return;
        }

        return [
            $key => isUuidString($value) ? Uuid::fromString(strtolower($value))->getBytes() : $value,
        ];
    }
}
