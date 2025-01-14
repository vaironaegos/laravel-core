<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent\Uploadable;

use Illuminate\Support\Facades\Storage;
use Astrotech\Core\Base\Exception\ValidationException;

trait Uploadable
{
    protected function processImage(InputData $data): void
    {
        if ($data->multiple) {
            $filesNames = [];
            foreach ($data->file as $file) {
                if (is_string($file)) {
                    continue;
                }

                if (
                    Storage::disk('public')->exists($data->path . '/' . $file->getClientOriginalName())
                ) {
                    array_push($filesNames, $file->getClientOriginalName());
                    continue;
                }

                $extension = $file->getClientOriginalExtension();

                if (!in_array($extension, $data->allowedExtensions)) {
                    throw new ValidationException(["field" => "{$data->field}", "error" => 'invalidExtension']);
                }

                $fileName = uniqid(date('HisYmd')) . '.' . $extension;
                $file->storeAs($data->path, $fileName, 'public');
                array_push($filesNames, $fileName);
            }

            $data->record->{$data->field} = $filesNames;
            return;
        }

        if (is_string($data->file)) {
            return;
        }

        $extension = $data->file->getClientOriginalExtension();

        if (!in_array($extension, $data->allowedExtensions)) {
            throw new ValidationException(["field" => $data->field, "error" => 'invalidExtension']);
        }

        $fileName = uniqid(date('HisYmd')) . '.' . $extension;
        $data->file->storeAs($data->path, $fileName, 'public');
        $data->record->{$data->field} = $fileName;
    }
}
