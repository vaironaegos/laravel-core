<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InfraStructure extends Command
{
    protected $signature = 'make:infra {name : The name of the infrastructure context}';
    protected $description = 'Creates a new infrastructure context structure.';

    public function handle(): int
    {
        $name = ucfirst($this->argument('name'));
        $basePath = base_path("infrastructure/{$name}");
        $folders = ['Persistence', 'Queue', 'Events', 'Adapters'];

        foreach ($folders as $folder) {
            $path = "{$basePath}/{$folder}";
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                File::put("{$path}/.gitkeep", '');
            }
        }

        $this->info("Infrastructure context '{$name}' created successfully!");

        return 0;
    }
}
