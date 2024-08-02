<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;

trait NewRead
{
    public function read(Request $request, string $id): JsonResponse
    {
        $modelName = $this->modelClassName();
        $query = $modelName::where('external_id', $id);
        $query->whereNull(['deleted_at', 'deleted_by']);
        $this->modifyReadQuery($query);

        /** @var NewModelBase $record */
        $record = $query->first();

        if (!$record || $record->isDeleted()) {
            return $this->answerFail(
                data: ['field' => 'id', 'error' => 'recordNotFound'],
                code: HttpStatus::NOT_FOUND
            );
        }

        return $this->answerSuccess($record->toArray());
    }

    protected function modifyReadQuery(Builder $query): void
    {
    }
}
