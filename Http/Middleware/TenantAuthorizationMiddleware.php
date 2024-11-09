<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use UnexpectedValueException;
use Illuminate\Support\Facades\DB;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Firebase\JWT\SignatureInvalidException;
use Astrotech\Core\Base\Exception\ValidationException;
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

            if (!str_contains($token, 'Basic')) {
                throw new ValidationException(
                    details: ['error' => 'invalidToken'],
                    code: HttpStatus::UNAUTHORIZED->value
                );
            }

            $explodeToken = explode(' ', $token);

            if (count($explodeToken) !== 2) {
                throw new ValidationException(
                    details: ['error' => 'invalidToken'],
                    code: HttpStatus::UNAUTHORIZED->value
                );
            }

            $token = trim($explodeToken[1]);
            $tenant = Tenant::authenticateTenant($token);
            DB::connection()->statement('SET search_path TO "' . $tenant->schema . '"');
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
