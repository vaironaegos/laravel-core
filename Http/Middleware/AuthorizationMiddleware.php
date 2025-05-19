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
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     * @throws ValidationException
     */
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
                return response()->json(['status' => 'User not found'], 404);
            }

            auth('api')->login($user);
        } catch (SignatureInvalidException $e) {
            return response()->json(['status' => 'Invalid Signature'], 403);
        } catch (TokenExpiredException $e) {
            return response()->json(['status' => 'Token is Expired'], 403);
        } catch (UnexpectedValueException $e) {
            return response()->json(['status' => 'Token is Invalid'], 403);
        }

        return $next($request);
    }
}
