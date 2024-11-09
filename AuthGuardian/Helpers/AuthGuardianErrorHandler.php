<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\AuthGuardian\Helpers;

use Illuminate\Http\JsonResponse;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

trait AuthGuardianErrorHandler
{
    public function handleGuardianError(
        RequestException|ConnectException $exception,
        bool $returnJsonResponse = true
    ): JsonResponse|array {
        if ($exception instanceof RequestException) {
            $statusCode = $exception->getResponse()->getStatusCode();
            $responsePayload = json_decode($exception->getResponse()->getBody()->getContents(), true);

            if ($statusCode === 400) {
                return response()->json($responsePayload, $exception->getResponse()->getStatusCode());
            }

            return response()->json([
                'error' => 'validationError',
                'message' => $exception->getMessage()
            ], $exception->getResponse()->getStatusCode());
        }

        $details = ['error' => 'connectError', 'message' => $exception->getMessage()];

        return $returnJsonResponse ?
            response()->json($details, 500) :
            $details;
    }
}
