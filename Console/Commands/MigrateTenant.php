<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

final class MigrateTenant extends Command
{
    protected $signature = 'migrate:tenant {schema} {connection=pgsql} {--fresh} {--force}';
    protected $description = 'Run migrations for a specific tenant schema';

    public function handle(): void
    {
        $schema = $this->argument('schema');
        $connection = $this->argument('connection');
        $dbName = config("database.connections.{$connection}.database");
        Config::set('database.default', $connection);

        $this->info("Database connection '{$connection}' and database '{$dbName}'");

        $schemaExists = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
            [$schema]
        );

        if (empty($schemaExists)) {
            $this->info("Schema '{$schema}' does not exists! Creating...");
            DB::connection($connection)->statement('CREATE SCHEMA "' . $schema . '"');
            $this->info('New schema created!');
        }

        DB::connection($connection)->statement('SET search_path TO "' . $schema . '"');

        $this->info("Changing migrations table to '{$schema}.migrations' tables...");
        Config::set('database.migrations', $schema . '.migrations');
        $this->info("Done!");

        $options = [
            '--database' => $connection,
            '--path' => 'database/migrations/tenants',
            '--force' => $this->option('force'),
        ];

        if ($this->option('fresh')) {
            $this->info("Dropping schema '{$schema}'...");
            DB::connection($connection)->statement('DROP SCHEMA IF EXISTS "' . $schema . '" CASCADE');
            $this->info("Schema deleted!");
            $this->info("Create schema '{$schema}'...");
            DB::connection($connection)->statement('CREATE SCHEMA "' . $schema . '"');
            $this->info("Schema created!");
        }

        $this->info('Running migrations for schema: ' . $schema);
        $output = new BufferedOutput();
        DB::connection($connection)->statement('SET search_path TO "' . $schema . '"');
        Artisan::call('migrate', $options, $output);
        DB::connection($connection)->statement('SET search_path TO "' . $schema . '"');
        $this->info($output->fetch());
        $this->info('Migrations completed for schema: ' . $schema);
    }
}
