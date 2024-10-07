<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BoundedContextStructure extends Command
{
    protected $signature = 'make:bounded-context {name : The name of the bounded context}';
    protected $description = 'Creates a new bounded context structure inside /domain folder';

    public function handle(): int
    {
        $name = ucfirst($this->argument('name'));
        $basePath = base_path("agnostic-app/domain/{$name}");

        $folders = [
            'Entities',
            'Repositories',
            'Services',
            'UseCases',
            'ValueObjects',
            'Enum'
        ];

        foreach ($folders as $folder) {
            $path = "{$basePath}/{$folder}";
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                File::put("{$path}/.gitkeep", '');
            }
        }

        $this->info("Bounded context '{$name}' created successfully!");

        $sharedPath = base_path("agnostic-app/domain/Shared");

        if (File::exists($sharedPath)) {
            $this->info("Bounded context '{$name}' has been created before!");
            return 0;
        }

        $sharedFolders = ['Adapters', 'Contracts', 'ValueObjects', 'Exceptions',];

        foreach ($sharedFolders as $folder) {
            $path = "{$sharedPath}/{$folder}";
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                File::put("{$path}/.gitkeep", '');
            }
        }

        $this->info("Shared folder structure created successfully!");

        return 0;
    }
}
