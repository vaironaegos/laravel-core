<?php

namespace Astrotech\Core\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
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
            DB::connection($connection)->statement('CREATE SCHEMA ' . $schema);
            $this->info('Schema created: ' . $schema);
        }

        DB::connection($connection)->statement('SET search_path TO ' . $schema);

        Config::set('database.migrations', $schema . '.migrations');

        $options = [
            '--database' => $connection,
            '--path' => 'database/migrations/tenants',
            '--force' => $this->option('force'),
        ];

        if ($this->option('fresh')) {
            $this->info('Cleaning all ' . $schema . ' tables...');
            DB::connection($connection)->statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
            DB::connection($connection)->statement("CREATE SCHEMA {$schema}");
            $this->info('Done!');
        }

        $this->info('Running migrations for schema: ' . $schema);
        $output = new BufferedOutput();
        Artisan::call('migrate', $options, $output);
        $this->info($output->fetch());
        $this->info('Migrations completed for schema: ' . $schema);
    }
}
