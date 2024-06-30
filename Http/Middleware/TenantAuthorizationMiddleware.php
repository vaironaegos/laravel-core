<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UnexpectedValueException;
use Firebase\JWT\SignatureInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

final class TenantAuthorizationMiddleware
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
                throw new HttpException(403, 'Your request was made without an authorization token.');
            }

            $token = trim(explode(' ', $token)[1]);
            $payload = JWT::decode($token, new Key(config('jwt.keys.public'), config('jwt.algo')));

            $tenant = DB::table('public.tenants')
                ->select(['external_id as id', 'name', 'schema', 'url'])
                ->where(['external_id' => $payload->sub, 'active' => 1])
                ->first();

            if (!$tenant) {
                throw new HttpException(403, 'Access Denied for this token.');
            }

            $request->merge([
                'X-Tenant-Id' => $tenant->id,
                'X-Tenant-Name' => $tenant->name,
                'X-Tenant-Schema' => $tenant->schema,
            ]);

            app()->instance('tenant', (array)$tenant);

            return $next($request);

        } catch (SignatureInvalidException $e) {
            throw new HttpException(403, 'Invalid Signature');
        } catch (TokenExpiredException $e) {
            throw new HttpException(403, 'Token is Expired');
        } catch (UnexpectedValueException $e) {
            throw new HttpException(403, 'Token is Invalid');
        }
    }
}
