<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent\Casts;

use Astrotech\Core\Base\Exception\ValidationException;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class UuidToIdCast implements CastsAttributes
{
    public function __construct(private readonly string $modelName)
    {
    }

    public function get($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (is_numeric($value)) {
            return $value;
        }

        return $this->getRecord($value)->id;
    }

    private function getRecord(string|int $value): ?NewModelBase
    {
        $instance = new $this->modelName();
        $record = $instance
            ->select(['id'])
            ->where('external_id', $value)
            ->first();

        if (!$record) {
            throw new ValidationException([
                'field' => 'id',
                'error' => 'relationRecordNotFound',
                'table' => $instance->getTable(),
                'value' => $value
            ]);
        }

        return $record;
    }
}
