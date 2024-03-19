<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Astrotech\Core\Laravel\Utils\KeyCaseConverter;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ResponseToCamelCase
{
    use KeyCaseConverter;

    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setData($this->convert('camel', json_decode($response->content(), true)));
        }

        return $response;
    }
}
