<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http;

enum HttpStatus: int
{
    /**
     * Successful responses
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#successful_responses
     */
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NON_AUTHORITATIVE_INFORMATION = 203;
    case NO_CONTENT = 204;

    /**
     * Redirection messages
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#redirection_messages
     */
    case MOVED_PERMANENTLY = 301;
    case NOT_MODIFIED = 304;
    case PERMANENT_REDIRECT = 308;

    /**
     * Client error responses
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#client_error_responses
     */
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case REQUEST_TIMEOUT = 408;
    case GONE = 410;

    /**
     * Server error responses
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#server_error_responses
     */
    case INTERNAL_SERVER_ERROR = 500;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;
}
