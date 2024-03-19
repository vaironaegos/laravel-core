<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http;

use Illuminate\Support\Facades\Route;

final class Router extends Route
{
    public static function crud(string $uri, string $controllerName): void
    {
        $actions = [
            ['verb' => 'get', 'path' => $uri, 'method' => 'search'],
            ['verb' => 'get', 'path' => "{$uri}/options", 'method' => 'options'],
            ['verb' => 'post', 'path' => $uri, 'method' => 'create'],
            ['verb' => 'get', 'path' => "{$uri}/{id:uuid}", 'method' => 'read'],
            ['verb' => 'put', 'path' => "{$uri}/{id:uuid}", 'method' => 'update'],
            ['verb' => 'patch', 'path' => "{$uri}/{id:uuid}", 'method' => 'update'],
            ['verb' => 'post', 'path' => "{$uri}/{id:uuid}", 'method' => 'update'],
            ['verb' => 'delete', 'path' => "{$uri}/{id:uuid}", 'method' => 'delete'],
        ];

        foreach ($actions as $action) {
            $verb = $action['verb'];
            $path = $action['path'];
            $method = $action['method'];
            if (!method_exists($controllerName, $method)) {
                continue;
            }
            static::$verb($path, "{$controllerName}@{$method}")->middleware("permission:{$method},{$uri}");
        }
    }
}
