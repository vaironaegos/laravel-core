<?php

namespace Astrotech\Core\Laravel\AuthGuardian\Helpers;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;

trait AuthGuardianErrorHandler
{
    public function handleGuardianError(RequestException|ConnectException $exception): JsonResponse
    {
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

        return response()->json(['error' => 'connectError', 'message' => $exception->getMessage()], 500);
    }
}
