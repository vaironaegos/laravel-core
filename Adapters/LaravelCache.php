<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Adapters;

use Astrotech\Core\Base\Adapter\Contracts\CacheSystem;
use Astrotech\Core\Laravel\Utils\Cache;

final class LaravelCache implements CacheSystem
{
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    public function get(string $key): string
    {
        $value = Cache::get($key);

        if ($value === null) {
            return '';
        }

        return $value;
    }

    public function set(string $key, string $value, int $durationInSecs = null): void
    {
        if ($durationInSecs) {
            Cache::put($key, $value, $durationInSecs);
            return;
        }

        Cache::forever($key, $value);
    }

    public function destroy(string $key): void
    {
        if (str_contains('*', $key)) {
            Cache::delPattern($key);
            return;
        }

        Cache::forget($key);
    }

    public function clearAll(): void
    {
        Cache::flush();
    }
}
