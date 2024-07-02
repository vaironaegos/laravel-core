<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Astrotech\Core\Laravel\Http\HttpStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;

trait NewRead
{
    public function read(Request $request, string $id): JsonResponse
    {
        $modelName = $this->modelClassName();
        $query = $modelName::where('external_id', $id);
        $query->whereNull('deleted_at');

        /** @var Model $record */
        $record = $query->first();

        if (!$record) {
            return $this->answerFail(
                data: ['field' => 'id', 'error' => 'recordNotFound'],
                code: HttpStatus::NOT_FOUND
            );
        }

        return $this->answerSuccess($record->toArray());
    }
}
