<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Astrotech\Core\Laravel\Http\HttpStatus;

trait Update
{
    public function update(Request $request, string $id): JsonResponse
    {
        $data = empty($this->requestFields) ? $request->all() : $request->only($this->requestFields);

        foreach ($this->requestFields as $fieldName) {
            if (!$request->hasFile($fieldName)) {
                continue;
            }
            $data[$fieldName] = $request->file($fieldName)->getContent();
        }

        if (!$data) {
            return $this->answerFail(['payload' => 'empty']);
        }

        if (isset($data['active'])) {
            $data['active'] = convertToBool($data['active']);
        }

        $modelName = $this->modelClassName();
        $query = $modelName::where('id', Uuid::fromString($id)->getBytes());

        /** @var Model $record */
        $record = $query->first();

        if (!$record) {
            return $this->answerFail(
                data: ['field' => 'id', 'error' => 'recordNotFound'],
                code: HttpStatus::NOT_FOUND
            );
        }

        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        $this->beforeFill($data);
        $record->fill($data);
        $this->beforeSave($record);
        $record->save();

        return $this->answerSuccess($record->toSoftArray());
    }
}
