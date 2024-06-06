<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Adapters;

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
}
