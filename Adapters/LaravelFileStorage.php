<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Adapters;

use Illuminate\Support\Facades\Storage;
use Astrotech\Core\Base\Adapter\Contracts\FileStorage;

class LaravelFileStorage implements FileStorage
{
    public function store(string $destinationPath, string $newName = ''): string
    {
        if (empty($newName)) {
            $newName = time();
        }

        $file = current(request()->allFiles());
        if (!$file) {
            return '';
        }

        $fileName = $newName . '.' . $file->extension();
        $file->storeAs($destinationPath, $fileName, 'public');

        return $fileName;
    }

    public function delete(string $destinationPathWithFileName): bool
    {
        $disk = Storage::disk('public');

        if ($disk->exists($destinationPathWithFileName)) {
            return $disk->delete($destinationPathWithFileName);
        }

        return false;
    }
}
