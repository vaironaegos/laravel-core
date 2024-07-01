<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Astrotech\Core\Base\Exception\ValidationException;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Closure;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use UnexpectedValueException;
use Firebase\JWT\SignatureInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

final class NewAuthorizationMiddleware
{
    public function handle(Request $request, Closure $next)
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
            $user = User::firstWhere('external_id', $payload->sub);

            if (!$user) {
                throw new ValidationException(
                    details: ['error' => 'accessDenied'],
                    code: HttpStatus::FORBIDDEN->value
                );
            }

            auth('api')->login($user);
        } catch (SignatureInvalidException $e) {
            throw new ValidationException(
                details: ['error' => 'invalidSignature'],
                code: HttpStatus::FORBIDDEN->value
            );
        } catch (TokenExpiredException $e) {
            throw new ValidationException(
                details: ['error' => 'expiredToken'],
                code: HttpStatus::FORBIDDEN->value
            );
        } catch (UnexpectedValueException $e) {
            throw new ValidationException(
                details: ['error' => 'invalidToken'],
                code: HttpStatus::FORBIDDEN->value
            );
        }

        return $next($request);
    }
}
