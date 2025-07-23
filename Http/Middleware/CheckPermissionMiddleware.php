<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Astrotech\Core\Laravel\Http\HttpStatus;
use Astrotech\Core\AuthGuardian\Models\User;
use Astrotech\Core\Laravel\Http\Response\AnswerTrait;

final class CheckPermissionMiddleware
{
    use AnswerTrait;

    public function handle(Request $request, Closure $next, string $moduleName, string $actionName)
    {
        /** @var User $loggedUser */
        $loggedUser = auth()->user();

        if (!$loggedUser) {
            return $next($request);
        }

        $moduleName = preg_replace('/[^a-zA-Z\-]/', '', $moduleName);

        if (empty($moduleName)) {
            $moduleName = explode('/', $request->path())[0];
        }

        $actionName = $actionName !== 'access'
            ? '.' . preg_replace('/[^a-zA-Z\-]/', '', $actionName)
            : '';

        $permissionKey = "{$moduleName}.access{$actionName}";

        $userPermissions = User::query()
            ->with('group.permissions')
            ->find($loggedUser->id)
            ->group
            ->permissions
            ->pluck('key')
            ->toArray();

        if (!in_array($permissionKey, $userPermissions)) {
            return $this->answerFail(
                data: [
                    'requiredPermission' => $permissionKey,
                    'message' => 'accessDenied',
                    'group' => $loggedUser->group->name,
                ],
                code: HttpStatus::FORBIDDEN
            );
        }

        return $next($request);
    }
}
