<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MigrateTenantBatch extends Command
{
    protected $signature = 'schema:migrate-batch';
    protected $description = 'Run migrations for all registered tenants';

    public function handle(): void
    {
        $tenants = DB::table('public.tenants')
            ->select(['schema'])
            ->orderBy('schema')
            ->get();

        foreach ($tenants as $tenant) {
            $this->info("=========== Running migrations for tenant '{$tenant->schema}' ===========");
            $this->call('schema:migrate', ['schema' => $tenant->schema, '--force' => true]);
            $this->info("Done!");
            $this->info("=========================================================================");
            sleep(1);
        }
    }
}
