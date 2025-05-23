<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Astrotech\Core\Laravel\Utils\KeyCaseConverter;

final class ResponseToCamelCase
{
    use KeyCaseConverter;

    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        if ($response instanceof JsonResponse && !empty($response->content())) {
            $response->setData($this->convert('camel', json_decode($response->content(), true)));
        }

        return $response;
    }
}
