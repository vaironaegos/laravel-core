<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use UnexpectedValueException;
use Firebase\JWT\SignatureInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

final class AuthorizationMiddleware
{
    /**
     * Handle an incoming request.
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->headers->get('Authorization');

            if (!$token) {
                return response()->json(['status' => 'Your request was made without an authorization token.'], 403);
            }

            $token = trim(explode(' ', $token)[1]);
            $payload = JWT::decode($token, new Key(config('jwt.keys.public'), config('jwt.algo')));
            $user = User::firstWhere('id', Uuid::fromString($payload->sub)->getBytes());

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
