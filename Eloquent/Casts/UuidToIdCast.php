<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent\Casts;

use stdClass;
use Illuminate\Support\Facades\DB;
use Astrotech\Core\Base\Exception\ValidationException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class UuidToIdCast implements CastsAttributes
{
    public function __construct(private readonly string $relatedTableName)
    {
    }

    public function get($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value;
        }

        return $this->getRecord($key, $value)->id;
    }

    private function getRecord(string $key, string|int $value): stdClass
    {
        $record = DB::table($this->relatedTableName)
            ->select(['id'])
            ->where('external_id', $value)
            ->first();

        if (!$record) {
            throw new ValidationException([
                'field' => $key,
                'error' => 'relationRecordNotFound',
                'table' => $this->relatedTableName,
                'value' => $value
            ]);
        }

        return $record;
    }
}
