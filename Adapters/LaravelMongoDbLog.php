<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Adapters;

use App\Models\Log;
use Astrotech\Core\Base\Adapter\Contracts\LogSystem;
use Astrotech\Core\Base\Infra\Enum\LogLevelEnum;
use DateTimeImmutable;

final class LaravelMongoDbLog implements LogSystem
{
    private string $defaultCategory = 'default';

    public function debug(string $message, array $options = []): void
    {
        $category = $options['category'] ?? $this->defaultCategory;
        $this->persistLog($category, LogLevelEnum::DEBUG, $message, $options['extraData'] ?? []);
    }

    public function trace(string $message, array $options = []): void
    {
        $category = $options['category'] ?? $this->defaultCategory;
        $this->persistLog($category, LogLevelEnum::TRACE, $message, $options['extraData'] ?? []);
    }

    public function info(string $message, array $options = []): void
    {
        $category = $options['category'] ?? $this->defaultCategory;
        $this->persistLog($category, LogLevelEnum::INFO, $message, $options['extraData'] ?? []);
    }

    public function warning(string $message, array $options = []): void
    {
        $category = $options['category'] ?? $this->defaultCategory;
        $this->persistLog($category, LogLevelEnum::WARNING, $message, $options['extraData'] ?? []);
    }

    public function error(string $message, array $options = []): void
    {
        $category = $options['category'] ?? $this->defaultCategory;
        $this->persistLog($category, LogLevelEnum::ERROR, $message, $options['extraData'] ?? []);
    }

    public function fatal(string $message, array $options = []): void
    {
        $category = $options['category'] ?? $this->defaultCategory;
        $this->persistLog($category, LogLevelEnum::FATAL, $message, $options['extraData'] ?? []);
    }

    private function persistLog(string $category, LogLevelEnum $level, string $message, array $extraData = []): void
    {
        $data = [
            'id' => uuid_create(),
            'ip' => getRealIp(),
            'category' => $category,
            'level' => $level->value,
            'createdAt' => (new DateTimeImmutable())->format(DATE_ATOM),
            'message' => $message,
            'extraData' => $extraData
        ];

        (new Log($data))->save();
    }
}
