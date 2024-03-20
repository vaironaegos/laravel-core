<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Astrotech\Core\Laravel\Utils\KeyCaseConverter;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ResponseToJson
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        /** @var Response $response */
        $response = $next($request);
        $response->header('Content-Type', 'application/json');

        return $response;
    }
}
