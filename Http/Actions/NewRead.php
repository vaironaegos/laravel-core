<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;
use Astrotech\Core\Laravel\Utils\Cache;

trait NewRead
{
    public function read(Request $request, string $id): JsonResponse
    {
        /** @var NewModelBase $record */
        $modelName = $this->modelClassName();
        $record = new $modelName();
        $cacheKey = $record->getTable() . '_' . $id;

        if (Cache::has($cacheKey)) {
            $record->fill(Cache::get($cacheKey));
            return $this->answerSuccess($record->toArray());
        }

        $query = $modelName::where('external_id', $id);

        if ($record->hasModelAttribute('deleted_at')) {
            $query->whereNull(['deleted_at', 'deleted_by']);
        }

        $this->modifyReadQuery($query);

        /** @var NewModelBase $record */
        $record = $query->first();

        if (!$record || $record->isDeleted()) {
            return $this->answerFail(
                data: ['field' => 'id', 'error' => 'recordNotFound'],
                code: HttpStatus::NOT_FOUND
            );
        }

        Cache::put($record->getTable() . '_' . $id, $record->getAttributes());
        $this->afterRead($record);
        return $this->answerSuccess($record->toArray());
    }

    protected function modifyReadQuery(Builder $query): void
    {
    }

    protected function afterRead(NewModelBase $record): void
    {
    }
}
