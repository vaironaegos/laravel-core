<?php

namespace Astrotech\Core\Laravel\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class RemoveSpecialCharacters implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return preg_replace('/[^a-zA-Z0-9\s]/', '', $value);
    }
}
