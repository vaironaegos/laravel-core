<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;

trait NewRead
{
    public function read(Request $request, string $id): JsonResponse
    {
        $modelName = $this->modelClassName();
        $query = $modelName::where('external_id', $id);

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
}
