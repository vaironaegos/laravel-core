<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent\Uploadable;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
                    throw new ValidationException([
                        "field" => $data->requestField ?? $data->field,
                        "error" => 'invalidExtension'
                    ]);
                }

                $fileName = $data->filename ?? uniqid(date('HisYmd'));
                $fileName .= '.' . $extension;

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
            throw new ValidationException([
                "field" => $data->requestField ?? $data->field,
                "error" => 'invalidExtension'
            ]);
        }

        $fileName = uniqid(date('HisYmd')) . '.' . $extension;
        $data->file->storeAs($data->path, $fileName, 'public');
        $data->record->{$data->field} = $fileName;
    }

    protected function storeBase64File(InputData $data): void
    {
        if (!$data->base64) {
            throw new ValidationException(["field" => $data->requestField ?? $data->field, "error" => 'invalidBase64']);
        }

        $tmpFileObject = $this->validateBase64(
            $data->requestField ?? $data->field,
            $data->base64,
            $data->allowedExtensions
        );

        $tmpFileObjectPathName = $tmpFileObject->getPathname();

        $file = new UploadedFile(
            $tmpFileObjectPathName,
            $tmpFileObject->getFilename(),
            $tmpFileObject->getMimeType(),
            0,
            true
        );

        $fileName = $data->filename ?? uniqid(date('HisYmd'));
        $fileName .= '.' . $file->extension();

        $file->storeAs($data->path, $fileName, 'public');
        $data->record->{$data->field} = $fileName;
    }

    protected function validateBase64(string $field, string $base64, array $allowedExtensions = []): File
    {
        $base64data = null;

        if (str_contains($base64, ';base64')) {
            list(, $base64data) = explode(';', $base64);
            list(, $base64data) = explode(',', $base64data);
        }

        if (
            base64_decode($base64data, true) === false
            || base64_encode(base64_decode($base64data)) !== $base64data
        ) {
            throw new ValidationException(["field" => $field, "error" => 'invalidBase64']);
        }

        $fileBinaryData = base64_decode($base64data);

        $tmpFileName = tempnam(sys_get_temp_dir(), 'base64-image');
        file_put_contents($tmpFileName, $fileBinaryData);

        $tmpFileObject = new File($tmpFileName);

        if (empty($allowedExtensions)) {
            return $tmpFileObject;
        }

        $validation = Validator::make(
            ['file' => $tmpFileObject],
            ['file' => 'mimes:' . implode(',', $allowedExtensions)]
        );

        if ($validation->fails()) {
            throw new ValidationException(["field" => $field, "error" => 'invalidExtension']);
        }

        return $tmpFileObject;
    }
}
