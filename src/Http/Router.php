<?php

namespace Jtech\Http;

use Exception;
use Jtech\Database\Model;
use ReflectionMethod;

class Router
{
    protected static array $routes = [];
    protected static array $groupStack = [];
    protected static array $defaultMiddleware = [];

    protected static array $pendingRoute = [
        'method' => null,
        'uri' => null,
        'action' => null,
        'name' => null,
        'middleware' => [],
    ];

    /* =========================
     | HTTP METHODS
     ========================= */

    public static function get(string $uri, string|callable|array $action)
    {
        static::prepareRoute('GET', $uri, $action);
        return new static;
    }

    public static function post(string $uri, string|callable|array $action)
    {
        static::prepareRoute('POST', $uri, $action);
        return new static;
    }

    public static function put(string $uri, string|callable|array $action)
    {
        static::prepareRoute('PUT', $uri, $action);
        return new static;
    }

    public static function delete(string $uri, string|callable|array $action)
    {
        static::prepareRoute('DELETE', $uri, $action);
        return new static;
    }

    /* =========================
     | GROUPING
     ========================= */

    public static function group(array $attributes, callable $callback)
    {
        static::$groupStack[] = [
            'prefix'     => $attributes['prefix'] ?? '',
            'middleware' => (array) ($attributes['middleware'] ?? []),
            'controller' => $attributes['controller'] ?? null,
            'name'       => $attributes['name'] ?? '',
        ];

        $callback();

        array_pop(static::$groupStack);
    }

    /* =========================
     | ROUTE BUILDING
     ========================= */

    protected static function prepareRoute(string $method, string $uri, $action)
    {
        $controller = static::resolveController();

        if (is_string($action) && !str_contains($action, '@') && $controller) {
            $action = $controller . '@' . $action;
        }

        static::$pendingRoute = [
            'method'     => $method,
            'uri'        => static::resolvePrefix($uri),
            'action'     => $action,
            'name'       => null,
            'middleware' => [],
        ];
    }

    public function middleware(string|array $middleware)
    {
        static::$pendingRoute['middleware'] = (array) $middleware;
        return $this;
    }

    public function name(string $name)
    {
        static::$pendingRoute['name'] = static::resolveName($name);
        static::commitRoute();
        return $this;
    }

    protected static function commitRoute()
    {
        static::$pendingRoute['middleware'] =
            static::resolveMiddleware(static::$pendingRoute['middleware']);

        static::$routes[] = static::$pendingRoute;

        static::$pendingRoute = [
            'method' => null,
            'uri' => null,
            'action' => null,
            'name' => null,
            'middleware' => [],
        ];
    }
    public function setDefaultMiddleware(callable $func)
    {
        $middleware =  (array) $func();
        static::$defaultMiddleware = $middleware;
    }

    public function exludeRouteFromMiddleware($routes)
    {
        // TODO
    }

    /* =========================
     | RESOLVERS
     ========================= */

    protected static function resolvePrefix(string $uri): string
    {
        $prefix = '';

        foreach (static::$groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'], '/');
        }

        return rtrim($prefix . '/' . trim($uri, '/'), '/') ?: '/';
    }

    protected static function resolveController(): ?string
    {
        for ($i = count(static::$groupStack) - 1; $i >= 0; $i--) {
            if (!empty(static::$groupStack[$i]['controller'])) {
                return static::$groupStack[$i]['controller'];
            }
        }
        return null;
    }

    protected static function resolveName(string $name): string
    {
        $prefix = '';

        foreach (static::$groupStack as $group) {
            $prefix .= $group['name'] ?? '';
        }

        return $prefix . $name;
    }

    protected static function resolveMiddleware(array $routeMiddleware): array
    {
        $middleware = [];

        foreach (static::$groupStack as $group) {
            $middleware = array_merge($middleware, $group['middleware']);
        }

        return array_merge($middleware, $routeMiddleware);
    }

    /* =========================
     | ROUTE RESOLUTION
     ========================= */

    public static function resolve($request)
    {
        $uri = $request->uri()->path();
        $method = $request->method();

        $uriMatched = false;
        foreach (static::$routes as $route) {

            $route['uri'] = ltrim($route['uri'], '/');
            // STEP 1: cek URI dulu
            $params = match_routes($route['uri'], $uri);

            if ($params === false) {
                continue;
            }

            $uriMatched = true;

            // STEP 2: kalau URI cocok tapi METHOD beda
            if ($route['method'] !== $method) {
                continue;
            }

            // STEP 3: URI + METHOD cocok → eksekusi
            return static::runRoute($route, $request, $params);
        }

        // STEP 4: hasil akhir
        if ($uriMatched) {
            http_response_code(405);
            throw new Exception('Method Not Allowed');
        }

        http_response_code(404);
        throw new Exception('Route Not Found');
    }


    protected static function runRoute(array $route, Request $request, array $params)
    {
        if (!is_string($route['action']) || !str_contains($route['action'], '@')) {
            throw new Exception('Invalid route action');
        }

        [$controllerName, $action] = explode('@', $route['action']);

        $controllerClass = class_exists($controllerName)
            ? $controllerName
            : "App\\Controllers\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller [$controllerClass] not found");
        }

        $controller = new $controllerClass;

        return static::dispatch(
            controller: $controller,
            action: $action,
            request: $request,
            params: $params,
            middleware: $route['middleware']
        );
    }

    /* =========================
     | DISPATCHER
     ========================= */
    protected static function dispatch(
        object $controller,
        string $action,
        Request $request,
        array $params = [],
        array $middleware = []
    ) {
        $method = new ReflectionMethod($controller, $action);
        $arguments = [];
        $middleware = array_merge($middleware, static::$defaultMiddleware);
        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if ($type && !$type->isBuiltIn() && $type->getName() === Request::class) {
                $arguments[] = $request;
                continue;
            }

            if ($type && !$type->isBuiltIn()) {
                $className = $type->getName();

                if (is_subclass_of($className, Model::class)) {

                    if (array_key_exists($name, $params)) {
                        $id = $params[$name];

                        $modelInstance = $className::find($id);

                        if (!$modelInstance) {
                            throw new Exception("No query results for model [{$className}] $id");
                        }

                        $arguments[] = $modelInstance;
                        continue;
                    }
                }
            }

            if (\array_key_exists($name, $params)) {
                $arguments[] = $params[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $msg = $controller::class;
            throw new Exception(
                "Cannot resolve parameter [$name] in {$msg}::$action"
            );
        }

        $core = fn() => $method->invokeArgs($controller, $arguments);

        foreach (array_reverse($middleware) as $middlewareClass) {
            $next = $core;
            $core = fn() => (new $middlewareClass)->handle($request, $next);
        }

        return $core();
    }

    public static function getRoutes()
    {
        return static::$routes;
    }
}
