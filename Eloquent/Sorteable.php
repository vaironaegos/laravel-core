<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait Sorteable
{
    public function processSort(Builder $query, ?Request $request = null): void
    {
        $request = $request ?? request();
        $sort = $request->query('sort');

        if (empty($sort)) {
            return;
        }

        $columns = explode(',', $sort);

        foreach ($columns as $column) {
            $columnName = Str::snake($column);
            if (str_contains($columnName, '-')) {
                $key = explode('-', $columnName);
                $query->orderBy($key[1], "DESC");
                continue;
            }
            $query->orderBy($columnName, "ASC");
        }
    }
}
