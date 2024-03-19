<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Actions;

use Astrotech\Core\Laravel\Eloquent\Uploadable\InputData;
use Astrotech\Core\Base\Exception\RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait Create
{
    /**
     * @throws RuntimeException
     */
    public function create(Request $request): JsonResponse
    {
        $data = empty($this->requestFields) ? $request->all() : $request->only($this->requestFields);

        if (!$data) {
            return $this->answerFail(['payload' => 'empty']);
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

            $this->proccessImage(InputData::createFromArray([
                'record' => $record,
                'field' => $fieldName,
                'file' => $request->file($fieldName),
                'path' => $record->uploadPath
            ]));
            unset($data[$fieldName]);
        }

        $this->beforeFill($data);
        $record->fill($data);
        $this->beforeSave($record);
        $record->save();

        return $this->answerSuccess($record->toArray());
    }
}
