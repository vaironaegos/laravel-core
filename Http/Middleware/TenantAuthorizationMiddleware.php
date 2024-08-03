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
use Illuminate\Support\Facades\Cache;
use UnexpectedValueException;
use Firebase\JWT\SignatureInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

final class TenantAuthorizationMiddleware
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
            app()->instance('tenant', (array)$payload->tenant);

            $request->headers->set('X-User-Id', $payload->sub);
            $request->headers->set('X-User-Name', $payload->name);
            $request->headers->set('X-User-Login', $payload->login);
            $request->headers->set('X-Tenant-Id', $payload->tenant->id);
            $request->headers->set('X-Tenant-Name', $payload->tenant->name);
            $request->headers->set('X-Tenant-Schema', $payload->tenant->schema);
            $request->headers->set('X-Tenant-Url', $payload->tenant->url);

            $model = new User();
            $userCacheKey = "{$model->getTable()}_{$payload->sub}";

            if (Cache::has($userCacheKey)) {
                $userAttributes = Cache::get($userCacheKey);
                auth('api')->login(new User($userAttributes));
                return $next($request);
            }

            /** @var User $user */
            $user = User::firstWhere('external_id', $payload->sub);

            if (!$user) {
                throw new ValidationException(
                    details: ['error' => 'accessDenied'],
                    code: HttpStatus::FORBIDDEN->value
                );
            }

            auth('api')->login($user);
            return $next($request);
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
    }
}
