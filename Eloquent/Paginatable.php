<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Illuminate\Database\Eloquent\Builder;

trait Paginatable
{
    protected array $data;
    protected array $paginationData;

    public function buildPagination(Builder $query, int $perPage = 40, string $method = 'toSoftArray'): void
    {
        $request = $request ?? request();

        if ($request->query('skipPagination') || $request->query('limit')) {
            $query->get()->each(fn ($row) => $this->data[] = $row->$method());
            $this->paginationData = [];
            if (empty($this->data)) {
                $this->data = [];
            }
            return;
        }

        $count = $query->count();
        $paginate = $query->paginate($perPage);
        $this->data = array_map(function ($data) use ($method) {
            return $data->$method();
        }, $paginate->items());

        $this->paginationData = [
            'current' => $paginate->currentPage(),
            'perPage' => $paginate->perPage(),
            'pagesCount' => ceil($count / $paginate->perPage()),
            'recordsCount' => $paginate->count(),
            'count' => $count
        ];
    }
}
