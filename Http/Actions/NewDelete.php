<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;
use Astrotech\Core\Laravel\AuthGuardian\AuthGuardianUser;

trait NewDelete
{
    public function delete(Request $request, string $id): JsonResponse
    {
        $modelName = $this->modelClassName();
        $query = $modelName::query()
            ->where('external_id', $id)
            ->whereNull('deleted_at')
            ->whereNull('deleted_by');

        $this->modifyDeleteQuery($query);

        /** @var NewModelBase $record */
        $record = $query->first();

        if (!$record) {
            return $this->answerFail(
                data: ['field' => 'id', 'error' => 'recordNotFound'],
                code: HttpStatus::NOT_FOUND
            );
        }

        $now = new DateTimeImmutable();
        /** @var AuthGuardianUser $user */
        $user = auth('api')->user();
        $username = $user->name ?? 'anonymous';
        $id = $user->external_id ? " [$user->external_id]" : '';
        $data['deleted_at'] = $now->format('Y-m-d H:i:s');
        $data['deleted_by'] = "{$username}{$id}";

        $record->fill($data);
        $this->beforeSave($record);
        $record->save();
        $this->afterSave($record);
        $this->afterSoftDelete($record);

        return $this->answerNoContent();
    }

    protected function modifyDeleteQuery(Builder $query): void
    {
    }
}
