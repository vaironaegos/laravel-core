<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Illuminate\Database\Eloquent\Builder;

trait DateIntervalWhere
{
    public function whereDatePeriod(
        Builder $query,
        string $column,
        string $startDate = null,
        string $endDate = null
    ): Builder {
        if (!is_null($startDate) && !is_null($endDate)) {
            $query->whereBetween($column, [$startDate, $endDate]);
            return $query;
        }

        if (!is_null($startDate)) {
            $query->where($column, '>=', $startDate);
            return $query;
        }

        if (!is_null($endDate)) {
            $query->where($column, '<=', $startDate);
            return $query;
        }

        return $query;
    }
}
