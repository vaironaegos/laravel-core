<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Astrotech\Core\Laravel\Http\HttpStatus;

/**
 * Trait AnswerTrait
 * JSEND
 *
 * @link https://labs.omniti.com/labs/jsend
 * |------------------------------------------------------------------------------------------------------------------|
 * | Type     | Description                                             | Required Keys          | Optional Keys      |
 * |------------------------------------------------------------------------------------------------------------------|
 * | success  | All went well, and (usually) some data was returned.    | status, data           |                    |
 * |..........|.........................................................|........................|....................|
 * | fail     | There was a problem with the data submitted, or some    | status, data           |                    |
 * |          | pre-condition of the API call wasn't satisfied          |                        |                    |
 * |..........|.........................................................|........................|....................|
 * | error    | An error occurred in processing the request, i.e. an    | status, message        | code, data         |
 * |          | exception was thrown                                    |                        |                    |
 * |------------------------------------------------------------------------------------------------------------------|
 * @package Devitools\Http\Response\Answer
 */
trait AnswerTrait
{
    public function answerSuccess(mixed $data, array $meta = [], HttpStatus $code = HttpStatus::OK): JsonResponse
    {
        return Answer::success($data, $meta, $code);
    }

    public function answerFail($data, array $meta = [], HttpStatus $code = HttpStatus::BAD_REQUEST): JsonResponse
    {
        return Answer::fail($data, $meta, $code);
    }

    public function answerError(
        $message,
        HttpStatus $code = HttpStatus::INTERNAL_SERVER_ERROR,
        $data = null
    ): JsonResponse {
        return Answer::error($message, $code, $data);
    }

    public function answerNoContent(HttpStatus $code = HttpStatus::NO_CONTENT): JsonResponse
    {
        return Response::json([], $code->value);
    }
}
