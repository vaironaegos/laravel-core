<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;

final class ConvertNullStrings
{
    public function handle($request, Closure $next)
    {
        $request->merge(array_map(function ($value) {
            return $value === 'null' ? null : $value;
        }, $request->all()));

        return $next($request);
    }
}
