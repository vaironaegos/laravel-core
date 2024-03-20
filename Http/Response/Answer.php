<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Response;

use Astrotech\Core\Laravel\Http\HttpStatus;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

abstract class Answer
{
    public static function success($data, array $meta, HttpStatus $code = HttpStatus::OK): JsonResponse
    {
        $response = [
            'status' => 'success',
            'data' => $data,
            'meta' => $meta,
        ];
        return static::response($response, $code);
    }

    public static function fail($data, array $meta, HttpStatus $code = HttpStatus::BAD_REQUEST): JsonResponse
    {
        $response = [
            'status' => 'fail',
            'data' => $data,
            'meta' => $meta,
        ];
        return static::response($response, $code);
    }

    public static function error(
        $message,
        HttpStatus $code = HttpStatus::INTERNAL_SERVER_ERROR,
        $meta = null
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return static::response($response, $code);
    }

    protected static function response(array $data, HttpStatus $code): JsonResponse
    {
        try {
            return Response::json($data, $code->value);
        } catch (Throwable $throwable) {
        }

        return Response::json($data, $code->value);
    }
}
