<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Exception;

use Throwable;
use Illuminate\Database\QueryException;
use GuzzleHttp\Exception\RequestException;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Illuminate\Validation\ValidationException;
use Astrotech\Core\Base\Exception\ExceptionBase;
use Astrotech\Core\Base\Adapter\Contracts\LogSystem;
use Illuminate\Foundation\Exceptions\Handler as LaravelHandlerException;
use Astrotech\Core\Base\Exception\ValidationException as AppValidationException;

use function Sentry\init;
use function Sentry\captureException;

class ExceptionHandler extends LaravelHandlerException
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        $sentryIsEnabled = filled(config('services.sentry.dsn'));

        if ($sentryIsEnabled) {
            init([
                'dsn' => config('services.sentry.dsn'),
                'environment' => config('app.env'),
            ]);
        }

        /** @var LogSystem $logSystem */
        $logSystem = app()->make(LogSystem::class);
        $isProduction = app()->environment('production');
        $isDevelopment = app()->environment('local');

        $request->headers->set('Accept', 'application/json');

        if ($e instanceof QueryException) {
            $response = [
                'status' => 'error',
                'data' => $e->getBindings(),
                'meta' => [
                    'message' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'trace' => !$isProduction ? $e->getTrace() : []
                ],
            ];

            if ($sentryIsEnabled && !$isDevelopment) {
                captureException($e);
            }

            if ($isProduction) {
                $logSystem->error($e->getMessage(), [
                    'category' => get_class($e),
                    'extraData' => $response
                ]);
            }

            return response()->json($response)->setStatusCode(500);
        }

        if ($e instanceof ValidationException) {
            $data = [];

            foreach ($e->validator->failed() as $fieldName => $errors) {
                foreach ($errors as $validatorName => $a) {
                    $data[] = ['field' => $fieldName, 'error' => strtolower($validatorName)];
                }
            }

            $response = [
                'status' => 'fail',
                'data' => $data,
                'meta' => [
                    'errors' => $e->errors(),
                    'message' => $e->getMessage(),
                    'trace' => !$isProduction ? $e->getTrace() : []
                ],
            ];

            if ($isProduction) {
                $logSystem->error($e->getMessage(), [
                    'category' => get_class($e),
                    'extraData' => $response
                ]);
            }

            return response()->json($response)->setStatusCode($e->status);
        }

        if ($e instanceof RequestException) {
            if ($sentryIsEnabled && !$isDevelopment) {
                captureException($e);
            }

            return response()->json([
                'status' => 'fail',
                'data' => json_decode($e->getResponse()->getBody()->getContents(), true),
                'message' => $e->getMessage()
            ], $e->getResponse()->getStatusCode());
        }

        if ($e instanceof AppValidationException) {
            $response = [
                'status' => 'fail',
                'data' => $e->details(),
                'meta' => [
                    'message' => $e->getMessage(),
                    'trace' => !$isProduction ? $e->getTrace() : []
                ],
            ];

            return response()
                ->json($response)
                ->setStatusCode(HttpStatus::BAD_REQUEST->value);
        }

        if ($e instanceof ExceptionBase) {
            $response = [
                'status' => 'fail',
                'data' => $e->details(),
                'meta' => [
                    'message' => $e->getMessage(),
                    'trace' => !$isProduction ? $e->getTrace() : []
                ],
            ];

            if ($sentryIsEnabled && !$isDevelopment) {
                captureException($e);
            }

            if ($isProduction) {
                $logSystem->error($e->getMessage(), [
                    'category' => get_class($e),
                    'extraData' => $response
                ]);
            }

            return response()
                ->json($response)
                ->setStatusCode(HttpStatus::INTERNAL_SERVER_ERROR->value);
        }

        $response = [
            'status' => 'fail',
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
            'meta' => [
                'message' => $e->getMessage(),
                'trace' => !$isProduction ? $e->getTrace() : []
            ],
        ];

        if ($sentryIsEnabled && !$isDevelopment) {
            captureException($e);
        }

        if ($isProduction) {
            $logSystem->error($e->getMessage(), [
                'category' => get_class($e),
                'extraData' => $response
            ]);
        }

        return response()
            ->json($response)
            ->setStatusCode(HttpStatus::INTERNAL_SERVER_ERROR->value);
    }
}
