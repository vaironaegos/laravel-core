<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Astrotech\Core\Laravel\Utils\KeyCaseConverter;

final class RequestToCamelCase
{
    use KeyCaseConverter;

    public function handle(Request $request, Closure $next)
    {
        $request->replace($this->convert('camel', $request->all()));
        return $next($request);
    }
}
