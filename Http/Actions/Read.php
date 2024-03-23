<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Astrotech\Core\Laravel\Base\Http\HttpStatus;

trait Read
{
    public function read(Request $request, string $id): JsonResponse
    {
        $modelName = $this->modelClassName();
        $query = $modelName::whereUuid($id);
        $query->whereNull('deleted_at');
        /** @var Model $record */
        $record = $query->first();

        if (!$record) {
            return $this->answerFail(['id' => 'recordNotFound'], [], HttpStatus::NOT_FOUND);
        }

        return $this->answerSuccess($record->toArray());
    }
}
