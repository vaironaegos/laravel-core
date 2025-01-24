<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Astrotech\Core\Laravel\Utils\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Laravel\Eloquent\Limitable;
use Astrotech\Core\Laravel\Eloquent\Sorteable;
use Astrotech\Core\Laravel\Eloquent\Paginatable;
use Astrotech\Core\Laravel\Eloquent\NewSearcheable;

trait NewSearch
{
    use NewSearcheable;
    use Sorteable;
    use Limitable;
    use Paginatable;

    public function search(Request $request, string $method = 'toSoftArray', string $cacheAppend = ''): JsonResponse
    {
        $modelName = $this->modelClassName();
        $model = new $modelName();
        $cacheKey = $model->getTable() . '_search_' . $request->getQueryString() . $cacheAppend;

        if (method_exists($this, 'cacheKeyBase')) {
            $cacheKey = $this->cacheKeyBase() . '_search_' . $request->getQueryString() . $cacheAppend;
        }

        if (Cache::has($cacheKey)) {
            return Response::json(Cache::get($cacheKey));
        }

        /** @var Builder $query */
        $query = $modelName::query();

        if ($model->hasModelAttribute('deleted_at')) {
            $query->whereNull(['deleted_at', 'deleted_by']);
        }

        $this->modifySearchQuery($query);

        $this->processSearch($query, $request->get('filter', []));
        $this->processSort($query, $request->input('sort', ''));
        $this->buildPagination($query, (int)$request->input('perPage', 40), $method);

        $response = $this->answerSuccess($this->data, [
            'pagination' => $this->paginationData
        ]);

        Cache::put($cacheKey, json_decode($response->getContent(), true), 120);

        return $response;
    }

    protected function modifySearchQuery(Builder $query): void
    {
    }
}
