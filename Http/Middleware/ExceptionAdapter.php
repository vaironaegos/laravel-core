<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Astrotech\Core\Base\Exception\ExceptionBase;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ExceptionAdapter
{
    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);
        if ($response->exception instanceof ExceptionBase) {
            return response()->json([
                'status' => 'fail',
                'data' => $response->exception->details(),
                'meta' => [
                    'message' => $response->exception->getMessage(),
                    'trace' => !app()->environment('production') ? $response->exception->getTrace() : []
                ],
            ])->setStatusCode($response->exception->getStatusCode());
        }


        return $response;
    }
}
