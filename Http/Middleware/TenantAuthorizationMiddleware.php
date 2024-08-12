<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use App\Models\Tenant;
use Astrotech\Core\Base\Exception\ValidationException;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Closure;
use Illuminate\Http\Request;
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
            $tenant = Tenant::authenticateTenant($token);
            app()->instance('tenant', $tenant->toSoftArray());

            $request->headers->set('X-Tenant-Id', $tenant->external_id);
            $request->headers->set('X-Tenant-Name', $tenant->name);
            $request->headers->set('X-Tenant-Schema', $tenant->schema);
            $request->headers->set('X-Tenant-Url', $tenant->url);

            return $next($request);
        } catch (SignatureInvalidException $e) {
            throw new ValidationException(
                details: ['error' => 'invalidSignature', 'message' => $e->getMessage()],
                code: HttpStatus::FORBIDDEN->value
            );
        } catch (TokenExpiredException $e) {
            throw new ValidationException(
                details: ['error' => 'expiredToken', 'message' => $e->getMessage()],
                code: HttpStatus::FORBIDDEN->value
            );
        } catch (UnexpectedValueException $e) {
            throw new ValidationException(
                details: ['error' => 'unexpectedTokenValue', 'message' => $e->getMessage()],
                code: HttpStatus::FORBIDDEN->value
            );
        }
    }
}
