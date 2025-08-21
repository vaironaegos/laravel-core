<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Console\Commands;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;
use Illuminate\Console\Command as CommandController;
use Symfony\Component\Console\Output\BufferedOutput;

final class MigrateTenant extends CommandController
{
    protected $signature = 'schema:migrate {schema} {connection=pgsql} {--fresh} {--force} {--rollback}';
    protected $description = 'Run migrations for a specific tenant schema';

    public function handle(): int
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

            $this->insertNewTenant($schema);
            $this->info('Tenant register inserted into database!');
        }

        DB::connection($connection)->statement('SET search_path TO "' . $schema . '"');
        app()->instance('tenant', ['schema' => $schema]);

        $this->info("Changing migrations table to '{$schema}.migrations' tables...");
        Config::set('database.migrations', $schema . '.migrations');
        $this->info("Done!");

        $options = [
            '--database' => $connection,
            '--path' => 'database/migrations/tenants',
            '--force' => $this->option('force'),
        ];

        if ($this->option('rollback')) {
            $this->info('Rollback migrations for schema: ' . $schema);
            $output = new BufferedOutput();
            Artisan::call('migrate:rollback', $options, $output);
            $this->info($output->fetch());
            $this->info('Migrations rollback successfully for schema: ' . $schema);

            return Command::SUCCESS;
        }

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
        Artisan::call('migrate', $options, $output);
        $this->info($output->fetch());
        $this->info('Migrations completed for schema: ' . $schema);

        return Command::SUCCESS;
    }

    private function insertNewTenant(string $schema): void
    {
        if (!Schema::hasTable('public.tenants')) {
            return;
        }

        $schemaExists = DB::table('public.tenants')->where('schema', $schema)->exists();

        if ($schemaExists) {
            return;
        }

        DB::table('public.tenants')->insert([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => ucwords(str_replace('-', ' ', $schema)),
            'schema' => $schema,
            'path' => $schema,
            'created_at' => now()->format("Y-m-d H:i:s"),
            'created_by' => 'MIGRATION'
        ]);
    }
}
