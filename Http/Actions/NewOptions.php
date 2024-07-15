<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;

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

        $query = $model->select([$value . ' as value', $label . ' as label'])
            ->orderBy($label, 'ASC');

        if ($model->hasModelAttribute('deleted_at')) {
            $query->whereNull('deleted_at')
                ->whereNull('deleted_at');
        }

        $this->modifyOptionsQuery($query);

        return $this->answerSuccess($query->get());
    }

    protected function modifyOptionsQuery(Builder $query): void
    {
    }
}
