<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Astrotech\Core\Laravel\Eloquent\ModelBase;

trait NewOptions
{
    public function options(Request $request): JsonResponse
    {
        $value = $request->get('value', 'external_id');
        $label = $request->get('label');

        if (is_null($label)) {
            return $this->answerFail(['label' => 'required']);
        }

        /** @var ModelBase $model */
        $modelName = $this->modelClassName();
        $model = new $modelName();

        $rows = DB::select(
            "select {$value} as value, {$label} as label
            from {$model->getTable()}
            order by {$label} ASC"
        );

        return $this->answerSuccess($rows);
    }
}
