<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Astrotech\Core\Base\Exception\RuntimeException;
use Astrotech\Core\Laravel\Eloquent\Uploadable\InputData;

trait Create
{
    /**
     * @throws RuntimeException
     */
    public function create(Request $request): JsonResponse
    {
        $data = empty($this->requestFields) ? $request->all() : $request->only($this->requestFields);

        if (!$data) {
            return $this->answerFail(['error' => 'emptyPayload']);
        }

        if (isset($data['active'])) {
            $data['active'] = convertToBool($data['active']);
        }

        $modelName = $this->modelClassName();
        $record = new $modelName();

        foreach ($this->requestFields as $fieldName) {
            if (!$request->hasFile($fieldName)) {
                continue;
            }

            $this->processImage(new InputData(
                record: $record,
                field: $fieldName,
                file: $request->file($fieldName),
                path: $record->uploadPath
            ));

            unset($data[$fieldName]);
        }

        $this->beforeFill($data);
        $record->fill($data);
        $this->beforeSave($record);
        $record->save();
        $this->afterSave($record);

        return $this->answerSuccess($record->toSoftArray());
    }
}
