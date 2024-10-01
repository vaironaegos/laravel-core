<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Utils;

use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Cache as LaravelCache;
use Illuminate\Support\Facades\Redis;

final class Cache extends LaravelCache
{
    public static function delPattern(string $pattern): void
    {
        /** @var RedisManager $redis */
        $redis = app()->make('redis');
        $redis->select(config('database.redis.cache.database'));
        Redis::connection('cache')->select(1);
        $keys = Redis::connection('cache')->command('KEYS', ["*"]);

        if (empty($keys)) {
            return;
        }

        $prefix = config('database.redis.cache.prefix');
        $fullPattern = $prefix . $pattern;

        foreach ($keys as $key) {
            $regexPattern = '/' . str_replace('*', '.*', preg_quote($fullPattern, '/')) . '/';
            if (!preg_match($regexPattern, $key)) {
                continue;
            }
            $key = substr($key, strlen($prefix));
            Redis::connection('cache')->select(1);
            Redis::connection('cache')->del($key);
        }
    }
}
