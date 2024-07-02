<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Astrotech\Core\Laravel\Eloquent\NewModelBase;
use Astrotech\Core\Laravel\Http\HttpStatus;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait NewDelete
{
    public function delete(Request $request, string $id): JsonResponse
    {
        $modelName = $this->modelClassName();
        $query = $modelName::where('external_id', $id)
            ->whereNull('deleted_at')
            ->whereNull('deleted_by');

        /** @var NewModelBase $record */
        $record = $query->first();

        if (!$record) {
            return $this->answerFail(
                data: ['field' => 'id', 'error' => 'recordNotFound'],
                code: HttpStatus::NOT_FOUND
            );
        }

        $now = new DateTimeImmutable();
        $user = auth('api')->user();
        $data['deleted_at'] = $now->format('Y-m-d H:i:s');
        $data['deleted_by'] = "{$user->name} [$user->external_id]";

        $record->fill($data);
        $record->save();

        return $this->answerSuccess($record->toSoftArray());
    }
}
