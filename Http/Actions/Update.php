<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Astrotech\Core\Base\Infra\Http\HttpStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $query = $modelName::whereUuid($id);

        /** @var Model $record */
        $record = $query->first();

        if (!$record) {
            return $this->answerFail(['id' => 'recordNotFound'], [], HttpStatus::NOT_FOUND);
        }

        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        $this->beforeFill($data);
        $record->fill($data);
        $this->beforeSave($record);
        $record->save();

        return $this->answerSuccess($record->toArray());
    }
}
