<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Limitable
{
    public function processLimit(Builder $query, ?Request $request = null): void
    {
        $request = $request ?? request();
        $limit = $request->query('limit');

        if (empty($limit)) {
            return;
        }

        $query->limit($limit);
    }
}
