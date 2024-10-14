<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use App\Models\Tenant;
use Astrotech\Core\Base\Exception\ValidationException;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $contextId = $request->headers->get('Context');

        if (!$contextId) {
            throw new ValidationException(
                details: ['error' => 'missingContextHeader'],
                code: HttpStatus::UNAUTHORIZED->value
            );
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::findByExternalId($contextId);

        if (!$tenant) {
            throw new ValidationException(
                details: ['error' => 'invalidContext'],
                code: HttpStatus::UNAUTHORIZED->value
            );
        }

        DB::connection()->statement('SET search_path TO ' . $tenant->schema);
        app()->instance('tenant', $tenant->toSoftArray());

        $request->headers->set('X-Tenant-Id', $tenant->external_id);
        $request->headers->set('X-Tenant-Name', $tenant->name);
        $request->headers->set('X-Tenant-Schema', $tenant->schema);

        return $next($request);
    }
}
