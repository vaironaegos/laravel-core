<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Adapters;

use Illuminate\Support\Facades\Log;
use Astrotech\Core\Base\Adapter\Contracts\LogSystem;

final class LaravelLog implements LogSystem
{
    public function debug(string $message, array $options = []): void
    {
        $channel = $options['channel'] ?? 'default';
        Log::channel($channel)->debug($message);
    }

    public function error(string $message, array $options = []): void
    {
        $channel = $options['channel'] ?? 'default';
        Log::channel($channel)->error($message);
    }

    public function warning(string $message, array $options = []): void
    {
        $channel = $options['channel'] ?? 'default';
        Log::channel($channel)->warning($message);
    }

    public function info(string $message, array $options = []): void
    {
        $channel = $options['channel'] ?? 'default';
        Log::channel($channel)->info($message);
    }

    public function trace(string $message, array $options = []): void
    {
        $channel = $options['channel'] ?? 'default';
        Log::channel($channel)->info($message);
    }

    public function fatal(string $message, array $options = []): void
    {
        $channel = $options['channel'] ?? 'default';
        Log::channel($channel)->critical($message);
    }
}
