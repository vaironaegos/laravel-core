<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Astrotech\Core\Base\Exception\ExceptionBase;

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

        if ($response->exception instanceof QueryException) {
            return response()->json([
                'status' => 'error',
                'data' => $response->exception->getBindings(),
                'meta' => [
                    'message' => $response->exception->getMessage(),
                    'sql' => $response->exception->getSql(),
                    'trace' => !app()->environment('production') ? $response->exception->getTrace() : []
                ],
            ])->setStatusCode(500);
        }


        return $response;
    }
}
