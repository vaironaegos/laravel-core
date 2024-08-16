<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Utils;

use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Cache as LaravelCache;

final class Cache extends LaravelCache
{
    public static function delPattern(string $pattern): void
    {
        /** @var RedisManager $redis */
        $redis = app()->make('redis');
        $redis->select(config('database.redis.cache.database'));
        $prefix = config('database.redis.options.prefix');
        $keys = $redis->keys($pattern);

        if (empty($keys)) {
            return;
        }

        foreach ($keys as $key) {
            if ($prefix && str_starts_with($key, $prefix)) {
                $key = substr($key, strlen($prefix));
            }

            $redis->del($key);
        }
    }
}
