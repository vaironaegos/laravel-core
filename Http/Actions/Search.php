<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Astrotech\Core\Laravel\Eloquent\Searcheable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Laravel\Eloquent\Limitable;
use Astrotech\Core\Laravel\Eloquent\Sorteable;
use Astrotech\Core\Laravel\Eloquent\Paginatable;

trait Search
{
    use Searcheable;
    use Sorteable;
    use Limitable;
    use Paginatable;

    public function search(Request $request): JsonResponse
    {
        $modelName = $this->modelClassName();

        /** @var Builder $query */
        $query = $modelName::query();
        $query->whereNull('deleted_at');

        $this->processSearch($query, $request->get('filter', []));
        $this->processSort($query);
        $this->buildPagination($query);

        return $this->answerSuccess($this->data, [
            'pagination' => $this->paginationData
        ]);
    }
}
