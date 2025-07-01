<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use UnexpectedValueException;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Firebase\JWT\SignatureInvalidException;
use Astrotech\Core\Base\Exception\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

final class AuthorizationMiddleware
{
    /**
     * Handle an incoming request.
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws ValidationException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $token = $request->headers->get('Authorization');

            if (!$token) {
                throw new ValidationException(
                    details: ['error' => 'missingAuthorizationHeader'],
                    code: HttpStatus::UNAUTHORIZED->value
                );
            }

            $token = trim(explode(' ', $token)[1]);
            $payload = JWT::decode($token, new Key(config('jwt.keys.public'), config('jwt.algo')));
            $user = User::query()->firstWhere('external_id', $payload->sub);

            if (!$user) {
                throw new ValidationException(
                    details: ['error' => 'userNotFound'],
                    code: HttpStatus::UNAUTHORIZED->value
                );
            }

            auth('api')->login($user);
        } catch (SignatureInvalidException $e) {
            throw new ValidationException(
                details: ['error' => 'invalidSignature'],
                message: $e->getMessage(),
                code: HttpStatus::UNAUTHORIZED->value
            );
        } catch (TokenExpiredException $e) {
            throw new ValidationException(
                details: ['error' => 'tokenExpired'],
                message: $e->getMessage(),
                code: HttpStatus::UNAUTHORIZED->value
            );
        } catch (UnexpectedValueException $e) {
            throw new ValidationException(
                details: ['error' => 'invalidToken'],
                message: $e->getMessage(),
                code: HttpStatus::UNAUTHORIZED->value
            );
        }

        return $next($request);
    }
}
