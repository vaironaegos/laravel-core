<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;
use Illuminate\Support\Facades\Cache;

trait NewOptions
{
    public function options(Request $request): JsonResponse
    {
        $value = $request->get('value', 'external_id');
        $label = $request->get('label');

        if (is_null($label)) {
            return $this->answerFail(['label' => 'required']);
        }

        /** @var NewModelBase $model */
        $modelName = $this->modelClassName();
        $model = new $modelName();
        $cacheKey = $model->getTable() . '_options';

        if (Cache::has($cacheKey)) {
            return $this->answerSuccess(Cache::get($cacheKey));
        }

        $query = $model->select([$value . ' as value', $label . ' as label'])
            ->orderBy($label, 'ASC');

        if ($model->hasModelAttribute('deleted_at')) {
            $query->whereNull('deleted_at')
                ->whereNull('deleted_at');
        }

        $this->modifyOptionsQuery($query);

        $response = $this->answerSuccess($query->get());
        Cache::put($cacheKey, $query->get(), 120);

        return $response;
    }

    protected function modifyOptionsQuery(Builder $query): void
    {
    }
}
