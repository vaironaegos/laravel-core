<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Astrotech\Core\Laravel\Eloquent\ModelBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

trait Options
{
    public function options(Request $request): JsonResponse
    {
        $value = $request->get('value', 'id');
        $label = $request->get('label');

        if (is_null($label)) {
            return $this->answerFail(['label' => 'required']);
        }

        /** @var ModelBase $model */
        $modelName = $this->modelClassName();
        $model = new $modelName();

        $rows = DB::select(
            "select `{$value}` as `value`, `{$label}` as `label`
            from `{$model->getTable()}`
            order by `${label}` ASC"
        );

        $rows = array_map(function ($row) {
            $row->value = Uuid::fromBytes($row->value)->toString();
            return $row;
        }, $rows);

        return $this->answerSuccess($rows);
    }
}
