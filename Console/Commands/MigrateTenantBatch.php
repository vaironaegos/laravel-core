<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Console\Commands;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Illuminate\Console\Command as CommandController;

final class MigrateTenantBatch extends CommandController
{
    protected $signature = 'schema:migrate-batch';
    protected $description = 'Run migrations for all registered tenants';

    public function handle(): int
    {
        $tenants = DB::table('public.tenants')
            ->select(['schema'])
            ->orderBy('schema')
            ->get();

        foreach ($tenants as $tenant) {
            DB::purge('pgsql');
            $this->info("=========================================================================");
            $this->info("=========== Running migrations for tenant '{$tenant->schema}' ===========");
            $this->info("=========================================================================");

            $process = new Process([
                'php', 'artisan', 'schema:migrate', $tenant->schema, '--force'
            ]);

            $process->run(function ($type, $buffer) {
                echo $buffer;
            });

            $this->info("Done!");
            sleep(2);
        }

        return Command::SUCCESS;
    }
}
