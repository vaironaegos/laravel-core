<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Illuminate\Database\Eloquent\Builder;

trait Limitable
{
    public function processLimit(Builder $query, ?int $limit = null): void
    {
        if (empty($limit)) {
            return;
        }

        $query->limit($limit);
    }
}
