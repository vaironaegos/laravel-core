<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Laravel\Utils\KeyCaseConverter;

trait Sorteable
{
    use KeyCaseConverter;

    public function processSort(Builder $query, string $sort = ''): void
    {
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

    public function processSortData(array &$data, string $sort = ''): void
    {
        if (empty($sort)) {
            return;
        }

        $camelCaseData = $this->convert('camel', $data);

        $keys = explode(',', $sort);
        foreach ($keys as $key) {
            $isDesc = false;

            if (str_contains($key, '-')) {
                $key = explode('-', $key)[1];
                $isDesc = true;
            }

            $column = array_column($camelCaseData, $key);

            if (str_contains($key, '.')) {
                $nestedKeys = explode('.', $key);
                $column = array_map(function ($item) use ($nestedKeys) {
                    $value = $item;
                    foreach ($nestedKeys as $nestedKey) {
                        $value = $value[$nestedKey] ?? null;
                    }
                    return $value;
                }, $camelCaseData);
            }

            if (empty($column)) {
                continue;
            }

            array_multisort(
                $column,
                $isDesc ? SORT_DESC : SORT_ASC,
                $data
            );
        }
    }
}
