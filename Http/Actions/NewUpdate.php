<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Astrotech\Core\Laravel\Eloquent\Uploadable\InputData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;

trait NewUpdate
{
    public function update(Request $request, string $id): JsonResponse
    {
        $data = empty($this->requestFields) ? $request->all() : $request->only($this->requestFields);

        if (!$data) {
            return $this->answerFail(['error' => 'emptyPayload']);
        }

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

        foreach ($this->requestFields as $fieldName) {
            if (!$request->hasFile($fieldName)) {
                continue;
            }

            $this->processImage(new InputData(
                record: $record,
                field: $fieldName,
                file: $request->file($fieldName),
                path: $record->uploadPath,
                allowedExtensions: $record->allowedExtensions,
            ));

            unset($data[$fieldName]);
        }

        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        $this->beforeFill($data);
        $record->fill($data);
        $this->beforeSave($record);
        $record->save();
        $this->afterSave($record);

        return $this->answerSuccess($record->toSoftArray());
    }
}
